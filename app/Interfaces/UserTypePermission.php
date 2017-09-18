<?php 
namespace Service\Interfaces;
 
interface UserTypePermission {
	// update or insert
	public function upsert($data, $id = null);
	// check permission of user is exist
	public function permissionExist($usertypeId, $permission);
	// clear user type permission using user type id
	public function clearPermissions($userTypeId);

}