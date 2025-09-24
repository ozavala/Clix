<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CrmUser;
use App\Models\Tenant;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Get all active tenants for the registration form
        $tenants = \App\Models\Tenant::where('is_active', true)
            ->orderBy('name')
            ->get();
            
        return view('auth.register', [
            'tenants' => $tenants
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['nullable', 'string', 'max:255', 'unique:crm_users,username'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.CrmUser::class],
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
            'tenant_id' => ['required', 'integer', 'exists:tenants,tenant_id'],
        ]);

        // Start a database transaction
        return DB::transaction(function () use ($validated, $request) {
            // Generate username if not provided
            $username = $validated['username'] ?? (Str::slug($validated['full_name']) . '-' . strtolower(Str::random(4)));
            
            // Get the selected tenant first
            $tenant = Tenant::findOrFail($validated['tenant_id']);
            
            // Create the user with the tenant_id
            $user = CrmUser::create([
                'full_name' => $validated['full_name'],
                'username' => $username,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'tenant_id' => $tenant->id, // Set the tenant_id during creation
            ]);
            
            // Attach the user to the tenant and set as primary
            $user->tenants()->attach($tenant->id, ['is_primary' => true]);
            
            // Log the registration
            Log::info('New user registered', [
                'user_id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $tenant->id,
                'ip' => $request->ip()
            ]);

            // Fire the registered event
            event(new Registered($user));

            // Log the user in with the crm guard
            Auth::guard('crm')->login($user);
            $request->session()->regenerate();
            
            // Set the current tenant in the session
            session(['current_tenant_id' => $tenant->id]);

            return redirect(route('dashboard', absolute: false));
        });
    }
}
