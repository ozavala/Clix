<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class EmailTemplateController extends TenantAwareController
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isSuper = $user && (bool) ($user->is_super_admin ?? false);
        $requestedTenantId = $request->input('tenant_id');

        if ($isSuper) {
            $query = EmailTemplate::withoutGlobalScopes()->with('creator')->orderBy('created_at', 'desc');
            if ($requestedTenantId) {
                $query->where('tenant_id', $requestedTenantId);
            }
            $templates = $query->paginate(15)->withQueryString();
            $tenants = Tenant::orderBy('name')->get(['id','name']);
            return view('email_templates.index', compact('templates', 'tenants', 'requestedTenantId'));
        } else {
            $templates = EmailTemplate::with('creator')->orderBy('created_at', 'desc')->paginate(15);
            return view('email_templates.index', compact('templates'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('email_templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'html_content' => 'nullable|string',
            'type' => 'required|in:newsletter,promotional,welcome,notification,custom',
            'variables' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = Auth::id();
        // Ensure tenant_id is set
        if (empty($validated['tenant_id'])) {
            $validated['tenant_id'] = $this->getTenantId();
        }
        $validated['is_active'] = $request->has('is_active');

        EmailTemplate::create($validated);

        return redirect()->route('email-templates.index')
            ->with('success', 'Email template created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmailTemplate $emailTemplate): View
    {
        $template = $emailTemplate->load('creator');
        return view('email_templates.show', compact('template'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailTemplate $emailTemplate): View
    {
        $template = $emailTemplate;
        return view('email_templates.edit', compact('template'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'html_content' => 'nullable|string',
            'type' => 'required|in:newsletter,promotional,welcome,notification,custom',
            'variables' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $emailTemplate->update($validated);

        return redirect()->route('email-templates.index')
            ->with('success', 'Email template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        // Verificar si hay campañas usando esta plantilla
        if ($emailTemplate->campaigns()->count() > 0) {
            return redirect()->route('email-templates.index')
                ->with('error', 'Cannot delete template that is being used by campaigns.');
        }

        $emailTemplate->delete();

        return redirect()->route('email-templates.index')
            ->with('success', 'Email template deleted successfully.');
    }

    /**
     * Preview template
     */
    public function preview(EmailTemplate $emailTemplate): View
    {
        $template = $emailTemplate;
        $previewData = [
            'customer_name' => 'John Doe',
            'company_name' => 'Your Company',
            'product_name' => 'Sample Product',
            'offer' => '20% discount',
            'unsubscribe_url' => '#',
        ];

        $renderedContent = $template->renderContent($previewData);
        $renderedHtml = $template->renderHtmlContent($previewData);

        return view('email_templates.preview', compact('template', 'renderedContent', 'renderedHtml', 'previewData'));
    }

    /**
     * Toggle template active status
     */
    public function toggleActive(EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->update(['is_active' => !$emailTemplate->is_active]);

        $status = $emailTemplate->is_active ? 'activated' : 'deactivated';
        return redirect()->route('email-templates.index')
            ->with('success', "Email template {$status} successfully.");
    }

    /**
     * Duplicate template
     */
    public function duplicate(EmailTemplate $emailTemplate): RedirectResponse
    {
        $newTemplate = $emailTemplate->replicate();
        $newTemplate->name = $emailTemplate->name . ' (Copy)';
        $newTemplate->created_by = Auth::id();
        $newTemplate->save();

        return redirect()->route('email-templates.index')
            ->with('success', 'Email template duplicated successfully.');
    }
}
