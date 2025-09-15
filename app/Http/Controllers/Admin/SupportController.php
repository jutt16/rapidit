<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;

class SupportController extends Controller
{
    public function index()
    {
        $messages = SupportMessage::latest()->paginate(10);
        return view('admin.support.index', compact('messages'));
    }

    public function show($id)
    {
        $message = SupportMessage::findOrFail($id);
        return view('admin.support.show', compact('message'));
    }
}
