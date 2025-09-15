<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Services\FcmService;

class NotificationController extends Controller
{
    protected FcmService $fcm;

    public function __construct(FcmService $fcm)
    {
        $this->fcm = $fcm;
    }

    public function index()
    {
        $notifications = Notification::latest()->paginate(15);
        return view('admin.notifications.index', compact('notifications'));
    }

    public function create()
    {
        return view('admin.notifications.create');
    }

    /**
     * Store a newly created notification in storage and send it.
     */
    public function store(Request $request)
    {
        // Basic validation
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'topic' => 'required|string',
            'data' => 'nullable|string',
        ]);

        // Decode JSON safely, default if empty
        $data = ['no_data' => true]; // default if empty
        if ($request->filled('data')) {
            $decoded = json_decode($request->data, true);
            if (is_array($decoded)) {
                $data = $decoded;
            } else {
                return back()->withInput()->with('error', 'Custom Data must be valid JSON.');
            }
        }

        // Create notification
        $notification = Notification::create([
            'title' => $request->title,
            'body' => $request->body,
            'topic' => $request->topic,
            'data' => $data,
        ]);

        // Send via FCM
        try {
            $this->fcm->sendToTopic(
                $notification->topic,
                $notification->title,
                $notification->body,
                $notification->data
            );

            $notification->update(['sent' => true]);

            return redirect()->route('admin.notifications.index')->with('success', 'Notification sent successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.notifications.index')->with('error', 'Notification created but failed to send: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified notification in storage.
     */
    public function update(Request $request, Notification $notification)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'topic' => 'required|string',
            'data' => 'nullable|string',
        ]);

        // Decode JSON safely, default if empty
        $data = ['no_data' => true]; // default if empty
        if ($request->filled('data')) {
            $decoded = json_decode($request->data, true);
            if (is_array($decoded)) {
                $data = $decoded;
            } else {
                return back()->withInput()->with('error', 'Custom Data must be valid JSON.');
            }
        }

        $notification->update([
            'title' => $request->title,
            'body' => $request->body,
            'topic' => $request->topic,
            'data' => $data,
        ]);

        return redirect()->route('admin.notifications.index')->with('success', 'Notification updated successfully.');
    }

    public function show(Notification $notification)
    {
        return view('admin.notifications.show', compact('notification'));
    }

    public function edit(Notification $notification)
    {
        return view('admin.notifications.edit', compact('notification'));
    }

    public function resend(Notification $notification)
    {
        try {
            $this->fcm->sendToTopic(
                $notification->topic,
                $notification->title,
                $notification->body,
                $notification->data ?? []
            );

            $notification->update(['sent' => true]);

            return redirect()->route('admin.notifications.index')->with('success', 'Notification resent successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.notifications.index')->with('error', 'Failed to resend notification: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(Notification $notification)
    {
        try {
            $notification->delete();
            return redirect()->route('admin.notifications.index')
                ->with('success', 'Notification deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.notifications.index')
                ->with('error', 'Failed to delete notification: ' . $e->getMessage());
        }
    }
}
