<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use \App\Models\Traits\HasTenantScope;


class JournalEntryLine extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'journal_entry_line_id';

    protected $fillable = [
        'tenant_id',
        'journal_entry_id',
        'account_code',
        'account_name',
        'debit_amount',
        'credit_amount',
        'entity_id',
        'entity_type',
        'description',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id', 'journal_entry_id');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_code', 'code');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}