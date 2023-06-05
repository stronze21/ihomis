<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugGeneric extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hgen';

    public function group()
    {
        return $this->belongsTo(DrugGroup::class, 'gencode', 'gencode');
    }
}
