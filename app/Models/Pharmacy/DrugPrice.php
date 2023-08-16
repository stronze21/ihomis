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
    public $timestamps = false;


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
        'mark_up',
        'acquisition_cost',
        'has_compounding',
        'compounding_fee',
        'retail_price',
    ];

    public function acquisition_cost()
    {
        return number_format($this->acquisition_cost, 2);
    }

    public function dmselprice()
    {
        return number_format($this->dmselprice, 2);
    }
}
