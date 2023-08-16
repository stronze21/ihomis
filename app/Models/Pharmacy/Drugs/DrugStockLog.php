<?php

namespace App\Models\Pharmacy\Drugs;

use App\Models\Pharmacy\Drug;
use Awobaz\Compoships\Compoships;
use App\Models\Pharmacy\DrugPrice;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DrugStockLog extends Model
{
    use HasFactory;
    use Compoships;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.pharm_drug_stock_logs';

    protected $fillable = [
        'loc_code',
        'dmdcomb',
        'dmdctr',
        'chrgcode',
        'date_logged',
        'time_logged',

        'dmdprdte',
        'unit_cost',
        'unit_price',

        'beg_bal', //nullable()->default(0);
        'purchased', //nullable()->default(0);
        'transferred', //nullable()->default(0);
        'received', //nullable()->default(0);
        'charged_qty', //nullable()->default(0);
        'issue_qty', //nullable()->default(0);
        'return_qty', //nullable()->default(0);

        'sc_pwd', //nullable()->default(0);
        'ems', //nullable()->default(0);
        'maip', //nullable()->default(0);
        'wholesale', //nullable()->default(0);
        'pay', //nullable()->default(0);
        'medicare', //nullable()->default(0);
        'service', //nullable()->default(0);
        'govt_emp', //nullable()->default(0);
        'caf', //nullable()->default(0);
        'dmdprdte',
    ];

    public function charge()
    {
        return $this->belongsTo(ChargeCode::class, 'chrgcode', 'chrgcode');
    }

    public function location()
    {
        return $this->belongsTo(PharmLocation::class, 'loc_code', 'id');
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr'])
            ->with('strength')->with('form')->with('route')->with('generic');
    }

    public function date_logged()
    {
        return date('m/d/Y', strtotime($this->date_logged));
    }

    public function time_logged()
    {
        return date('H:i A', strtotime($this->time_logged));
    }

    public function available()
    {
        return $this->beg_bal + $this->purchased;
    }

    public function available_amount()
    {
        $unit_cost = $this->current_price->acquisition_cost;
        return ($this->beg_bal + $this->purchased) * $unit_cost;
    }

    public function total_cost()
    {
        $unit_cost = $this->current_price->acquisition_cost;
        return $this->purchased * $unit_cost;
    }

    public function total_sales()
    {
        return ($this->issue_qty - $this->return_qty) * $this->current_price->dmselprice;
    }

    public function total_cogs()
    {
        $unit_cost = $this->current_price->acquisition_cost;
        $total_qty_issued = $this->issue_qty - $this->return_qty;
        return $total_qty_issued * $unit_cost;
    }

    public function total_profit()
    {
        $unit_cost = $this->current_price->acquisition_cost;
        $total_qty_issued = ($this->issue_qty - $this->return_qty);
        $unit_sales_cost = $total_qty_issued * $unit_cost;
        $unit_sales = $total_qty_issued * $this->current_price->dmselprice;
        return $unit_sales - $unit_sales_cost;
    }

    public function ending_balance()
    {
        $beg_bal = $this->beg_bal;
        $purchased = $this->purchased;
        $issued = $this->issue_qty - $this->return_qty;

        return ($beg_bal + $purchased) - $issued;
    }

    public function current_price()
    {
        return $this->belongsTo(DrugPrice::class, 'dmdprdte', 'dmdprdte');
    }
}
