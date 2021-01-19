<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\ItemCategory as ItemCategoryInterface;
use Service\Models\ItemCategory as ItemCategoryDB;
use DB;
 
class ItemCategory implements ItemCategoryInterface {
 
	public function upsert($data, $id = null){
		if(!empty($id)){
			// update data
			try{
				ItemCategoryDB::where('CategoryID', $id)->update($data);
				return $this->find($id);
			}catch(\Exception $e){
				return ['error'=>true];
			}
		}else{
			// insert data
			try{
				return ItemCategoryDB::insert($data);
			}catch(\Exception $e){
				return ['error'=>true];
			}
		}
	}

	public function find($id){
		try{
			return ItemCategoryDB::findOrFail($id);
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

	public function where($column, $value, $mode = 'all'){
		try{
			if($mode == 'first') return ItemCategoryDB::where($column, $value)->first();
			else return ItemCategoryDB::where($column, $value)->get();
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

	public function get($perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param){
		$offset = $start;
		$result = ItemCategoryDB::query()->select(['CategoryCode','CategoryName','Image','CategoryID'])->where('BranchID', $param->BranchID)->where('BrandID', $param->MainID);
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword){
        		 $query->orWhere(DB::raw('lower(trim("CategoryName"::varchar))'),'like','%'.strtolower($keyword).'%')
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
		$result = ItemCategoryDB::where('BranchID', $param->BranchID)->where('BrandID', $param->MainID)->count();
		return $result;
	}

	public function checkBranchCode($code, $branchID, $brandID, $id = null){
		try{
			$result = ItemCategoryDB::where('CategoryCode',$code)->where('BranchID',$branchID)->where('BrandID',$brandID)->where('CategoryID', '<>', $id)->count();
			return $result;
		}catch(\Exception $e){
			return 0;
		}
	}

	public function delete($id){
		try{
			return ItemCategoryDB::destroy($id);
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

	public function all($sortBy, $orderType = 'asc'){
		try{
			return ItemCategoryDB::orderBy($sortBy, $orderType)->get();
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

}