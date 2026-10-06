<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Tower extends Model { protected $fillable=['sub_division_id','code','name']; public function subDivision(){return $this->belongsTo(SubDivision::class);} public function levels(){return $this->hasMany(Level::class);} }
