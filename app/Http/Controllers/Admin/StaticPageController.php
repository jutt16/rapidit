<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    public function index()
    {
        $pages = StaticPage::all();
        return view('admin.static_pages.index', compact('pages'));
    }

    public function edit($id)
    {
        $page = StaticPage::findOrFail($id);
        return view('admin.static_pages.edit', compact('page'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $page = StaticPage::findOrFail($id);
        $page->update($request->only('title', 'content'));

        return redirect()->route('admin.static-pages.index')
            ->with('success', 'Page updated successfully.');
    }
}
