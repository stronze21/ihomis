<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrxoSecondariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('worker')->create('hrxo_secondaries', function (Blueprint $table) {
            $table->id();
            $table->string('docointkey');
            $table->string('enccode');
            $table->string('hpercode');
            $table->string('rxooccid')->default('1');
            $table->string('rxoref')->default('1');
            $table->string('dmdcomb');
            $table->string('repdayno1')->default(1);
            $table->string('rxostatus')->default('A');
            $table->string('rxolock')->default('N');
            $table->string('rxoupsw')->default('N');
            $table->string('rxoconfd')->default('N');
            $table->string('dmdctr');
            $table->string('estatus')->default('U');
            $table->string('entryby');
            $table->string('ordcon')->default('NEWOR');
            $table->string('orderupd')->default('ACTIV');
            $table->string('locacode')->default('PHARM');
            $table->string('orderfrom');
            $table->string('issuetype')->default('c');
            $table->string('has_tag');
            $table->string('tx_type');
            $table->string('ris');
            $table->integer('pchrgqty');
            $table->decimal('pchrgup');
            $table->decimal('pcchrgamt');
            $table->dateTime('dodate');
            $table->dateTime('dotime');
            $table->dateTime('dodtepost');
            $table->dateTime('dotmepost');
            $table->dateTime('dmdprdte');
            $table->date('exp_date');
            $table->string('loc_code');
            $table->string('item_id');
            $table->string('remarks')->nullable();
            $table->string('prescription_data_id')->nullable();
            $table->string('prescribed_by')->nullable();
            $table->string('drug_concat')->nullable();
            $table->string('chrgdesc')->nullable();
            $table->integer('qtyissued')->nullable();
            $table->integer('qtybal')->nullable();
            $table->string('pcchrgcod')->nullable();
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
        Schema::connection('worker')->dropIfExists('hrxo_secondaries');
    }
}
