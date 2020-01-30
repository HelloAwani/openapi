<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\Space as SpaceInterface;
use DB;

class Space implements SpaceInterface {
    
	public function getDataTable($display, $column, $perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param){
		$offset = $start;
		$result = DB::table('Space')->join('SpaceSection', 'Space.SpaceSectionID', '=', 'SpaceSection.SpaceSectionID')->select($display)->where('Space.BranchID', $param->BranchID)->where('Space.BrandID', $param->MainID)->where('Space.Archived', null);
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword, $column){
                for($i = 0; $i < count($column);$i++){
                    $query->orWhere(DB::raw('lower(trim("'.$column[$i].'"::varchar))'),'like','%'.strtolower($keyword).'%');
                }
        	});	
        }
        $totalFiltered = $result->count();
        $maxPage = ceil($totalFiltered/$perPage);
        if(!empty($orderBy)){
        	if(strtolower($sort) != 'asc' && strtolower($sort) != 'desc') $sort = 'asc';
        	$result = $result->orderBy($orderBy,$sort);
        }
        $result = $result->skip($offset)->take($perPage);
		$response = ['recordsFiltered' => $totalFiltered, 'maxPage' => $maxPage, 'data' => $result->get()];
		return $response;
	}
    

}