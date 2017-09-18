<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\Discount as DiscountInterface;
use DB;

class Discount implements DiscountInterface {
    
	public function getDataTable($display, $column, $perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param){
		$offset = $start;
		$result = DB::table('Discount')->leftJoin('PaymentMethod', 'PaymentMethod.PaymentMethodID', '=', 'Discount.PaymentMethodID')->select($display)->where('Discount.BranchID', $param->BranchID)->where('Discount.BrandID', $param->MainID)->where('Discount.Archived', null);
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