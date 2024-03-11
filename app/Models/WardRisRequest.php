<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WardRisRequest extends Model
{
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_ward_ris_requests';

    protected $fillable = [
        'trans_no',
        'stock_id',
        'ris_location_id',
        'dmdcomb',
        'dmdctr',
        'loc_code',
        'chrgcode',
        'issued_qty',
        'issued_by',
        'trans_stat',
        'dmdprdte'
    ];
}