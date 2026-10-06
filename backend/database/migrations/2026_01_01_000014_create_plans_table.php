<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
 Schema::create('plans', function(Blueprint $t){$t->id();$t->foreignId('project_id')->constrained()->cascadeOnDelete();$t->date('plan_date');$t->string('status')->default('draft');$t->foreignId('created_by')->constrained('users');$t->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamp('submitted_at')->nullable();$t->timestamps();$t->unique(['project_id','plan_date']);});
 }
 public function down(): void { }
};
