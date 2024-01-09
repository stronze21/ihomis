<?php

namespace App\Console\Commands;

use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitializeStockCard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:stock-card';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize daily stock card';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $stocks = DrugStock::select('id', 'stock_bal', 'dmdcomb', 'dmdctr', 'exp_date', 'drug_concat', 'chrgcode', 'loc_code')->where('stock_bal', '>', 0)->get();

        foreach ($stocks as $stock) {
            $card = DrugStockCard::firstOrCreate([
                'chrgcode' => $stock->chrgcode,
                'loc_code' => $stock->loc_code,
                'dmdcomb' => $stock->dmdcomb,
                'dmdctr' => $stock->dmdctr,
                'drug_concat' => $stock->drug_concat(),
                'exp_date' => $stock->exp_date,
                'stock_date' => date('Y-m-d'),
                'reference' => $stock->stock_bal,
                'bal' => $stock->stock_bal,
            ]);
        }

        return 'Stock card reference value captured';
    }
}
