<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
 Schema::create('reasons', function(Blueprint $t){$t->id();$t->string('code')->unique();$t->string('name');$t->boolean('active')->default(true);$t->timestamps();});
 }
 public function down(): void { }
};
