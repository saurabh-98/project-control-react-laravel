<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Activity extends Model { protected $fillable=['code','name']; public function subActivities(){return $this->hasMany(SubActivity::class);} public function configurations(){return $this->hasMany(ProjectConfiguration::class);} }
