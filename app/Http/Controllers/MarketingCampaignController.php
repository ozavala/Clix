<?php

namespace App\Http\Controllers;

use App\Models\MarketingCampaign;
use App\Models\EmailTemplate;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\CampaignRecipient;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\MarketingEmail;

class MarketingCampaignController extends TenantAwareController
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
            $query = MarketingCampaign::withoutGlobalScopes()->with(['creator', 'emailTemplate'])->orderBy('created_at', 'desc');
            if ($requestedTenantId) {
                $query->where('tenant_id', $requestedTenantId);
            }
            $campaigns = $query->paginate(15)->withQueryString();
            $tenants = Tenant::orderBy('name')->get(['id','name']);
            return view('marketing_campaigns.index', compact('campaigns', 'tenants', 'requestedTenantId'));
        } else {
            $campaigns = MarketingCampaign::with(['creator', 'emailTemplate'])->orderBy('created_at', 'desc')->paginate(15);
            return view('marketing_campaigns.index', compact('campaigns'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $templates = EmailTemplate::active()->get();
        $customers = Customer::orderBy('first_name')->orderBy('last_name')->get();
        $leads = Lead::orderBy('title')->get();

        return view('marketing_campaigns.create', compact('templates', 'customers', 'leads'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:email,newsletter,promotional,announcement',
            'email_template_id' => 'nullable|exists:email_templates,id',
            'scheduled_at' => 'nullable|date|after:now',
            'target_audience' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $validated['created_by'] = Auth::id();
        if (empty($validated['tenant_id'])) {
            $validated['tenant_id'] = $this->getTenantId();
        }
        $validated['status'] = $validated['scheduled_at'] ? 'scheduled' : 'draft';

        $campaign = MarketingCampaign::create($validated);

        return redirect()->route('marketing-campaigns.index')
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(MarketingCampaign $marketingCampaign): View
    {
        $campaign = $marketingCampaign->load(['creator', 'emailTemplate', 'recipients']);
        
        $stats = [
            'total_recipients' => $campaign->recipients()->count(),
            'sent_count' => $campaign->recipients()->sent()->count(),
            'opened_count' => $campaign->recipients()->opened()->count(),
            'clicked_count' => $campaign->recipients()->clicked()->count(),
            'bounced_count' => $campaign->recipients()->bounced()->count(),
            'unsubscribed_count' => $campaign->recipients()->unsubscribed()->count(),
        ];

        return view('marketing_campaigns.show', compact('campaign', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MarketingCampaign $marketingCampaign): View
    {
        $campaign = $marketingCampaign;
        $templates = EmailTemplate::active()->get();
        $customers = Customer::all();
        $leads = Lead::all();

        return view('marketing_campaigns.edit', compact('campaign', 'templates', 'customers', 'leads'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MarketingCampaign $marketingCampaign): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:email,newsletter,promotional,announcement',
            'email_template_id' => 'nullable|exists:email_templates,id',
            'scheduled_at' => 'nullable|date|after:now',
            'target_audience' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $marketingCampaign->update($validated);

        return redirect()->route('marketing-campaigns.index')
            ->with('success', 'Campaign updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MarketingCampaign $marketingCampaign): RedirectResponse
    {
        if ($marketingCampaign->isSent()) {
            return redirect()->route('marketing-campaigns.index')
                ->with('error', 'Cannot delete a sent campaign.');
        }

        $marketingCampaign->delete();

        return redirect()->route('marketing-campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Add recipients to campaign
     */
    public function addRecipients(Request $request, MarketingCampaign $marketingCampaign): RedirectResponse
    {
        $validated = $request->validate([
            'recipients' => 'required|array',
            'recipients.*.email' => 'required|email',
            'recipients.*.name' => 'nullable|string',
            'recipients.*.customer_id' => 'nullable|exists:customers,id',
            'recipients.*.lead_id' => 'nullable|exists:leads,id',
        ]);

        $recipients = collect($validated['recipients'])->map(function ($recipient) use ($marketingCampaign) {
            return [
                'campaign_id' => $marketingCampaign->id,
                'email' => $recipient['email'],
                'name' => $recipient['name'] ?? null,
                'customer_id' => $recipient['customer_id'] ?? null,
                'lead_id' => $recipient['lead_id'] ?? null,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        CampaignRecipient::insert($recipients->toArray());

        $marketingCampaign->update([
            'total_recipients' => $marketingCampaign->recipients()->count()
        ]);

        return redirect()->route('marketing-campaigns.show', $marketingCampaign)
            ->with('success', 'Recipients added successfully.');
    }

    /**
     * Send campaign
     */
    public function send(MarketingCampaign $marketingCampaign): RedirectResponse
    {
        if (!$marketingCampaign->canBeSent()) {
            return redirect()->route('marketing-campaigns.show', $marketingCampaign)
                ->with('error', 'Campaign cannot be sent in its current status.');
        }

        $recipients = $marketingCampaign->recipients()->where('status', 'pending')->get();

        if ($recipients->isEmpty()) {
            return redirect()->route('marketing-campaigns.show', $marketingCampaign)
                ->with('error', 'No recipients to send to.');
        }

        $marketingCampaign->update(['status' => 'sending']);

        // Enviar emails en background
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)
                    ->send(new MarketingEmail($marketingCampaign, $recipient));

                $recipient->update([
                    'status' => 'sent',
                    'sent_at' => now()
                ]);
            } catch (\Exception $e) {
                $recipient->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }
        }

        $marketingCampaign->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_count' => $marketingCampaign->recipients()->sent()->count()
        ]);

        return redirect()->route('marketing-campaigns.show', $marketingCampaign)
            ->with('success', 'Campaign sent successfully.');
    }

    /**
     * Schedule campaign
     */
    public function schedule(Request $request, MarketingCampaign $marketingCampaign): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $marketingCampaign->update([
            'scheduled_at' => $validated['scheduled_at'],
            'status' => 'scheduled'
        ]);

        return redirect()->route('marketing-campaigns.show', $marketingCampaign)
            ->with('success', 'Campaign scheduled successfully.');
    }

    /**
     * Pause campaign
     */
    public function pause(MarketingCampaign $marketingCampaign): RedirectResponse
    {
        if ($marketingCampaign->isSending()) {
            $marketingCampaign->update(['status' => 'paused']);
            return redirect()->route('marketing-campaigns.show', $marketingCampaign)
                ->with('success', 'Campaign paused successfully.');
        }

        return redirect()->route('marketing-campaigns.show', $marketingCampaign)
            ->with('error', 'Campaign cannot be paused.');
    }

    /**
     * Resume campaign
     */
    public function resume(MarketingCampaign $marketingCampaign): RedirectResponse
    {
        if ($marketingCampaign->status === 'paused') {
            $marketingCampaign->update(['status' => 'sending']);
            return redirect()->route('marketing-campaigns.show', $marketingCampaign)
                ->with('success', 'Campaign resumed successfully.');
        }

        return redirect()->route('marketing-campaigns.show', $marketingCampaign)
            ->with('error', 'Campaign cannot be resumed.');
    }

    /**
     * Cancel campaign
     */
    public function cancel(MarketingCampaign $marketingCampaign): RedirectResponse
    {
        if (in_array($marketingCampaign->status, ['draft', 'scheduled', 'paused'])) {
            $marketingCampaign->update(['status' => 'cancelled']);
            return redirect()->route('marketing-campaigns.show', $marketingCampaign)
                ->with('success', 'Campaign cancelled successfully.');
        }

        return redirect()->route('marketing-campaigns.show', $marketingCampaign)
            ->with('error', 'Campaign cannot be cancelled.');
    }
}
