<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index(Request $request)
    {
        $query = User::with('role')
            ->whereIn('role_id', [1, 2]); // super_admin and admin only

        // Support both 'q' and 'search' parameters to align with Commodities/Varieties
        $searchQuery = $request->string('q')->trim()->toString() ?: $request->string('search')->trim()->toString();
        if ($searchQuery) {
            $query->where(function ($builder) use ($searchQuery) {
                $builder->where('name', 'like', "%{$searchQuery}%")
                    ->orWhere('email', 'like', "%{$searchQuery}%");
            });
        }

        // Order: superadmin first (role_id=1), then admin (role_id=2), tie-break by created_at ASC, then id ASC
        $admins = $query->orderBy('role_id', 'ASC')
                       ->orderBy('created_at', 'ASC')
                       ->orderBy('id', 'ASC')
                       ->paginate(15)
                       ->appends($request->query());

        // AJAX partial rendering for progressive enhancement (ignore query ?ajax=1 on normal navigation)
        if ($request->ajax()) {
            return view('admin.admin-users.partials.table-content', compact('admins'));
        }

        return view('admin.admin-users.index', compact('admins'));
    }

    /**
     * Show the form for creating a new admin user.
     */
    public function create()
    {
        $roles = Role::whereIn('id', [1, 2])->get(); // super_admin and admin only
        return view('admin.admin-users.create', compact('roles'));
    }

    /**
     * Store a newly created admin user in storage.
     */
    public function store(StoreAdminRequest $request)
    {
        $validated = $request->validated();
        
        // Hash the password
        $validated['password'] = Hash::make($validated['password']);
        
        User::create($validated);

        return redirect()->route('admin.admin-users.index')
            ->with('success', 'Admin user created successfully.');
    }

    /**
     * Display the specified admin user.
     */
    public function show(User $adminUser)
    {
        // Ensure the user is an admin
        if (!in_array($adminUser->role_id, [1, 2])) {
            abort(404);
        }

        return view('admin.admin-users.show', compact('adminUser'));
    }

    /**
     * Show the form for editing the specified admin user.
     */
    public function edit(User $adminUser)
    {
        // Ensure the user is an admin
        if (!in_array($adminUser->role_id, [1, 2])) {
            abort(404);
        }

        $roles = Role::whereIn('id', [1, 2])->get(); // super_admin and admin only
        return view('admin.admin-users.edit', compact('adminUser', 'roles'));
    }

    /**
     * Update the specified admin user in storage.
     */
    public function update(UpdateAdminRequest $request, User $adminUser)
    {
        // Ensure the user is an admin
        if (!in_array($adminUser->role_id, [1, 2])) {
            abort(404);
        }

        $validated = $request->validated();
        
        // Hash password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            // Remove password from validated data if not provided
            unset($validated['password']);
        }
        
        $adminUser->update($validated);

        return redirect()->route('admin.admin-users.index')
            ->with('success', 'Admin user updated successfully.');
    }

    /**
     * Remove the specified admin user from storage.
     */
    public function destroy(User $adminUser)
    {
        // Ensure the user is an admin
        if (!in_array($adminUser->role_id, [1, 2])) {
            abort(404);
        }

        // Prevent deletion of the current user
        if ($adminUser->id === auth()->id()) {
            // Check if this is the last super admin first
            if ($adminUser->role_id === 1) {
                $superAdminCount = User::where('role_id', 1)->count();
                if ($superAdminCount <= 1) {
                    return redirect()->route('admin.admin-users.index')
                        ->with('error', 'Cannot delete the last Super Admin user.');
                }
            }
            
            return redirect()->route('admin.admin-users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // Prevent deletion if this is the last super admin
        if ($adminUser->role_id === 1) {
            $superAdminCount = User::where('role_id', 1)->count();
            if ($superAdminCount <= 1) {
                return redirect()->route('admin.admin-users.index')
                    ->with('error', 'Cannot delete the last Super Admin user.');
            }
        }

        $adminUser->delete();

        return redirect()->route('admin.admin-users.index')
            ->with('success', 'Admin user deleted successfully.');
    }
}
