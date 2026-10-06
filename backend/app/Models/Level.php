<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Level extends Model { protected $fillable=['tower_id','code','name','sort_order']; public function tower(){return $this->belongsTo(Tower::class);} }
