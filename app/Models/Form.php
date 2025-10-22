<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

   protected $fillable = [
    'title',
    'structure',
    'fee_amount',
    'description',
    'status',
    ];

    protected $casts = [
        'structure' => 'array',
    ];

    
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}