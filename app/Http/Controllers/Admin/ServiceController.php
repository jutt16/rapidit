<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // Show list of services
    public function index()
    {
        $services = Service::with('category')->latest()->paginate(10);
        return view('admin.services.index', compact('services'));
    }

    // Show edit form (price and tax only)
    public function edit(Service $service)
    {
        return view('admin.services.edit', compact('service'));
    }

    // Update only price and tax
    public function update(Request $request, Service $service)
    {
        $request->validate([
            'price' => 'required|numeric|min:0',
            'tax'   => 'required|numeric|min:0',
        ]);

        $service->update([
            'price' => $request->price,
            'tax'   => $request->tax,
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
    }
}
