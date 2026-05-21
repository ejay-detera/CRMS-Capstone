<?php

namespace App\Models;

use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $table = 'contracts';
    protected $primaryKey = 'contract_id';

    protected $fillable = [
        'category_id',
        'supplier_id',
        'status_id',
        'bp_name',
        'sbu_number',
        'item_code',
        'description',
        'serial_number',
        'start_date',
        'end_date',
        'created_by',
    ];

    protected $casts = [
        'bp_name' => EncryptedCast::class,
        'description' => EncryptedCast::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
