<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\UserTypePermission as UserTypePermissionInterface;
use Service\Models\UserTypePermission as UserTypePermissionDB;
use DB;

class UserTypePermission implements UserTypePermissionInterface {

	public function upsert($data, $id = null){
		if(!empty($id)){
			// update data
			try{
				UserTypePermissionDB::where('id', $id)->update($data);
				return $this->find($id);
			}catch(\Exception $e){
				return ['error'=>true];
			}
		}else{
			// insert data
			try{
				return UserTypePermissionDB::create($data);
			}catch(\Exception $e){
				return ['error'=>true];
			}
		}
	}

	public function permissionExist($usertypeId, $permission){
		try{
			$result = UserTypePermissionDB::where('UserTypeID',$usertypeId)->where('PermissionID',$permission)->count();
			return $result;
		}catch(\Exception $e){
			return 0;
		}
	}

	public function clearPermissions($userTypeId){
		return UserTypePermissionDB::where('UserTypeID', $userTypeId)->delete();
	}

}