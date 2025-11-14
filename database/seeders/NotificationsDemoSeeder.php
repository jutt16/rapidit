<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::take(5)->get();
        foreach ($users as $i => $user) {
            Notification::updateOrCreate(
                ['user_id' => $user->id, 'title' => 'Welcome ' . $user->name],
                [
                    'message' => 'This is a seeded notification #' . ($i + 1),
                    'type' => 'info',
                    'status' => 'sent',
                ]
            );
        }
    }
}


