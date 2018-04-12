<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\Shift as ShiftInterface;
use Service\Models\Shift as ShiftDB;
use DB;

class Shift implements ShiftInterface {

	public function upsert($data, $id = null){
		if(!empty($id)){
			// update data
			try{
				ShiftDB::where('ShiftID', $id)->update($data);
				return $this->find($id);
			}catch(\Exception $e){
				return ['error'=>true];
			}
		}else{
			// insert data
			try{
				return ShiftDB::insert($data);
			}catch(\Exception $e){
				return ['error'=>true];
			}
		}
	}

	public function find($id){
		try{
			return ShiftDB::findOrFail($id);
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

	public function get($perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param){
		$offset = $start;
		$result = ShiftDB::query()->select(['ShiftCode','ShiftName','From','To','ShiftID'])->where('BranchID', $param->BranchID)->where('BrandID', $param->MainID);
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword){
        		 $query->where(DB::raw('lower(trim("ShiftName"::varchar))'),'like',"'%".strtolower($keyword)."%'")
        		 			->orWhere(DB::raw('lower(trim("From"::varchar))'),'like','%'.strtolower($keyword).'%')
        		 			->orWhere(DB::raw('lower(trim("To"::varchar))'),'like','%'.strtolower($keyword).'%');
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

	public function totalRecords($param){
		$result = ShiftDB::where('BranchID', $param->BranchID)->where('BrandID', $param->MainID)->count();
		return $result;
	}

	public function delete($id){
		try{
			return ShiftDB::destroy($id);
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

}