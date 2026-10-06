<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Plan extends Model { protected $fillable=['project_id','plan_date','status','created_by','submitted_by','submitted_at']; protected $casts=['plan_date'=>'date','submitted_at'=>'datetime']; public function project(){return $this->belongsTo(Project::class);} public function details(){return $this->hasMany(PlanDetail::class);} public function creator(){return $this->belongsTo(User::class,'created_by');} public function submitter(){return $this->belongsTo(User::class,'submitted_by');} }
