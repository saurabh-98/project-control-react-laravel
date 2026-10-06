<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
 Schema::create('plan_details', function(Blueprint $t){$t->id();$t->foreignId('plan_id')->constrained()->cascadeOnDelete();$t->foreignId('activity_id')->constrained()->cascadeOnDelete();$t->foreignId('sub_activity_id')->constrained()->cascadeOnDelete();$t->decimal('target_quantity',14,3)->default(0);$t->decimal('planned_quantity',14,3)->default(0);$t->decimal('shortfall',14,3)->default(0);$t->decimal('backlog',14,3)->default(0);$t->decimal('productivity',12,4)->default(1);$t->unsignedInteger('planned_manpower')->default(0);$t->timestamps();$t->unique(['plan_id','sub_activity_id']);});
 }
 public function down(): void { }
};
