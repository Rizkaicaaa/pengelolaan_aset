<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'total_quantity',
    ];

    protected $casts = [
        'category' => 'string',
    ];

    // Relasi: satu asset punya banyak asset_item
    public function items()
    {
        return $this->hasMany(AssetItem::class);
    }
}
