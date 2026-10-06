<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PlanDetail extends Model { protected $fillable=['plan_id','activity_id','sub_activity_id','target_quantity','planned_quantity','shortfall','backlog','productivity','planned_manpower']; protected $casts=['target_quantity'=>'float','planned_quantity'=>'float','shortfall'=>'float','backlog'=>'float','productivity'=>'float','planned_manpower'=>'integer']; public function plan(){return $this->belongsTo(Plan::class);} public function activity(){return $this->belongsTo(Activity::class);} public function subActivity(){return $this->belongsTo(SubActivity::class);} public function apartments(){return $this->hasMany(PlanApartmentDetail::class);}}
