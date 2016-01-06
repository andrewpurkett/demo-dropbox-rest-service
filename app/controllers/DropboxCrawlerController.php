<?php
use Carbon\Carbon;
class DropboxCrawlerController extends BaseController {

    private $appInfo;
    private $clientIdentifier = "examples-web-file-browser";
    private $userLocale = null;
    private $last_sleep_deprivation = 0;
    private $sync_queue_id = "dropbox.sync"; // TODO: move to config
    private $file_queue_id = "files.dropbox"; // TODO: move to config

    private $active_client;

    public function __construct() {

        // Initialize Dropbox Application configuration
        $this->appInfo = Dropbox\AppInfo::loadFromJson([
            'key' => Config::get('dropbox.key'),
            'secret' => Config::get('dropbox.secret'),
        ]);

        if (Config::has('queue.postfix')) {
            $this->sync_queue_id .= '.' . Config::get('queue.postfix');
            $this->file_queue_id .= '.' . Config::get('queue.postfix');
        }
    }

    private function authenticate($contract_guid)
    {
        $dropbox = Dropbox::find($contract_guid);
        if ( ! $dropbox) {
            App::abort(403, 'Account not found');
        }
        // if they're logged in, get their dropbox information
        $this->active_client = new Dropbox\Client(
            $dropbox->dropbox_token,
            $this->clientIdentifier,
            $this->userLocale,
            $this->appInfo->getHost()
        );
    }

    public function enqueue($contract_guid)
    {
        Queue::push('DropboxCrawlerController@dequeue', ['id' => $contract_guid]);

        return ['success' => true];
    }

    public function dequeue($job, $data)
    {
        $response = $this->sync($data['id']);

        $job->release();

        // TODO: Setup error logging table in dropbox db
        // if ( ! $response['success']) {
        //     DB::table('errors')->insert($response['error']);
        // }
    }

    // Download a file at given path
    public function download($file_id)
    {
        $entry = Entry::find($file_id);
        if ($entry->is_dir) {
            App::abort(400, "Directories cannot be downloaded");
        }

        $this->authenticate($entry->dropbox_id);

        header("Content-Type: " . $entry->mime_type);
        header('Content-Disposition: attachment; filename="' . addslashes(basename($entry->original_path)) .'"');

        $fd = fopen('php://stdout', 'w');
        $meta = $this->active_client->getFile($entry->original_path, $fd);
        fclose($fd);
    }

    // Generate a link to dropbox's servers to download the file.
    // This is commented out because it is not desirable due to insecurity of generated links

    // public function downloadLink($file_id)
    // {
    //     $entry = Entry::find($file_id);
    //     if ($entry->is_dir) {
    //         App::abort(400, "Directories cannot be downloaded");
    //     }
    //
    //     $this->authenticate($entry->dropbox_id);
    //
    //     $link = $this->active_client->createTemporaryDirectLink($entry->original_path);
    //
    //     if (!is_array($link)) {
    //         return [
    //             'success'   => false,
    //         ];
    //     }
    //
    //     return [
    //         'success'   => true,
    //         'link'      => $link[0],
    //     ];
    // }

    public function sync($contract_guid)
    {
        $this->authenticate($contract_guid);

        $active_tree = Dropbox::find($contract_guid);

        $previous_delta = (empty($active_tree->delta)) ? null : $active_tree->delta;
        $delta_data = $this->active_client->getDelta($previous_delta);

        foreach (['has_more', 'cursor', 'entries', 'reset'] as $required_key) {
            if (!isset($delta_data[$required_key])) {
                return [
                    'success' => false,
                    'error_message' => 'Missing ' . $required_key,
                ];
            }
        }

        if ($delta_data['reset'] && !is_null($previous_delta)) {
            // Have yet to encounter a 'reset', documentation suggests it only
            // will occur at the initial (null) delta, and if an app folder is
            // renamed. Since we're not in an app folder, it shouldn't occur.
            //
            // That said, if it does occur, we need to know right away!
            error_log(
                "DELTA RESET ENCOUNTERED FOR USER " . $contract_guid .
                " AT DELTA " . $previous_delta . " NEED TO TEST!"
            );
            // From documentation:
            // https://www.dropbox.com/developers/core/docs#metadata-details
            // reset If true, clear your local state before processing the delta
            // entries. reset is always true on the initial call to /delta (i.e.
            // when no cursor is passed in). Otherwise, it is true in rare
            // situations, such as after server or account maintenance, or if a
            // user deletes their app folder.

            // Supposedly that means we have to reprocess this user's entire
            // dropbox account, from scratch. Very scary prospect.
            dd("Special case, not yet handled!...");
        }

        if (count($delta_data['entries']) < 1 && ! $delta_data['has_more'] && ! $delta_data['reset']) {
            $active_tree->delta = $delta_data['cursor'];
            $active_tree->save();
            return ['success' => true, 'updated' => false];
        }

        foreach ($delta_data['entries'] as $entry_key => list($entry['original_path'], $entry['update_data'])) {
            $entry_altered = false;
            $entry_created = false;
            $entry_deleted = false;

            // Remove attributes we don't track, are deprecated, etc.
            unset(
                $delta_data['entries'][$entry_key][1]['thumb_exists'],
                $delta_data['entries'][$entry_key][1]['revision'],
                $entry['update_data']['icon'],
                $entry['update_data']['root'],
                $entry['update_data']['size'],
                $entry['update_data']['hash'],
                $entry['update_data']['thumb_exists'],
                $entry['update_data']['revision']
            );

            if (is_null($entry['update_data'])) {
                $entry_deleted = true;
            } elseif ($stored_entry = Entry::where('original_path', '=', $entry['original_path'])->where('dropbox_id', $contract_guid)->first()) {
                foreach ($entry['update_data'] as $entry_column => $entry_column_value) {

                    $stored_column_name = [
                        'rev' => 'rev',
                        'bytes' => 'bytes',
                        'modified' => 'service_updated_at',
                        'client_mtime' => 'client_updated_at',
                        'path' => 'original_path',
                        'is_dir' => 'is_dir',
                        'mime_type' => 'mime_type',
                    ][$entry_column];

                    if ($entry_column_value !== $stored_entry->{$stored_column_name}) {
                        $stored_entry->{$stored_column_name} = $entry_column_value;
                        $entry_altered = true;
                    }
                }

            } else {
                // Path does not exist in the database
                $stored_entry = new Entry();
                $stored_entry->dropbox_id = $contract_guid;

                if ($entry['original_path'] == '/') {
                    $stored_entry->parent_id = 0;
                } else {

                    $parent_entry = Entry::where('original_path', '=', dirname($entry['original_path']))
                                         ->where('dropbox_id', $contract_guid)
                                         ->first();

                    if (!($parent_entry) && dirname($entry['original_path']) == '/') {
                        // Generate a root since it is not shown in deltas
                        $parent_entry = new Entry();
                        $parent_entry->original_path = '/';
                        $parent_entry->dropbox_id = $contract_guid;
                        $parent_entry->parent_id = 0;
                        $parent_entry->rev = '';
                        $parent_entry->bytes = 0;
                        $parent_entry->mime_type = '';
                        $parent_entry->service_updated_at = '';
                        $parent_entry->client_updated_at = '';
                        $parent_entry->is_dir = 1;
                        $parent_entry->save();
                    }
                    $stored_entry->parent_id = $parent_entry->id;
                }
                $stored_entry->original_path = $entry['update_data']['path'];
                $stored_entry->rev = $entry['update_data']['rev'];
                $stored_entry->bytes = $entry['update_data']['bytes'];
                $stored_entry->mime_type = isset($entry['update_data']['mime_type']) ? $entry['update_data']['mime_type'] : '';
                $stored_entry->service_updated_at = $entry['update_data']['modified'];
                $stored_entry->client_updated_at = isset($entry['update_data']['client_mtime']) ? $entry['update_data']['client_mtime'] : '';
                $stored_entry->is_dir = $entry['update_data']['is_dir'];

                $entry_created =  true;
            }

            if ($entry_altered || $entry_created) {
                $stored_entry->save();
            }

            if ($entry_created) {
                $data = [
                    'action'    => 'create',
                    'entry'     => $stored_entry->toArray(),
                ];

                if ( ! $stored_entry->is_dir) {
                    Queue::push('FileHandlerController@create', $data, $this->file_queue_id);
                }
            }

            if ($entry_altered){
                $data = [
                    'action'    => 'update',
                    'entry'     => $stored_entry->toArray(),
                ];

                if ( ! $stored_entry->is_dir) {
                    Queue::push('FileHandlerController@update', $data, $this->file_queue_id);
                }
            }

            if ($entry_deleted) {
                // TODO: Fire off single-file deletion processing for this file, here
                if ($stored_entry = Entry::where('original_path', '=', $entry['original_path'])->where('dropbox_id', $contract_guid)->first()) {

                    // Remove any/all children files and folders to this folder
                    foreach ($stored_entry->children() as $child_entry) {
                        $data = [
                            'action'    => 'remove',
                            'entry'     => $child_entry->toArray(),
                        ];
                        Queue::push('FileHandlerController@remove', $data, $this->file_queue_id);
                        $child_entry->delete();
                    }

                    $data = [
                        'action'    => 'remove',
                        'entry'     => $stored_entry->toArray(),
                    ];

                    if ( ! $stored_entry->is_dir) {
                        Queue::push('FileHandlerController@remove', $data, $this->file_queue_id);
                    }

                    $stored_entry->delete();
                }
            }
        }

        $active_tree->delta = $delta_data['cursor'];
        $active_tree->save();

        // One delta sync per queue job
        return ['success' => true, 'updated' => true];
    }

    public function status($contract_guid)
    {
        $this->authenticate($contract_guid);

        $active_tree = Dropbox::find($contract_guid);

        $previous_delta = (empty($active_tree->delta)) ? null : $active_tree->delta;
        $delta_data = $this->active_client->getDelta($previous_delta);

        foreach (['has_more', 'cursor', 'entries', 'reset'] as $required_key) {
            if (!isset($delta_data[$required_key])) {
                return [
                    'success' => false,
                    'error_message' => 'Missing ' . $required_key,
                ];
            }
        }

        if ($delta_data['reset'] && !is_null($previous_delta)) {
            // Have yet to encounter a 'reset', documentation suggests it only
            // will occur at the initial (null) delta, and if an app folder is
            // renamed. Since we're not in an app folder, it shouldn't occur.
            //
            // That said, if it does occur, we need to know right away!
            error_log(
                "DELTA RESET ENCOUNTERED FOR USER " . $contract_guid .
                " AT DELTA " . $previous_delta . " NEED TO TEST!"
            );
            // From documentation:
            // https://www.dropbox.com/developers/core/docs#metadata-details
            // reset If true, clear your local state before processing the delta
            // entries. reset is always true on the initial call to /delta (i.e.
            // when no cursor is passed in). Otherwise, it is true in rare
            // situations, such as after server or account maintenance, or if a
            // user deletes their app folder.

            // Supposedly that means we have to reprocess this user's entire
            // dropbox account, from scratch. Very scary prospect.
            dd("Special case, not yet handled!...");
        }

        if (count($delta_data['entries']) < 1 && ! $delta_data['has_more'] && ! $delta_data['reset']) {
            $active_tree->delta = $delta_data['cursor'];
            $active_tree->save();
            return ['success' => true, 'synced' => 'true'];
        }
        return ['success' => true, 'synced' => 'false'];
    }

    public function info($contract_guid)
    {

        $this->authenticate($contract_guid);

        $accountInfoRaw = $this->active_client->getAccountInfo();

        return [
            'success'   => true,
            'data'      => [
                'id'        => $accountInfoRaw['uid'],
                'name'      => $accountInfoRaw['display_name'],
                'email'     => $accountInfoRaw['email'],
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
        // $sync_status = $this->status($contract_guid); // no longer used?
        $user = Dropbox::find($contract_guid);
        if ( ! $user) {
            throw new Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        $entries = $user->entries;

        $apiEntries = [];

        foreach ($entries->toArray() as $entry) {

            if ($entry['is_dir']) {
                continue; // skip directories
            }

            // if (is_null($entry['file_sha']) || is_null($entry['etag'])) {
            //     continue; // skip files that are not yet downloaded
            // }

            if (strrpos(basename($entry['original_path']), '.')) {
                $extension = substr(basename($entry['original_path']), strrpos(basename($entry['original_path']), '.'));
            } else {
                $extension = ".extensionless";
            }

            if (is_null($entry['file_sha'])) {
                $local_url = 'https://' . Config::get('app.drobpox_host') . '/download/' . $entry['id'];
            } else {
                $local_url = 'https://' . Config::get('app.content_host') . '/dropbox/' . $entry['file_sha'] . $extension;
            }
            $remote_url = 'https://www.dropbox.com/home' . dirname($entry['original_path']); // TODO: URL encoding?

            $created_at = Carbon::createFromFormat('Y-m-d H:i:s', $entry['created_at'])->toRFC2822String();
            $updated_at = Carbon::createFromFormat('Y-m-d H:i:s', $entry['updated_at'])->toRFC2822String();

            $apiEntry = [
                'local_url'             => $local_url,
                'remote_url'            => $remote_url,
                'original_path'         => $entry['original_path'],
                'mime_type'             => $entry['mime_type'],
                'bytes'                 => $entry['bytes'],
                'service_created_at'    => $entry['service_created_at'],
                'service_updated_at'    => $entry['service_updated_at'],
                'client_created_at'     => $entry['client_created_at'],
                'client_updated_at'     => $entry['client_updated_at'],
                'created_at'            => $created_at,
                'updated_at'            => $updated_at,
                'file_sha'              => $entry['file_sha'],
                'etag'                  => $entry['etag'], // return in desired order as per API spec
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

        $entry = Entry::find($file_id);
        if ($entry->is_dir) {
            return ['success' => false, 'error_message' => 'Cannot compute SHA1 of directory'];
        }

        $this->authenticate($entry->dropbox_id);

        $tmpfname = tempnam(sys_get_temp_dir(), $file_id);
        $fd = fopen($tmpfname, "w+");
        $meta = $this->active_client->getFile($entry->original_path, $fd);

        $sha1 = sha1_file($tmpfname);
        if (strrpos(basename($entry->original_path), '.')) {
            $extension = substr(basename($entry->original_path), strrpos(basename($entry->original_path), '.'));
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

        // Close the temp file pointer
        fclose($fd);

        // Update the database with the file size information
        $entry->file_sha = $sha1;
        $entry->etag = sha1($sha1 . $entry->bytes . $entry->service_updated_at . $entry->client_updated_at);
        $entry->save();

        // TODO: Error handling
        // TODO: Process abstraction from controller to classes

        return [
            'success'   => true,
            'local_url' => 'http://' . Config::get('app.content_host') .'/' . $save_path,
        ];

    }

    public function requeue_deadbeats() {
        dd("Don't run me yet, I'm not ready.");
        foreach (Entry::where('file_sha', null)->where('is_dir', 0)->get() as $entry) {

            $data = [
                'action'    => 'create',
                'entry'     => $entry->toArray(),
            ];

            if ( ! $stored_entry->is_dir) {
                Queue::push('FileHandlerController@create', $data, $this->file_queue_id);
            }
        }
    }
}
