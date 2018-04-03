<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\UserType as UserTypeInterface;
use Service\Models\UserType as UserTypeDB;
use DB;

class UserType implements UserTypeInterface {

	public function upsert($data, $id = null){
		if(!empty($id)){
			// update data
			try{
				UserTypeDB::where('id', $id)->update($data);
				return $this->find($id);
			}catch(\Exception $e){
				return ['error'=>true, 'message'=>$e->getMessage()];
			}
		}else{
			// insert data
			try{
				return UserTypeDB::insert($data);
			}catch(\Exception $e){
				return ['error'=>true, 'message'=>$e->getMessage()];
			}
		}
	}

	public function find($id){
		try{
			return UserTypeDB::with('Permissions.Permission')->findOrFail($id);
		}catch(\Exception $e){
			return ['error'=>true, 'message'=>$e->getMessage()];
		}
	}

	public function get($perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null){
		$offset = $start;
		$result = UserTypeDB::query();
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword){
        		 $query->where(DB::raw('lower(trim("id"::varchar))'),'like',"'%".$keyword."%'")
        		 			->orWhere(DB::raw('lower(trim("UserTypeName"::varchar))'),'like','%'.$keyword.'%')
        		 			->orWhere(DB::raw('lower(trim("UserTypeCode"::varchar))'),'like','%'.$keyword.'%');
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

	public function where($column, $value, $mode = 'all'){
		try{
			if($mode == 'first') return UserTypeDB::where($column, $value)->first();
			else return UserTypeDB::where($column, $value)->get();
		}catch(\Exception $e){
			return ['error'=>true, 'message'=>$e->getMessage()];
		}
	}

	public function totalRecords(){
		$result = UserTypeDB::count();
		return $result;
	}

	public function checkBranchCode($code, $branchId){
		try{
			$result = UserTypeDB::where('UserTypeCode',$code)->where('BranchID',$branchId)->count();
			return $result;
		}catch(\Exception $e){
			return 0;
		}
	}

	public function delete($id){
		try{
			return UserTypeDB::destroy($id);
		}catch(\Exception $e){
			return ['error'=>true, 'message'=>$e->getMessage()];
		}
	}

}