<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreCrmUserRequest;
use App\Http\Requests\UpdateCrmUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\UserRole;



class CrmUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $crmUsers = User::with('roles')->paginate(10);
        return view('users.index', compact('crmUsers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = UserRole::orderBy('name')->get(); 
        return view('users.create',compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCrmUserRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);

        $User= User::create($validatedData);

         if ($request->has('roles')) {
            $User->roles()->sync($request->input('roles'));
        }

        return redirect()->route('crm-users.index')->with('success', 'CRM User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $User)
    {
        return view('users.show', compact('User'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $User)
    {
       $roles = UserRole::orderBy('name')->get();
        $assignedRoles = $User->roles->pluck('role_id')->toArray();
        return view('users.edit', compact('User', 'roles', 'assignedRoles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCrmUserRequest $request, User $User)
    {
        $validatedData = $request->validated();

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']); // Don't update password if not provided
        }

        $User->update($validatedData);

         if ($request->has('roles')) {
            $User->roles()->sync($request->input('roles'));
        } else {
            $User->roles()->detach(); // Remove all roles if none are selected
        }

        return redirect()->route('crm-users.index')->with('success', 'CRM User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $User)
    {
        // Add any checks here, e.g., prevent deleting the logged-in user or last admin
        // For now, basic delete:
        // if (auth()->id() === $User->user_id) {
        //     return redirect()->route('crm-users.index')->with('error', 'You cannot delete yourself.');
        // }
        $User->roles()->detach(); // Detach roles before deleting user
        $User->delete();
        return redirect()->route('crm-users.index')->with('success', 'CRM User deleted successfully.');
    }
}