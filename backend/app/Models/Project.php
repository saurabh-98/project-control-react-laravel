<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Project extends Model { protected $fillable=['code','name','status']; public function divisions(){return $this->hasMany(Division::class);} public function configurations(){return $this->hasMany(ProjectConfiguration::class);} public function plans(){return $this->hasMany(Plan::class);} }
