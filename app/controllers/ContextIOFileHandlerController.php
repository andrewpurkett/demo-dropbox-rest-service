<?php

class ContextIOFileHandlerController extends BaseController {

    private $file_queue_id = "files.contextio"; // TODO: move to config
    private $listing_queue_id = "picard.listings";

    public function __construct()
    {

        // Initialize Dropbox Application configuration
        if (Config::has('queue.postfix')) {
            $this->file_queue_id .= '.' . Config::get('queue.postfix');
            $this->listing_queue_id .= '.' . Config::get('queue.postfix');
        }

    }

    public function create($job, $data)
    {
        $dropboxClient = new \Guzzle\Service\Client('https://' . Config::get('app.dropbox_host') . '/');

        $request = $dropboxClient->get('2/localize/' . $data['attachment']['id']);
        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYHOST, false); // for testing ONLY!
        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYPEER, false); // for testing ONLY!

        $response = $request->send()->json();
        if ($response['success']) {
            $job->delete();
        } else {
            dd($response);
            // TODO: logging of error
            $job->release();
        }
    }

    public function fire($job, $data)
    {
        switch ($data['action']) {
            case 'create':
                return $this->create($job, $data);
            case 'update': // not yet handled
            case 'delete': // not yet handled
            default:
                $job->release();
        }
    }

}
