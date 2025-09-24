<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Show the form for editing the application settings.
     */
    public function edit()
    {
        Gate::forUser(auth('crm')->user())->authorize('edit-settings');
        $tenantId = auth('crm')->check() && auth('crm')->user()->tenant_id
            ? auth('crm')->user()->tenant_id
            : ((app()->bound('currentTenant') && app('currentTenant')) ? (app('currentTenant')->id ?? app('currentTenant')->tenant_id) : null);
        $coreSettings = Setting::where('type', 'core')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->get();
        $customSettings = Setting::where('type', 'custom')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->get();
        return view('settings.edit', compact('coreSettings', 'customSettings'));
    }

    /**
     * Update the application settings in storage.
     */
    public function update(Request $request)
    {
        Gate::forUser(auth('crm')->user())->authorize('edit-settings');
        $tenantId = auth('crm')->check() && auth('crm')->user()->tenant_id
            ? auth('crm')->user()->tenant_id
            : ((app()->bound('currentTenant') && app('currentTenant')) ? (app('currentTenant')->id ?? app('currentTenant')->tenant_id) : null);
        // Validar solo los core settings
        $coreKeys = Setting::where('type', 'core')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->pluck('key');
        $rules = [];
        foreach ($coreKeys as $key) {
            $rules[$key] = 'nullable|string|max:255';
        }
        $validated = $request->validate($rules);
        foreach ($validated as $key => $value) {
            Setting::withoutGlobalScopes()->updateOrCreate(
                ['key' => $key, 'tenant_id' => $tenantId],
                ['value' => $value, 'type' => 'core', 'is_editable' => true]
            );
        }
        return redirect()->route('settings.edit')->with('success', __('settings.Updated successfully'));
    }

    public function storeCustom(Request $request)
    {
        Gate::forUser(auth('crm')->user())->authorize('edit-settings');
        $tenantId = auth('crm')->check() && auth('crm')->user()->tenant_id
            ? auth('crm')->user()->tenant_id
            : ((app()->bound('currentTenant') && app('currentTenant')) ? (app('currentTenant')->id ?? app('currentTenant')->tenant_id) : null);
        $validated = $request->validate([
            'key' => ['required','string','max:255',
                \Illuminate\Validation\Rule::unique('settings','key')
                    ->where(fn($q) => $q->where('tenant_id', $tenantId))
            ],
            'value' => 'nullable|string',
        ]);
        Setting::withoutGlobalScopes()->updateOrCreate(
            ['key' => $validated['key'], 'tenant_id' => $tenantId],
            ['value' => $validated['value'], 'type' => 'custom', 'is_editable' => true]
        );
        return redirect()->route('settings.edit')->with('success', __('settings.Custom setting added'));
    }

    public function destroyCustom(Setting $setting)
    {
        Gate::forUser(auth('crm')->user())->authorize('edit-settings');
        if ($setting->type === 'custom' && $setting->is_editable) {
            $setting->delete();
            return redirect()->route('settings.edit')->with('success', __('settings.Custom setting deleted'));
        }
        return redirect()->route('settings.edit')->with('error', __('settings.Cannot delete core setting'));
    }
}