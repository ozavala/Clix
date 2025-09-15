@php
    // Resolve tenant_id in priority:
    // 1) currentTenant shared to views
    // 2) explicit $tenantId passed in
    // 3) $model->tenant_id if provided
    // 4) Auth::user()->tenant_id as last resort
    $resolvedTenantId = null;
    if (isset($currentTenant) && !empty($currentTenant?->id)) {
        $resolvedTenantId = $currentTenant->id;
    } elseif (isset($tenantId) && !empty($tenantId)) {
        $resolvedTenantId = $tenantId;
    } elseif (isset($model) && !empty($model?->tenant_id)) {
        $resolvedTenantId = $model->tenant_id;
    } elseif (Auth::check() && !empty(Auth::user()->tenant_id)) {
        $resolvedTenantId = Auth::user()->tenant_id;
    }
@endphp
@if(!empty($resolvedTenantId))
    <input type="hidden" name="tenant_id" value="{{ $resolvedTenantId }}">
@endif
