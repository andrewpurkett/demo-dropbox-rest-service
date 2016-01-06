<?php

class ContextIOSubscriptionController extends BaseController {

    private $contextIO;
    private $accountIDs;

    public function __construct() {
        $this->contextIO = new ContextIO(Config::get('contextIO.key'), Config::get('contextIO.secret'));
        $this->accountIDs = [];
    }

    // Initialize dropbox authentication
    public function get()
    {
        $accountID = null;

        $callback_url = Input::has('callback') ? Input::get('callback') : 'https://' . Config::get('app.app_host') . '/link/2/finish';


        $connectDetails = ['callback_url' => $callback_url];

        if (Input::has('email')) {
            $connectDetails['email'] = Input::get('email'); // preferable, removes one context.io page from user flow
        }

        $result = $this->contextIO->addConnectToken($accountID, $connectDetails);

        if ( ! $result) {
            return [
                'success' => false,
                'error_message' => 'Failed to retrieve a connect token from context.io with connect details ' . json_encode($connectDetails),
            ];
        }

        $response = $result->getData();

        if ( ! isset($response['browser_redirect_url']) || empty($response['browser_redirect_url'])) {
            return [
                'success' => false,
                'error_message' => 'Invalid or missing browser_redirect_url in response from Context.IO',
            ];
        }

        return [
            'success'           => true,
            'authorization_url' => $response['browser_redirect_url'],
        ];
    }

    public function put($uid)
    {

        $token = Input::get('token');

        $result = $this->contextIO->getConnectToken(null, $token);

        if ( ! $result) {
            return [
                'success' => 'false',
                'error_message' => 'Failed to retrieve a connect token from context.io',
            ];
        }

        $response = $result->getData();

        $responseJSON = json_encode($response);
        $response = json_decode($responseJSON);

        $data = $response->account;

        $serviceMap = [
            'imap.googlemail.com'       => 'gmail',
            'imap-mail.outlook.com:993' => 'outlook',
        ];

        $desired_data = [
            'email_address'     => $data->email_addresses[0],
            'mailbox_id'        => $data->id,
            'service'   => isset($serviceMap[$data->sources[0]->server])
                            ? $serviceMap[$data->sources[0]->server]
                            : 'other',
        ];

        $account = new Mailbox();
        $account->id            = $uid;
        $account->mailbox_id    = $desired_data['mailbox_id'];
        $account->email_address = $desired_data['email_address'];
        $account->service       = $desired_data['service'];

        if ( ! $account->save()) {
            return [
                'success'       => false,
                'error_message' => 'Failed to save account details'
            ];
        }

        // TODO: Start sync process
        // Queue Mailbox crawl
        try {
            Queue::push('ContextIOCrawlerController@dequeue', ['id' => $uid], 'contextio.sync.' . Config::get('queue.postfix'));
        } catch (Exception $e) {
            return ['success' => false, 'error_message' => $e->getMessage()];
        }

        return [
            'success' => true,
        ];
    }

    public function delete($uid)
    {
        return [
            'success' => false,
            'error_message' => 'Not yet implemented'
        ];

        // try {
        //     $dropbox = Dropbox::find($uid);

        //     $dropbox->delete();
        // } catch (Exception $e) {
        //     return [
        //         'success' => false,
        //         'error_message' => $e->getMessage()
        //     ];
        // }

        // return [
        //     'success' => true,
        // ];
    }

}
