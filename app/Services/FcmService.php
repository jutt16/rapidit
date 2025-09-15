<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;

class FcmService
{
    protected $messaging;

    public function __construct()
    {
        // $factory = (new Factory)
        //     ->withServiceAccount(config('services.firebase.credentials'))
        //     ->withProjectId(config('services.firebase.project_id'));
        $path = realpath(storage_path('app/firebase/firebase-admin.json'));

        $factory = (new \Kreait\Firebase\Factory())->withServiceAccount($path);


        $this->messaging = $factory->createMessaging();
    }

    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): MulticastSendReport
    {
        $message = CloudMessage::new()
            ->withNotification([
                'title' => $title,
                'body'  => $body,
            ])
            ->withData($data);

        return $this->messaging->sendMulticast($message, $tokens);
    }


    public function sendToTopic(string $topic, string $title, string $body, array $data = [])
    {
        $message = [
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'topic' => $topic,
        ];

        // Only add data if it exists
        if (!empty($data)) {
            // Make sure all values are strings
            $message['data'] = array_map('strval', $data);
        }


        return $this->messaging->send($message);
    }

    public function subscribeToTopic(string $token, string $topic)
    {
        return $this->messaging->subscribeToTopic($topic, $token);
    }
}
