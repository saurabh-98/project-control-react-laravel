<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
 Schema::create('sub_activities', function(Blueprint $t){$t->id();$t->foreignId('activity_id')->constrained()->cascadeOnDelete();$t->string('code')->unique();$t->string('name');$t->decimal('productivity',12,4)->default(1);$t->timestamps();});
 }
 public function down(): void { }
};
