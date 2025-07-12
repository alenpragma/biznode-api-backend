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
        Schema::create('package', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('price');
            $table->string('interest_rate');
            $table->string('duration');
            $table->enum('return_type',['daily','weekly','monthly','yearly']);
            $table->enum('type',['mini','master'])->default('master');
            $table->integer('stock')->default(0);
            $table->integer('total_sell')->default(0);
            $table->boolean('active');
            $table->timestamps();
        });

        DB::table('package')->insert([
            'name' => 'Phase 1',
            'price' => '500',
            'interest_rate' => '10',
            'duration' => '30',
            'return_type' => 'daily',
            'active' => '1',
            'stock' => '5000',
        ]);
        DB::table('package')->insert([
            'name' => 'Mini Node',
            'price' => '250',
            'interest_rate' => '10',
            'duration' => '30',
            'return_type' => 'daily',
            'active' => '1',
            'stock' => '50000',
            'type' => 'mini'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package');
    }
};
