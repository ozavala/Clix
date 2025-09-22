<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use \App\Models\Traits\HasTenantScope;

class Note extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $primaryKey = 'note_id';

    protected $fillable = ['tenant_id',
                        'body',
                        'noteable_id', 
                        'noteable_type', 
                        'created_by_user_id'];

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id');
    }
}