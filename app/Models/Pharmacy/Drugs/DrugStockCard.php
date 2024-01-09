<?php

namespace App\Models\Pharmacy\Drugs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugStockCard extends Model
{
    use HasFactory;

    protected $connection = 'worker';
    protected $table = 'pharm_drug_stock_cards';

    protected $fillable = [
        'loc_code',
        'dmdcomb',
        'dmdctr',
        'chrgcode',
        'drug_concat',
        'exp_date',
        'stock_date',
        'reference',
        'rec',
        'iss',
        'bal',
    ];

    public function stock()
    {
        return $this->belongsTo(DrugStock::class, 'stock_id', 'id');
    }
}
