<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\SubService as SubServiceInterface;
use DB;

class SubService implements SubServiceInterface {
    
	public function getDataTable($display, $column, $perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param){
		$offset = $start;
		$result = DB::table('SubService')->join('DurationUnit', 'SubService.DurationUnitID', '=', 'DurationUnit.DurationUnitID')->join('Service', 'SubService.ServiceID', '=', 'Service.ServiceID')->select($display)->where('SubService.BranchID', $param->BranchID)->where('SubService.BrandID', $param->MainID)->where('SubService.Archived', null);
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword){
                for($i = 0; $i < count($column);$i++){
                    if($i = 0)
                        $query->where(DB::raw('lower(trim("'.$column[$i].'"::varchar))'),'like',"'%".strtolower($keyword)."%'");
                    else
        		 	    $query->orWhere(DB::raw('lower(trim("'.$column[$i].'"::varchar))'),'like','%'.strtolower($keyword).'%');
                }
        	});	
        }
        $totalFiltered = $result->count();
        $maxPage =  $perPage==null ? 1 : ceil($totalFiltered/$perPage);
        if(!empty($orderBy)){
        	if(strtolower($sort) != 'asc' && strtolower($sort) != 'desc') $sort = 'asc';
        	$result = $result->orderBy($orderBy,$sort);
        }
        $result = $result->skip($offset);
        $result = $perPage==null ? $result : $result->take($perPage);
		$response = ['recordsFiltered' => $totalFiltered, 'maxPage' => $maxPage, 'data' => $result->get()];
		return $response;
	}
    

}