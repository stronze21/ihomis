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
        'drug_concat',
        'exp_date',
        'stock_date',
        'reference',
        'rec_revolving',
        'rec_regular',
        'rec_others',
        'iss_revolving',
        'iss_regular',
        'iss_others',
        'bal_revolving',
        'bal_regular',
        'bal_others',
    ];

    public function stock()
    {
        return $this->belongsTo(DrugStock::class, 'stock_id', 'id');
    }
}