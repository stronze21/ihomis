<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugStockCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('worker')->create('pharm_drug_stock_cards', function (Blueprint $table) {
            $table->id();
            $table->string('stock_id');
            $table->date('stock_date');
            $table->string('reference')->nullable();
            $table->decimal('rec_revolving')->nullable();
            $table->decimal('rec_regular')->nullable();
            $table->decimal('rec_others')->nullable();
            $table->decimal('iss_revolving')->nullable();
            $table->decimal('iss_regular')->nullable();
            $table->decimal('iss_others')->nullable();
            $table->decimal('bal_revolving')->nullable();
            $table->decimal('bal_regular')->nullable();
            $table->decimal('bal_others')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('worker')->dropIfExists('pharm_drug_stock_cards');
    }
}