<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\HasTenantScope;

class EmailTemplate extends Model
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'tenant_id',
        'name',
        'subject',
        'content',
        'html_content',
        'type',
        'variables',
        'settings',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(MarketingCampaign::class);
    }

    // Métodos de utilidad
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getAvailableVariables(): array
    {
        return $this->variables ?? [];
    }

    public function renderContent(array $data = []): string
    {
        $content = $this->content;
        
        foreach ($data as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }
        
        return $content;
    }

    public function renderHtmlContent(array $data = []): string
    {
        $content = $this->html_content ?? $this->content;
        
        foreach ($data as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }
        
        return $content;
    }
}
