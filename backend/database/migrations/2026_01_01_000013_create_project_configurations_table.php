<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_configurations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('division_id')
                ->constrained('divisions')
                ->cascadeOnDelete();

            $table->foreignId('sub_division_id')
                ->constrained('sub_divisions')
                ->cascadeOnDelete();

            $table->foreignId('tower_id')
                ->constrained('towers')
                ->cascadeOnDelete();

            $table->foreignId('level_id')
                ->constrained('levels')
                ->cascadeOnDelete();

            $table->foreignId('activity_id')
                ->constrained('activities')
                ->cascadeOnDelete();

            $table->foreignId('sub_activity_id')
                ->constrained('sub_activities')
                ->cascadeOnDelete();

            $table->foreignId('apartment_id')
                ->constrained('apartments')
                ->cascadeOnDelete();

            $table->foreignId('typology_id')
                ->nullable()
                ->constrained('apartment_typologies')
                ->nullOnDelete();

            $table->decimal('quantity', 14, 3)
                ->default(0);

            $table->foreignId('uom_id')
                ->constrained('uoms')
                ->restrictOnDelete();

            $table->unsignedInteger('priority')
                ->default(1);

            $table->timestamps();

            /*
             * Explicit short index name.
             * MySQL has a 64-character identifier limit.
             */
            $table->unique(
                [
                    'project_id',
                    'division_id',
                    'sub_division_id',
                    'tower_id',
                    'level_id',
                    'activity_id',
                    'sub_activity_id',
                    'apartment_id',
                ],
                'pc_scope_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_configurations');
    }
};