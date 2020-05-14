<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\ServiceUsage as ServiceUsageInterface;
use DB;

class ServiceUsage implements ServiceUsageInterface {
    
	public function getDataTable($display, $column, $perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param){
		$offset = $start;
		$result = DB::table('ServiceUsage')->join('Item', 'ServiceUsage.ItemID', '=', 'Item.ItemID')->join('SubService', 'ServiceUsage.SubServiceID', '=', 'SubService.SubServiceID')
            ->select($display)->where('ServiceUsage.BranchID', $param->BranchID)->where('ServiceUsage.BrandID', $param->MainID)->where('ServiceUsage.SubServiceID', $subID);
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword){
                for($i = 0; $i < count($column);$i++){
                    if($i = 0)
                        $query->where(DB::raw('lower(trim("'.$column[$i].'"::varchar))'),'like',"'%".$keyword."%'");
                    else
        		 	    $query->orWhere(DB::raw('lower(trim("'.$column[$i].'"::varchar))'),'like','%'.$keyword.'%');
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