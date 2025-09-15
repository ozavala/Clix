<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\HasTenantScope;

class MarketingCampaign extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'subject',
        'content',
        'status',
        'type',
        'email_template_id',
        'created_by',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'unsubscribed_count',
        'target_audience',
        'settings',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'target_audience' => 'array',
        'settings' => 'array',
    ];

    // Relaciones
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class, 'campaign_id');
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'campaign_id');
    }

    // Métodos de utilidad
    public function getOpenRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }
        return round(($this->opened_count / $this->sent_count) * 100, 2);
    }

    public function getClickRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }
        return round(($this->clicked_count / $this->sent_count) * 100, 2);
    }

    public function getBounceRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }
        return round(($this->bounced_count / $this->sent_count) * 100, 2);
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function canBeSent(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'scheduled', 'sending']);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')->where('scheduled_at', '<=', now());
    }
}
