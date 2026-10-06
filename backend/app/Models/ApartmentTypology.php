<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApartmentTypology extends Model { protected $fillable=['code','name']; public function apartments(){return $this->hasMany(Apartment::class,'typology_id');} }
