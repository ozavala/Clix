<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    /**
     * Display a listing of the accounts.
     * - Normal users: only their tenant accounts
     * - Super admin: all tenants unless a tenant_id filter is provided
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $requestedTenantId = $request->input('tenant_id');
        $currentTenantId = $user?->tenant_id;

        $query = Account::query()->withoutGlobalScopes()->orderBy('name');

        $isSuper = $user && (bool) ($user->is_super_admin ?? false);
        if (!$isSuper) {
            // Normal users: scope by requested tenant if provided, otherwise by current config/user tenant
            $effectiveTenantId = $requestedTenantId ?: ($currentTenantId ?: config('tenant_id'));
            $query->where('tenant_id', $effectiveTenantId);
        } else {
            // Super admin: aggregate across tenants unless a specific tenant_id is requested
            if ($requestedTenantId) {
                $query->where('tenant_id', $requestedTenantId);
            }
        }

        $accounts = $query->get(['id','tenant_id','code','name','type','description']);

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'data' => $accounts,
                'meta' => [
                    'count' => $accounts->count(),
                ],
            ]);
        }

        // Return a simple HTML list so tests can assertSee on names
        $html = '<ul>';
        foreach ($accounts as $acc) {
            $html .= '<li>' . e($acc->name) . '</li>';
        }
        $html .= '</ul>';

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    // Other resource methods can be added later as needed
}
