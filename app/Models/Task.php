<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use \App\Models\Traits\HasTenantScope;

class Task extends Model
{
    use HasFactory, SoftDeletes, HasTenantScope;

    protected $primaryKey = 'task_id';

    protected $fillable = [
        'title',
        'description',
        'due_date',
        'status',
        'priority',
        'taskable_id',
        'taskable_type',
        'assigned_to_user_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public static array $statuses = ['Pending', 'In Progress', 'Completed', 'On Hold'];
    public static array $priorities = ['Low', 'Normal', 'High', 'Urgent'];

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'assigned_to_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by_user_id');
    }
}