<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use \App\Models\Traits\HasTenantScope;

class EmailLog extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'recipient_id',
        'email',
        'subject',
        'type',
        'status',
        'content',
        'metadata',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'error_message',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    // Relaciones
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(CampaignRecipient::class, 'recipient_id');
    }

    // Métodos de utilidad
    public function isDelivered(): bool
    {
        return in_array($this->status, ['delivered', 'opened', 'clicked']);
    }

    public function isOpened(): bool
    {
        return in_array($this->status, ['opened', 'clicked']);
    }

    public function isClicked(): bool
    {
        return $this->status === 'clicked';
    }

    public function isBounced(): bool
    {
        return $this->status === 'bounced';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSent($query)
    {
        return $query->whereIn('status', ['sent', 'delivered', 'opened', 'clicked']);
    }

    public function scopeDelivered($query)
    {
        return $query->whereIn('status', ['delivered', 'opened', 'clicked']);
    }

    public function scopeOpened($query)
    {
        return $query->whereIn('status', ['opened', 'clicked']);
    }

    public function scopeClicked($query)
    {
        return $query->where('status', 'clicked');
    }

    public function scopeBounced($query)
    {
        return $query->where('status', 'bounced');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
