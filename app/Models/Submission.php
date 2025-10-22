<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    use HasFactory;
    protected $table = 'form_submissions';
    protected $fillable = [
        'user_id',
        'form_id',
        'form_data',
        'status',
        'admin_notes',
        'document_path',
        'processed_by',
        'payment_id',
        'transaction_id',
        'payment_status',
    ];

    protected $casts = [
        'form_data' => 'array',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
    
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
    
    public function getDocumentPathAttribute()
    {
        $docPath = $this->attributes['document_path'] ?? null;
    
        if (!empty($docPath)) {
            return asset('storage/' . $docPath);
        }
    
        return asset('assets/placeholder/default-document.png');
    }
    
}
