<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToPharmDrugStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_drug_stocks', function (Blueprint $table) {
            $table->foreign(['dmdctr', 'dmdcomb'])->references(['dmdctr', 'dmdcomb'])->on('hdmhdr');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pharm_drug_stocks', function (Blueprint $table) {
            $table->dropForeign(['dmdctr', 'dmdcomb']);
        });
    }
}
