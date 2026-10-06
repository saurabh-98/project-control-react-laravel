<?php
namespace App\Http\Controllers;
use App\Models\{Project,Division,SubDivision,Tower,Level,Activity,SubActivity,Apartment,ApartmentTypology,Uom,Reason};
use Illuminate\Http\Request;
class MasterDataController extends Controller {
 public function projects(){return Project::orderBy('name')->get();}
 public function divisions(Project $project){return $project->divisions()->orderBy('name')->get();}
 public function subDivisions(Division $division){return $division->subDivisions()->orderBy('name')->get();}
 public function towers(SubDivision $subDivision){return $subDivision->towers()->orderBy('name')->get();}
 public function levels(Tower $tower){return $tower->levels()->orderBy('sort_order')->get();}
 public function activities(){return Activity::with('subActivities')->orderBy('name')->get();}
 public function apartments(){return Apartment::with('typology')->orderBy('code')->get();}
 public function typologies(){return ApartmentTypology::orderBy('name')->get();}
 public function uoms(){return Uom::orderBy('name')->get();}
 public function reasons(){return Reason::where('active',true)->orderBy('name')->get();}
}
