<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PlanApartmentDetail extends Model { protected $fillable=['plan_detail_id','apartment_id','required_quantity','planned_quantity','completion_quantity','planned_manpower','reason_id','reason_text']; protected $casts=['required_quantity'=>'float','planned_quantity'=>'float','completion_quantity'=>'float','planned_manpower'=>'integer']; public function planDetail(){return $this->belongsTo(PlanDetail::class);} public function apartment(){return $this->belongsTo(Apartment::class);} public function reason(){return $this->belongsTo(Reason::class);} }
