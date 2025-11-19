<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'asset_name',
        'quantity',
        'category',
        'reason',
        'request_status',
        'rejection_reason',
    ];

    protected $casts = [
        'category' => 'string',
        'request_status' => 'string',
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function isAdminJurusan()
{
    return $this->role === 'admin_jurusan';
}

public function isAdminLab()
{
    return $this->role === 'admin_lab';
}

public function isDosen()
{
    return $this->role === 'dosen';
}

}