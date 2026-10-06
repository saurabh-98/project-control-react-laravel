<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
 Schema::create('plan_apartment_details', function(Blueprint $t){$t->id();$t->foreignId('plan_detail_id')->constrained()->cascadeOnDelete();$t->foreignId('apartment_id')->constrained()->cascadeOnDelete();$t->decimal('required_quantity',14,3)->default(0);$t->decimal('planned_quantity',14,3)->default(0);$t->decimal('completion_quantity',14,3)->default(0);$t->unsignedInteger('planned_manpower')->default(0);$t->foreignId('reason_id')->nullable()->constrained()->nullOnDelete();$t->text('reason_text')->nullable();$t->timestamps();$t->unique(['plan_detail_id','apartment_id']);});
 }
 public function down(): void { }
};
