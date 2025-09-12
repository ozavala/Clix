<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CrmUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        return view('auth.register');
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
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ]);

        $username = $validated['username'] ?? (Str::slug($validated['full_name']) . '-' . strtolower(Str::random(4)));

        $user = CrmUser::create([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'full_name' => $validated['full_name'],
            'username' => $username,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        $guard = config('auth.defaults.guard', 'crm');
        Auth::guard($guard)->loginUsingId($user->getKey());
        $request->session()->regenerate();

        return redirect(route('dashboard', absolute: false));
    }
}
