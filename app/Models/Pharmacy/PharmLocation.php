<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmLocation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_locations';

    protected $fillable = [
        'description',
    ];
}
