<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
 Schema::create('levels', function(Blueprint $t){$t->id();$t->foreignId('tower_id')->constrained()->cascadeOnDelete();$t->string('code');$t->string('name');$t->integer('sort_order')->default(0);$t->timestamps();$t->unique(['tower_id','code']);});
 }
 public function down(): void { }
};
