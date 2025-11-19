<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone_number',
        'lab_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Casting enum agar otomatis jadi string
    protected $casts = [
        'role' => 'string',
    ];

    // Relasi ke Lab
    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }

    // Relasi User -> Loans
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    // Relasi User -> Procurement Requests
    public function procurementRequests()
    {
        return $this->hasMany(ProcurementRequest::class);
    }
}
