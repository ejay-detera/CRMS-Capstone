<?php

namespace App\Models;

use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';
    protected $primaryKey = 'supplier_id';
    
    // Disable default timestamps because suppliers table only has created_at
    const UPDATED_AT = null;

    protected $fillable = [
        'supplier_name',
        'tin_number',
        'industry',
        'contact_person',
        'contact_number',
        'email',
        'address',
        'region',
        'status',
    ];

    protected $casts = [
        'contact_number' => EncryptedCast::class,
        'email' => EncryptedCast::class,
        'address' => EncryptedCast::class,
    ];
}
