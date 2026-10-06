<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Apartment extends Model { protected $fillable=['code','name','typology_id']; public function typology(){return $this->belongsTo(ApartmentTypology::class,'typology_id');} }
