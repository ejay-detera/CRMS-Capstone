<?php

namespace App\Models;

use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';
    protected $primaryKey = 'document_id';
    
    // Disable default timestamps because documents table only has uploaded_at
    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'file_name',
        'file_path',
        'document_url',
        'file_type',
        'file_size',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'file_path' => EncryptedCast::class,
        'uploaded_at' => 'datetime',
    ];
}
