<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\Staff as StaffInterface;
use Service\Models\Staff as StaffDB;
use DB;

class Staff implements StaffInterface {

	public function upsert($data, $id = null){
		if(!empty($id)){
			// update data
			try{
				StaffDB::where('UserID', $id)->update($data);
				return $this->find($id);
			}catch(\Exception $e){
				return ['error'=>true];
			}
		}else{
			// insert data
			try{
				return StaffDB::insert($data);
			}catch(\Exception $e){
				return ['error'=>true];
			}
		}
	}

	public function find($id){
		try{
			return StaffDB::findOrFail($id);
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

	public function where($column, $value, $mode = 'all'){
		try{
			if($mode == 'first') return StaffDB::where($column, $value)->first();
			else return StaffDB::where($column, $value)->get();
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

	public function get($perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param){
		$offset = $start;
		$result = StaffDB::select('User.UserID as UserID','ShiftName','UserTypeName','UserCode','Fullname','User.Email')->join('Shift','User.ShiftID','=','Shift.ShiftID')->join('UserType','User.UserTypeID','=','UserType.UserTypeID')->where('User.BranchID', $param->BranchID)->where('User.BrandID', $param->MainID)->where('User.Archived', null);
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword){
        		 $query->where(DB::raw('lower(trim("ShiftName"::varchar))'),'like','%'.strtolower($keyword).'%')
        		 			->orWhere(DB::raw('lower(trim("UserTypeName"::varchar))'),'like','%'.strtolower($keyword).'%')
        		 			->orWhere(DB::raw('lower(trim("UserCode"::varchar))'),'like','%'.strtolower($keyword).'%')
        		 			->orWhere(DB::raw('lower(trim("Email"::varchar))'),'like','%'.strtolower($keyword).'%')
        		 			->orWhere(DB::raw('lower(trim("Fullname"::varchar))'),'like','%'.strtolower($keyword).'%');
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
		$result = StaffDB::where('BranchID', $param->BranchID)->where('BrandID', $param->MainID)->count();
		return $result;
	}

	public function checkBranchCode($code, $branchId){
		try{
			$result = StaffDB::where('UserCode',$code)->where('BranchID',$branchId)->count();
			return $result;
		}catch(\Exception $e){
			return 0;
		}
	}

	public function checkBranchPIN($code, $branchId){
		try{
			$result = StaffDB::where('PIN',$code)->where('BranchID',$branchId)->count();
			return $result;
		}catch(\Exception $e){
			return 0;
		}
	}
    
	public function checkUnique($column, $code, $branchId){
		try{
			$result = StaffDB::where($column,$code)->where('BranchID',$branchId)->count();
			return $result;
		}catch(\Exception $e){
			return 0;
		}
	}

	public function delete($id){
		try{
			return StaffDB::destroy($id);
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}

}