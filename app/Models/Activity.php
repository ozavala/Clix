<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use \App\Models\Traits\HasTenantScope;

class Activity extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'activity_id';

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'user_id',
        'type',
        'description',
        'activity_date',
    ];

    protected $casts = [
        'activity_date' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'lead_id');
    }

    public function user(): BelongsTo
    {
        // User who performed/logged the activity
        return $this->belongsTo(CrmUser::class, 'user_id', 'user_id');
    }
}