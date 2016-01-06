<?php
use Carbon\Carbon;
class ContextIOCrawlerController extends BaseController {

    private $sync_queue_id = "contextio.sync"; // TODO: move to config
    private $file_queue_id = "files.contextio"; // TODO: move to config

    private $contextIO;
    private $accountIDs;

    public function __construct()
    {
        $this->contextIO = new ContextIO(Config::get('contextIO.key'), Config::get('contextIO.secret'));
        $this->accountIDs = [];

        if (Config::has('queue.postfix')) {
            $this->sync_queue_id .= '.' . Config::get('queue.postfix');
            $this->file_queue_id .= '.' . Config::get('queue.postfix');
        }
    }

    public function enqueue($contract_guid)
    {
        Queue::push('ContextIOCrawlerController@dequeue', ['id' => $contract_guid], $this->sync_queue_id);

        return ['success' => true];
    }

    public function dequeue($job, $data)
    {
        $response = $this->sync($data['id']);

        if ( ! $response['success']) {
            DB::table('failed_jobs')->insert(['queue' => $this->sync_queue_id, 'connection' => 'context.io', 'payload' => json_encode(['job_id' => $job->getJobId(), 'job_data' => $data, 'response' => $response]), 'failed_at' => date('Y-m-d H:i:s')]);
            if ($job->attempts() > 3)
            {
                $job->delete();
                return;
            }
        }

        $job->release();
    }

    public function sync($contract_guid = '1462206426458726')
    {
        Log::info('Syncing ' . $contract_guid);

        $mailbox = Mailbox::find($contract_guid);

        if ( ! $mailbox) {
            return [
                'success' => false,
                'error_message' => 'No such mailbox by contract GUID ' . $contract_guid,
            ];
        }

        $files = [];
        $saves = $failed_saves = 0;

        if ($mailbox->last_synced_at !== null && $mailbox->last_synced_at !== '0000-00-00 00:00:00') {
            $files_since = Carbon::createFromFormat('Y-m-d H:i:s', $mailbox->last_synced_at)->timestamp;
            $filter_by = 'indexed_after';
        } else {
            $files_since = time() - 365*24*60*60; // default to 1 year ago
            $filter_by = 'date_after';
        }

        Log::info('Syncing ' . $contract_guid . ' since ' . date('Y-m-d H:i:s', $files_since));

        $listLimit = 500;
        $listOffset = 0;
        $carryOffset = true;
        $lastTimeStamp = time();

        while ($carryOffset) {
            if ($result = $this->contextIO->listFiles(
                    /* get files that have been indexed since the last sync... or use date_after for specific date ranges */
                    $mailbox->mailbox_id, [$filter_by => $files_since, 'limit' => $listLimit, 'offset' => $listOffset]
                )
            ) {

                $lastTimeStamp = time();

                $attachments = $result->getData();

                if (count($attachments) > 0) {
                    foreach ($attachments as $attachment) {
                        if ($this->saveAttachment($attachment, $mailbox->id)) {
                            $saves++;
                        } else {
                            $failed_saves++;
                        }
                    }

                    if (count($attachments) < $listLimit) {
                        $carryOffset = false;
                    } else {
                        $listOffset += $listLimit;
                    }
                } else {
                    $carryOffset = false;
                }
            }

            Log::info('Loop cycle completed for ' . $contract_guid . ', at offset ' . $listOffset . ', synced ' . $saves . ' files (' . $failed_saves . ' failed)');

        }

        if (($saves + $failed_saves) > 0 || ($mailbox->last_synced_at !== null && $mailbox->last_synced_at !== '0000-00-00 00:00:00')) {
            $mailbox->last_synced_at = date('Y-m-d H:i:s', $lastTimeStamp);
            $mailbox->save();
        } else {
            Log::info('Last sync date not updated for ' . $contract_guid . ' because zero files were synced. Sleeping 10 minutes to allow context.io to sync some files...');
            sleep(600);
        }

        Log::info('Done retrieving file info for ' . $contract_guid . ', synced ' . $saves . ' files (' . $failed_saves . ' failed)');

        if ($saves + $failed_saves < 1) {
            Log::warning('Sleeping two minutes to prevent queue over-utilization due to inactivity. This should be disabled in a high user count environment');
            sleep(120); // prevent eating queue bandwidth on iron.io while there is only 1 user to sync
        }

        // TODO: Update the value of mailboxes last_synced_at column to date('Y-m-d H:i:s')

        if ($failed_saves > 0) {
            return [
                'success' => false,
                'files_added' => $saves,
                'files_failed' => $failed_saves,
            ];
        }

        return [
            'success' => true,
            'files_added' => $saves
        ];
    }

    private function saveAttachment($file, $mailbox_id)
    {
        $attachment = new Attachment();
        $attachment->mailbox_id         = $mailbox_id;
        $attachment->attachment_id      = $file['file_id'];
        $attachment->original_path      = $file['addresses']['from']['email'] . '/' . $file['message_id'] . '/' . $file['file_name'];

        $attachment->bytes              = $file['size'];
        $attachment->mime_type          = $file['type'];
        $attachment->file_sha           = null; // Compute when downloaded
        $attachment->etag               = null; // Compute when downloaded
        $attachment->service_created_at = date('r', $file['date_received']);
        $attachment->service_updated_at = date('r', $file['date_indexed']);
        $attachment->client_created_at  = date('r', $file['date']);
        $attachment->client_updated_at  = null; // never known

        if ( ! $attachment->save()) {
            return false;
        }

        Queue::push(
            'ContextIOFileHandlerController@create',
            [
                'action'        =>  'create',
                'attachment'    =>  $attachment->toArray(),
            ],
            $this->file_queue_id
        );

        return true;
    }

    public function download($attachment_guid)
    {

        $attachment = Attachment::find($attachment_guid);

        if ( ! $attachment) {
            throw new Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        header("Content-Type: " . $attachment->mime_type);
        header('Content-Disposition: attachment; filename="' . addslashes(basename($attachment->original_path)) .'"');

        exit($this->contextIO->getFileContent($attachment->mailbox_id, ['file_id'=> $attachment->attachment_id]));
    }

    public function status($contract_guid)
    {
        return $this->info($contract_guid); // alias to info
    }

    public function info($contract_guid)
    {

        $mailbox = Mailbox::find($contract_guid);

        if ( ! $mailbox) {
            throw new Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        // if last_synced_at is less than 1 hour ago, consider mailbox synced
        $synced = (time() - Carbon::createFromFormat('Y-m-d H:i:s', $mailbox->last_synced_at)->timestamp < 60 * 60) ? true : false;

        return [
            'success'   => true,
            'data'      => [
                'mailbox_id'    => $mailbox->mailbox_id,
                'mailbox_type'  => $mailbox->service,
                'email'         => $mailbox->email_address,
                'synced'        => $synced,
            ],
        ];
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function files($contract_guid) // `list` is reserved
    {
        $attachments = Attachment::where('mailbox_id', $contract_guid)->get();

        $apiEntries = [];

        foreach ($attachments->toArray() as $attachment) {

            if (strrpos(basename($attachment['original_path']), '.')) {
                $extension = substr(basename($attachment['original_path']), strrpos(basename($attachment['original_path']), '.'));
            } else {
                $extension = ".extensionless";
            }

            $local_url = 'https://' . Config::get('app.content_host') . '/dropbox/' . $attachment['file_sha'] . $extension;
            $remote_url = 'https://' . Config::get('app.dropbox_host') . '/context.io/download/' . $attachment['id'];

            $created_at = Carbon::createFromFormat('Y-m-d H:i:s', $attachment['created_at'])->toRFC2822String();
            $updated_at = Carbon::createFromFormat('Y-m-d H:i:s', $attachment['updated_at'])->toRFC2822String();

            $apiEntry = [
                'local_url'             => $local_url,
                'remote_url'            => $remote_url,
                'original_path'         => $attachment['original_path'],
                'mime_type'             => $attachment['mime_type'],
                'bytes'                 => $attachment['bytes'],
                'service_created_at'    => $attachment['service_created_at'],
                'service_updated_at'    => $attachment['service_updated_at'],
                'client_created_at'     => $attachment['client_created_at'],
                'client_updated_at'     => $attachment['client_updated_at'],
                'created_at'            => $created_at,
                'updated_at'            => $updated_at,
                'file_sha'              => $attachment['file_sha'],
                'etag'                  => $attachment['etag'], // return in desired order as per API spec
            ];

            $apiEntries[] = $apiEntry;
        }

        return [
            $apiEntries // TODO: Envelope...
        ];
    }

    public function localize($file_id)
    {

        $contentClient = new \Guzzle\Service\Client('http://' . Config::get('app.content_host') . '/');

        $attachment = Attachment::find($file_id);

        $mailbox = Mailbox::find($attachment->mailbox_id);

        $tmpfname = tempnam(sys_get_temp_dir(), $file_id);

        $response = $this->contextIO->getFileContent($mailbox->mailbox_id, ['file_id'=> $attachment->attachment_id], $tmpfname);

        if ( ! $response) {
            return [
                'success'       => false,
                'error_message' => 'Failed to retrieve context.io file contents',
            ];
        }

        $sha1 = sha1_file($tmpfname);

        if (strrpos(basename($attachment->original_path), '.')) {
            $extension = substr(basename($attachment->original_path), strrpos(basename($attachment->original_path), '.'));
        } else {
            $extension = ".extensionless";
        }

        $save_path = 'dropbox/' . $sha1 . $extension;

        // check the byte size of the cached file if it exists on the content server
        $request = $contentClient->head($save_path);
        try {
            $response = $request->send();
            $contentLength = $response->getContentLength();
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $clientResponseException = $e->getResponse();
            $statusCode = $clientResponseException->getStatusCode();
            if ($statusCode !== 404) {
                return [
                    'success' => false,
                    'error_message' => 'HEAD request from content server path ' . $save_path .
                        ' raised http exception ' . $statusCode . "\n" .
                        print_r($clientResponseException, true),
                ];
            }

            $contentLength = false;
        }

        // Raise an exception if we are about to localize this file in place of another file with the same content SHA
        if ($contentLength && $contentLength > 0 && $contentLength != filesize($tmpfname)) {
            return [
                'success' => false,
                'error_message' => "File exists on content server with same SHA but different byte count!",
            ];
        }

        // Upload the file if it does not already exist on the content server
        if ( ! $contentLength) {
            $request = $contentClient->put($save_path);

            $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYHOST, false); // for testing ONLY!
            $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYPEER, false); // for testing ONLY!
            // TODO: Determine which approach is best of these two:
            // Having a second pointer on same file, while first pointer is still open
            // (Temp files are deleted as soon as fclose called)
            $request->setBody(fopen($tmpfname, 'r'));

            // Or should we use....
            // rewind($fd);
            // $request->setBody($fd);
            // I'm not sure if guzzle would support that,
            // nor if w+ mode supports rewind
            // (Will it read from start without erasing after a rewind in w+?)

            $response = $request->send();
        }

        // Update the database with the file size information
        $attachment->file_sha = $sha1;
        $attachment->etag = sha1($sha1 . $attachment->bytes . $attachment->service_updated_at . $attachment->client_updated_at);
        $attachment->save();

        // TODO: Error handling
        // TODO: Process abstraction from controller to classes

        return [
            'success'   => true,
            'local_url' => 'http://' . Config::get('app.content_host') .'/' . $save_path,
        ];

    }
}
