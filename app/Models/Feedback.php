<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use \App\Models\Traits\HasTenantScope;

class Feedback extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'feedback_id';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'title',
        'description',
        'type',
        'status',
    ];

    /**
     * Get the user who submitted the feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'user_id', 'user_id');
    }
}