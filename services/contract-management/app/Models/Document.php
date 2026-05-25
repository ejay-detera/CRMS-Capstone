<?php

namespace App\Models;

use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\HybridRelations;

class Document extends Model
{
    use HasFactory, HybridRelations;

    protected $connection = 'mongodb';
    protected $collection = 'documents';
    protected $primaryKey = '_id';
    
    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'uuid',
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

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id', 'contract_id');
    }
}
