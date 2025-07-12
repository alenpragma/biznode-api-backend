<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referrals_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('invest_level_1')->default(0);
            $table->integer('roi_level_1')->default(0);
            $table->integer('roi_level_2')->default(0);
            $table->integer('roi_level_3')->default(0);
            $table->timestamps();
        });
        DB::table('referrals_settings')->insert([
            'invest_level_1' => 0,
            'roi_level_1' => 0,
            'roi_level_2' => 0,
            'roi_level_3' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals_settings');
    }
};
