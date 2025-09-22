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

    public static function core()
    {
        return static::where('type', 'core');
    }

    public static function custom()
    {
        return static::where('type', 'custom');
    }
}
