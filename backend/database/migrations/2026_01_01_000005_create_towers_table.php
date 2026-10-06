<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
 Schema::create('towers', function(Blueprint $t){$t->id();$t->foreignId('sub_division_id')->constrained()->cascadeOnDelete();$t->string('code');$t->string('name');$t->timestamps();$t->unique(['sub_division_id','code']);});
 }
 public function down(): void { }
};
