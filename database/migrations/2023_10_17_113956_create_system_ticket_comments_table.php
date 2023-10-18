<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemTicketCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('system_ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreignId('system_ticket_id');
            $table->text('comment');
            $table->text('attachments')->nullable();
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
        Schema::dropIfExists('system_ticket_comments');
    }
}
