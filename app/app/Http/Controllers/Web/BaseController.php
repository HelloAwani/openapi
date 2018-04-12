<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Models\Authenticator as AuthenticatorDB;
use Service\Interfaces\Permission;
use DB;
use Storage;
use Config;

class BaseController extends Controller
{
	public function __construct(Permission $permission){
        $this->permission = $permission;
	}

    public function checkCon(){
        DB::table('Branch')->limit(1)->get();
        return 0;
    }
    
	// generating error message in array from validation
	function checkErrors($rules,$errors){
		$result = [];
		foreach($rules as $key => $val){
			if(count($errors->get($key)) > 0){
				foreach($errors->get($key) as $err){
					$temp = ["ID" => $key, "Message" => $err];
					array_push($result, $temp);
				}
			}
		} 
		return $result;
	}

    public function give_hour(&$param){
        $param['DateFrom'] = $param['DateFrom'].' 00:00:00';
        $param['DateTo'] = $param['DateTo'].' 23:59:59';
    }
    
    public function coalesce($val, $ret){
		if($this->is_empty($val)||$val==null){
			return $ret;
		}else{
			return trim($val);
		}
	}

    
    public function upload_to_s3($input){
    	$folder = @$input['Folder'];
    	$brandID = @$input['BrandID'];
    	$branchID = @$input['BranchID'];
    	$objectID = @$input['ObjectID'];
    	$data = @$input['Data'];
    	$filename = @$input['Filename'];
        $destinationPath = strtolower($folder).'/'.$brandID.'/'.$branchID.'/'.$objectID.'/'.time().'.'.$filename;
    	Storage::disk('s3')->put($destinationPath, base64_decode($data));
    	$response = $this->generateResponse(0, [], "Success", ["url" => $this->s3path.$destinationPath]);
        return response()->json($response);
    }
    
    public function get_env($key){
        return $_ENV[$key];
    }
    
    public function db_trans_start(){
        $res = \DB::select( \DB::raw('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;'));
        $res = \DB::select( \DB::raw('BEGIN;'));
        return @$res;
	}

    public function db_trans_end(){
        $res = \DB::select( \DB::raw('END;'));
        return @$res;
	}                      
    
    public function get_date_now($timezone = 'Asia/Jakarta'){
        $res = collect(\DB::select('Select timezone(:timezone, now()) "ServerTime"',
            array(
                "timezone" => $timezone
            )
        ))->first()->ServerTime;
        return $res;
    }
    
	// generating response array
    function generateResponse($status, $errors, $message, $additional = null){
    	$result = ['Status'=>$status, 'Errors'=>$errors, 'Message'=>$message];
    	if(!empty($additional) && is_array($additional)) $result = array_merge($result, $additional);
    	return $result;
    }
    public function is_empty($val){
		return strlen(trim($val))==0 || $val===null;
	}
    public function checkToken($request){
        $input = array();
        $input = json_decode($request->getContent(),true);
        $rules = [
                    'Token' => 'required'
                ];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            print_r(json_encode($response));exit();
        }
        $response = $this->checkTokenDB(@$input['Token']);
        return $response;
        
    }
    
	public function checkTokenDB($token){
        try{
            $response = AuthenticatorDB::select(['AuthenticatorID', 'ProductCode', 'MainID', 'BranchID', 'AccountID'])
                ->where('AuthenticatorToken', $token)
                ->where('DisabledDate', null)
                ->first();
            $errorList = array();
            $now = collect(\DB::select("Select timezone('Asia/Jakarta', now()) \"ServerTime\""))->first()->ServerTime;
            $data = array(
                'LastAccessed' => $now
            );
            if(isset($response->AuthenticatorID)){
				AuthenticatorDB::where('AuthenticatorID', $response->AuthenticatorID)->update($data);
                return $response;
            } else {
                $errorList[] = array(
                    'ID' => 'Token',
                    'Message' => 'No Token Valid Found'
                );
                $error = $this->generateResponse(1, $errorList, "Please check input", null);
                print_r(json_encode($error));exit();
            }
        }catch(\Exception $e){
            return ['error'=>true, 'message'=>$e->getMessage()];
        }
		return $response;
	}
    
    function getPermissions(){
    	$result = $this->permission->all();
    	$response = $this->generateResponse(0, [], "Success", ['Permissions' => $result]);
        return response()->json($response);
    }
    
    function getMeta($param, $array, $table, $first = false){
        
        if($first == true)
            $result =  collect(\DB::select('Select * from '.$table.' where "BranchID" = \''.$param->BranchID.'\' AND "BrandID" = \''.$param->MainID.'\''))->first();
        else $result = DB::select('Select * from '.$table.' where "BranchID" = \''.$param->BranchID.'\' AND "BrandID" = \''.$param->MainID.'\'');
        return $result;
    }
    
    function getLastVal(){
        return DB::select('select lastval() AS id')[0]->id;
    }
    
    function checkExists($table, $column, $value){
        $result = DB::table($table)->where($column, $value)->count();
        return $result;
    }
}
