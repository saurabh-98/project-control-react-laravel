<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('value')->unique();
            $table->string('label', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach (range(1, 10) as $value) {
            \Illuminate\Support\Facades\DB::table('priorities')->insert([
                'value' => $value,
                'label' => 'Priority ' . $value,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('priorities');
    }
};
