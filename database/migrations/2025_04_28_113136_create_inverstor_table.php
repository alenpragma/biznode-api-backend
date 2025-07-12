<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('package_name');
            $table->foreignId('package_id')->references('id')->on('package')->onDelete('cascade');
            $table->string('return_type');
            $table->float('investment')->default(0);
            $table->integer('duration')->nullable();
            $table->float('payable_amount')->nullable();
            $table->float('total_receive')->nullable()->default(0);
            $table->bigInteger('total_receive_day')->nullable()->default(0);
            $table->bigInteger('total_due_day')->nullable()->default(0);
            $table->dateTime('start_date');
            $table->dateTime('next_cron');
            $table->dateTime('last_cron');
            $table->timestamps();
            $table->boolean('status')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investors');
    }
};
