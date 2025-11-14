<?php

namespace App\Services;

use App\Models\UserNotification;
use App\Models\User;
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

    /**
     * Send notification to a single user and store in database
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        // Store notification in database first
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => 'fcm',
            'data' => $data,
            'sent' => false,
        ]);

        // Try to send via FCM if user has token
        if ($user->fcm_token) {
            try {
                $this->sendToTokens([$user->fcm_token], $title, $body, $data);
                $notification->update(['sent' => true]);
                return true;
            } catch (\Exception $e) {
                // Notification stored but not sent
                \Log::error("FCM send failed for user {$user->id}: " . $e->getMessage());
                return false;
            }
        }

        return false;
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
