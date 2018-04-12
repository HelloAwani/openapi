<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\Item as ItemInterface;
use Service\Models\Item as ItemDB;
use DB;

class Item implements ItemInterface {

	public function upsert($data, $id = null){
		if(!empty($id)){
			// update data
			try{
				ItemDB::where('ItemID', $id)->update($data);
				return $this->find($id);
			}catch(\Exception $e){
				return ['error'=>true];
			}
		}else{
			// insert data
			try{
				return ItemDB::insert($data);
			}catch(\Exception $e){
				return ['error'=>true];
			}
		}
	}

	public function find($id){
		try{
			return ItemDB::findOrFail($id);
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

	public function where($column, $value, $mode = 'all'){
		try{
			if($mode == 'first') return ItemDB::where($column, $value)->first();
			else return ItemDB::where($column, $value)->get();
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

	public function get($perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param){
		$offset = $start;
		$result = ItemDB::select('Item.*','Category.CategoryName','Category.CategoryCode','InventoryUnitTypeAbbv')
            ->join('Category','Category.CategoryID','=','Item.CategoryID')
            ->join('InventoryUnitType','InventoryUnitType.InventoryUnitTypeID','=','Item.InventoryUnitTypeID')
            ->where('Item.BranchID', $param->BranchID)->where('Item.BrandID', $param->MainID);
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword){
        		 $query->orWhere(DB::raw('lower(trim("CategoryName"::varchar))'),'like','%'.strtolower($keyword).'%')
        		 			->orWhere(DB::raw('lower(trim("ItemName"::varchar))'),'like','%'.strtolower($keyword).'%')
        		 			->orWhere(DB::raw('lower(trim("ItemCode"::varchar))'),'like','%'.strtolower($keyword).'%')
        		 			->orWhere(DB::raw('lower(trim("CategoryCode"::varchar))'),'like','%'.strtolower($keyword).'%');
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

	public function totalRecords($param){
		$result = ItemDB::where('Item.BranchID', $param->BranchID)->where('Item.BrandID', $param->MainID)
            ->join('Category','Category.CategoryID','=','Item.CategoryID')->count();
		return $result;
	}

	public function checkBranchCode($code, $branchID, $brandID, $id = null){
		try{
			$result = ItemDB::where('ItemCode',$code)->where('BranchID',$branchId)->where('BrandID',$brandID)->where('ItemID', '<>', $id)->count();
			return $result;
		}catch(\Exception $e){
			return 0;
		}
	}

	public function delete($id){
		try{
			return ItemDB::destroy($id);
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

	public function all($sortBy, $orderType = 'asc'){
		try{
			return ItemDB::orderBy($sortBy, $orderType)->get();
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

}