<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
 Schema::create('apartments', function(Blueprint $t){$t->id();$t->string('code')->unique();$t->string('name');$t->foreignId('typology_id')->nullable()->constrained('apartment_typologies')->nullOnDelete();$t->timestamps();});
 }
 public function down(): void { }
};
