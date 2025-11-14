<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Display list of users
    public function index(Request $request)
    {
        $query = User::query();

        // Role filter
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->with(['partnerProfile', 'addresses'])->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    // Show create form
    public function create()
    {
        return view('admin.users.create');
    }

    // Store new user
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,partner,user',
        ]);

        User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'phone_verified' => true,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status ? 1 : 0,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    // Show user details
    public function show(User $user)
    {
        $user->load([
            'profile',
            'addresses',
            'partnerProfile.services.category',
        ]);

        return view('admin.users.show', compact('user'));
    }

    // Show edit form
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    // Update user
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:admin,partner,user',
        ]);

        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->role = $request->role;
        $user->status = $request->status ? 1 : 0;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    // Delete user
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    // Export users to CSV
    public function export(Request $request)
    {
        $query = User::with(['wallet', 'partnerProfile']);

        // Apply same filters as index
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->get();

        $fileName = 'users_export_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID',
                'Name',
                'Phone',
                'Phone Verified',
                'Role',
                'Status',
                'Partner Status',
                'Wallet Balance',
                'Experience (Years)',
                'Rating',
                'Total Reviews',
                'Created At',
                'Updated At'
            ]);

            // CSV rows
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->phone,
                    $user->phone_verified ? 'Yes' : 'No',
                    ucfirst($user->role),
                    $user->status ? 'Active' : 'Inactive',
                    $user->partner_status ?? 'N/A',
                    $user->wallet ? $user->wallet->balance : '0.00',
                    $user->partnerProfile->experience ?? 'N/A',
                    $user->partnerProfile->rating ?? 'N/A',
                    $user->partnerProfile->total_reviews ?? 'N/A',
                    $user->created_at,
                    $user->updated_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
