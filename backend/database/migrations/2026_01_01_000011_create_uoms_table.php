<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
 Schema::create('uoms', function(Blueprint $t){$t->id();$t->string('code')->unique();$t->string('name');$t->timestamps();});
 }
 public function down(): void { }
};
