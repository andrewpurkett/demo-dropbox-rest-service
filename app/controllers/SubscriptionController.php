<?php

// Replaces Dropbox\ArrayEntryStore, which is poorly written such that you are forced
// into implementation around a php variable array
Class DbxCSRFSessionStore implements Dropbox\ValueStore {
    private $csrfData;

    function get()
    {
        return $this->csrfData;
    }

    function set($value)
    {
        $this->csrfData = $value;
    }

    function clear()
    {
        $this->csrfData = null;
    }

}

class SubscriptionController extends BaseController {
    private $appInfo;
    private $clientIdentifier = "examples-web-file-browser";
    private $sync_queue_id = "dropbox.sync"; // TODO: move to config
    private $file_queue_id = "files.dropbox"; // TODO: move to config
    private $csrfStore;

    public function __construct() {

        // Initialize Dropbox Application configuration
        $this->appInfo = Dropbox\AppInfo::loadFromJson([
            'key' => Config::get('dropbox.key'),
            'secret' => Config::get('dropbox.secret'),
        ]);

        $this->csrfStore = new DbxCSRFSessionStore();
        if (Input::has('state')) {
            $this->csrfStore->set(Input::get('state'));
        }

        if (Config::has('queue.postfix')) {
            $this->sync_queue_id .= '.' . Config::get('queue.postfix');
            $this->file_queue_id .= '.' . Config::get('queue.postfix');
        }
    }

    // Initialize dropbox authentication
    public function get()
    {
        return json_encode([
            'success'               => true,
            'authorization_url'     => $this->getWebAuth()->start(),
        ]);
    }

    public function put($uid)
    {
        $this->callbackURL = Input::get('callback');

        $subscription_data = [
            'code'  => Input::get('code'),
            'state' => Input::get('state'),
        ];

        $authorization_data = [];

        try {
           list($authorization_data['dropbox_access_token'], $authorization_data['dropbox_user_id'], $authorization_data['url_state']) = $this->getWebAuth()->finish($subscription_data);
        } catch (Dropbox\Exception_BadRequest $e) {
            return ['success' => false, 'error_message' => $e->getMessage()];
        }

        assert($authorization_data['url_state'] === null);

        // Store this as a new Dropbox
        try {
            $dropbox = new Dropbox;
            $dropbox->id = $uid;
            $dropbox->dropbox_authorized_id = $authorization_data['dropbox_user_id'];
            $dropbox->dropbox_token = $authorization_data['dropbox_access_token'];
            $dropbox->save();
        } catch (Illuminate\Database\QueryException $e) {
            return ['success' => false, 'error_message' => $e->getMessage()];
        }

        // Queue Dropbox crawl
        try {
            Queue::push('DropboxCrawlerController@dequeue', ['id' => $dropbox->id], $this->sync_queue_id);
        } catch (Exception $e) {
            return ['success' => false, 'error_message' => $e->getMessage()];
        }

        return ['success' => true];
    }

    public function delete($uid)
    {
        try {
            $dropbox = Dropbox::find($uid);

            $dropbox->delete();
        } catch (Exception $e) {
            return [
                'success' => false,
                'error_message' => $e->getMessage()
            ];
        }

        return [
            'success' => true,
        ];
    }

    private function getWebAuth()
    {
        if (! Input::has('callback')) {
            throw new Exception("No callbackURL provided");
        }

        $callbackURL = Input::get('callback');

        return new Dropbox\WebAuth(
            $this->appInfo,
            $this->clientIdentifier,
            $callbackURL,
            $this->csrfStore
        );
    }

}
