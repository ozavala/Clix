<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory;

    protected $primaryKey = 'category_id';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'parent_category_id',
    ];

    /**
     * Get the parent category.
     */
    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_category_id', 'category_id');
    }

    /**
     * Get the child categories.
     */
    public function childCategories(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_category_id', 'category_id');
    }

    /**
     * Get the products for the category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
    /**
     * Check if the current category is a descendant of the given category.
     *
     * @param ProductCategory|null $potentialAncestor
     * @return bool
     */
    public function isDescendantOf(?ProductCategory $potentialAncestor): bool
    {
        if (!$potentialAncestor) {
            return false;
        }

        $parent = $this->parent;
        while ($parent) {
            if ($parent->category_id === $potentialAncestor->category_id) {
                return true;
            }
            $parent = $parent->parent;
        }
        return false;
    }
}