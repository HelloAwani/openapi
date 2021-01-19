<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\Permission as PermissionInterface;
use Service\Models\Permission as PermissionDB;
use Service\Models\Authenticator as AuthenticatorDB;
use DB;

class Permission implements PermissionInterface {
	
	public function all(){
		return PermissionDB::all();
	}

	public function where($column, $value, $mode = 'all'){
		try{
			if($mode == 'first') return PermissionDB::where($column, $value)->first();
			else return PermissionDB::where($column, $value)->get();
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}
    
	public function checkTokenDB($token){
        try{
            $response = AuthenticatorDB::select(['AuthenticatorID', 'ProductCode', 'MainID', 'BranchID', 'AccountID'])
                ->where('AuthenticatorToken', $token)
                ->where('DisabledDate', null)
                ->first();
            $errorList = array();
            if(isset($response->AuthenticatorID)){
                return $response;
            } else {
                $errorList[] = array(
                    'ID' => 'Token',
                    'Message' => 'No Token Valid Found'
                );
                $error = $this->generateResponse(1, $errorList, "Please check input", null);
                print_r($error);exit();
            }
        }catch(\Exception $e){
            return ['error'=>true, 'message'=>$e->getMessage()];
        }
		return $response;
	}
    
	// generating response array
    function generateResponse($status, $errors, $message, $additional = null){
    	$result = ['Status'=>$status, 'Errors'=>$errors, 'Message'=>$message];
    	if(!empty($additional) && is_array($additional)) $result = array_merge($result, $additional);
    	return $result;
    }
    
}