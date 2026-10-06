<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SubActivity extends Model { protected $fillable=['activity_id','code','name','productivity']; protected $casts=['productivity'=>'float']; public function activity(){return $this->belongsTo(Activity::class);} }
