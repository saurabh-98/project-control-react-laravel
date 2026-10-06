<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('name');
            $table->date('finish_date')->nullable()->after('start_date');
            $table->unsignedInteger('planned_working_days')
                ->nullable()
                ->after('finish_date');
        });

        Schema::table('sub_activities', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('productivity');
            $table->date('finish_date')->nullable()->after('start_date');
            $table->unsignedInteger('planned_working_days')
                ->nullable()
                ->after('finish_date');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn([
                'start_date',
                'finish_date',
                'planned_working_days',
            ]);
        });

        Schema::table('sub_activities', function (Blueprint $table) {
            $table->dropColumn([
                'start_date',
                'finish_date',
                'planned_working_days',
            ]);
        });
    }
};