<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'color',
        'wood_type',
        'upholstery',
        'finish',
        'size',
        'width_cm',
        'depth_cm',
        'height_cm',
        'weight_kg',
        'seat_height_cm',
        'price',
        'stock_quantity',
        'image_url',
        'is_available',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'width_cm' => 'decimal:1',
        'depth_cm' => 'decimal:1',
        'height_cm' => 'decimal:1',
        'weight_kg' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }
}
