<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'asset_item_id',
        'loan_purpose',
        'loan_date',
        'return_date',
        'loan_status',
        'rejection_reason',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'return_date' => 'date',
        'loan_status' => 'string',
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke asset item
    public function assetItem()
    {
        return $this->belongsTo(AssetItem::class);
    }
}
