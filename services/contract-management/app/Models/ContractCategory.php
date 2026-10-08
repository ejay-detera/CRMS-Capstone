<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractCategory extends Model
{
    protected $table = 'contract_categories';
    protected $primaryKey = 'category_id';
    public $timestamps = false;
    protected $fillable = ['category_name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
