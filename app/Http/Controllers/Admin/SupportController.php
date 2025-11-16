<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;

class SupportController extends Controller
{
    public function index()
    {
        $messages = SupportMessage::with('user')->latest()->paginate(10);
        return view('admin.support.index', compact('messages'));
    }

    public function show($id)
    {
        $message = SupportMessage::with('user')->findOrFail($id);
        return view('admin.support.show', compact('message'));
    }

    public function export()
    {
        $messages = SupportMessage::latest()->get();

        $fileName = 'support_messages_export_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function() use ($messages) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Subject',
                'Message',
                'Created At'
            ]);

            // CSV rows
            foreach ($messages as $message) {
                fputcsv($file, [
                    $message->id,
                    $message->name ?? 'N/A',
                    $message->email ?? 'N/A',
                    $message->phone ?? 'N/A',
                    $message->subject ?? 'N/A',
                    $message->message ?? 'N/A',
                    $message->created_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
