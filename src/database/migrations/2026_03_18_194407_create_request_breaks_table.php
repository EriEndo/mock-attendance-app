<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestBreaksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_breaks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('correction_request_id')
                ->constrained('correction_requests')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('break_no');

            $table->time('requested_break_start_at')->nullable();
            $table->time('requested_break_end_at')->nullable();

            $table->timestamps();

            $table->unique(['correction_request_id', 'break_no']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_breaks');
    }
}
