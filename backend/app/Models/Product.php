<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'material',
        'brand',
        'base_price',
        'sale_price',
        'image_url',
        'is_active',
        'is_featured',
    ];

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function variants()
    {
        return $this->hasMany(\App\Models\ProductVariant::class);
    }
}
