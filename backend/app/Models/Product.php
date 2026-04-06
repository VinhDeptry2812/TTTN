<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'brand',
        'material',
        'base_price',
        'sale_price',
        'image_url',
        'is_featured',
        'is_active',
        'view_count',
        'stock_quantity',
    ];

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function variants()
    {
        return $this->hasMany(\App\Models\ProductVariant::class);
    }

    public function images()
    {
        return $this->hasMany(\App\Models\ProductImage::class)->orderBy('sort_order', 'asc');
    }

}
