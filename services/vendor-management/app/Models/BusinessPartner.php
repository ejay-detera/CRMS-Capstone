<?php

namespace App\Models;

use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessPartner extends Model
{
    use HasFactory;

    protected $table = 'business_partners';
    protected $primaryKey = 'partner_id';

    protected $fillable = [
        'bp_code',
        'partner_name',
        'contact_number',
        'email',
        'address',
        'region',
        'created_by',
    ];

    protected $casts = [
        'contact_number' => EncryptedCast::class,
        'email' => EncryptedCast::class,
        'address' => EncryptedCast::class,
        'created_by' => 'integer',
    ];
}
