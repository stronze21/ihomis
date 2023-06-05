<?php

namespace App\Models\Pharmacy;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugPrice extends Model
{
    use HasFactory;
    use Compoships;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hdmhdrprice';
    public $incrementing = false;
    public $timestamps = false ;


    protected $fillables = [
        'dmdcomb',
        'dmdctr',
        'dmhdrsub',
        'dmduprice',
        'unitcode',
        'dmdrem',
        'dmdprdte',
        'dmselprice',
        'stockbal',
        'expdate',
        'brandname',
        'stock_id',
    ];

}
