<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'asset_code',
        'condition',
        'status',
        'procurement_date',
        'description',
    ];

    protected $casts = [
        'condition' => 'string',
        'status' => 'string',
        'procurement_date' => 'date',
    ];

    // Relasi: item milik satu asset
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    // Relasi: item bisa dipinjam banyak kali
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}
