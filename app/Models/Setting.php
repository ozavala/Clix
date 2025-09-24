<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \App\Models\Traits\HasTenantScope;

class Setting extends Model
{
    use HasFactory, HasTenantScope;

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['key', 'value', 'type', 'is_editable', 'tenant_id', 'group'];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id) && app()->bound('currentTenant') && app('currentTenant')) {
                $model->tenant_id = app('currentTenant')->id ?? app('currentTenant')->tenant_id;
            }
        });
    }

    public static function core()
    {
        return static::where('type', 'core');
    }

    public static function custom()
    {
        return static::where('type', 'custom');
    }
}
