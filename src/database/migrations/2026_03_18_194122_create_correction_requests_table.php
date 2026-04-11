<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();

            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->enum('request_type', ['user_request', 'admin_direct']);

            $table->enum('status', ['pending', 'approved'])->default('pending');

            $table->time('requested_clock_in_at')->nullable();
            $table->time('requested_clock_out_at')->nullable();

            $table->text('note');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();


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
        Schema::dropIfExists('correction_requests');
    }
}
