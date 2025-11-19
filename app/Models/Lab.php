<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lab extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    // Relasi: satu lab punya banyak admin_lab (User)
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
