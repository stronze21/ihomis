<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugGroup extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hdruggrp';
    protected $primaryKey = 'grpcode';
    protected $keyType = 'string';

    public function generic()
    {
        return $this->belongsTo(DrugGeneric::class, 'gencode', 'gencode');
    }

    public function drug()
    {
        return $this->hasMany(Drug::class, 'grpcode', 'grpcode');
    }
}
