<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Division extends Model { protected $fillable=['project_id','code','name']; public function project(){return $this->belongsTo(Project::class);} public function subDivisions(){return $this->hasMany(SubDivision::class);} }
