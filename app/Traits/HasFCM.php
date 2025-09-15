<?php

namespace App\Traits;

use App\Services\FcmService;

trait HasFcm
{
    /**
     * Send FCM notification to this user (using fcm_token column).
     */
    public function sendFcmNotification(string $title, string $body, array $data = []): bool
    {
        if (!$this->fcm_token) {
            return false; // user has no token
        }

        app(FcmService::class)->sendToTokens([$this->fcm_token], $title, $body, $data);

        return true;
    }

    /**
     * Subscribe this user to an FCM topic.
     */
    public function subscribeToTopic(string $topic): bool
    {
        if (!$this->fcm_token) {
            return false;
        }

        app(FcmService::class)->subscribeToTopic($this->fcm_token, $topic);

        return true;
    }
}
