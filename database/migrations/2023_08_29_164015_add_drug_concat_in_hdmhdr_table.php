<?php

use App\Models\Pharmacy\Drug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDrugConcatInHdmhdrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hdmhdr', function (Blueprint $table) {
            $table->string('drug_concat')->nullable();
        });

        $drugs = Drug::all();
        foreach ($drugs as $drug) {
            $drug->drug_concat = $drug->drug_name();
            $drug->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hdmhdr', function (Blueprint $table) {
            $table->dropColumn('drug_concat');
        });
    }
}
