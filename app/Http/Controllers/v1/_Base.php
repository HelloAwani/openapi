<?php
//used as a core and helper of every device controller, excluding business logics
//controller oop "style"
namespace Service\Http\Controllers\v1;

use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Service\Http\Requests;
use Validator;
class _Base 
{   
    //request from json, use array for attributes
    protected $request = null;  

    //the current validator object
    protected $validator = null;

    //response object
    protected $response = null;

    //response code status, 1 = error, 0 = everything good
    protected $status = 0;

    //set false if you want override the code, have to set the status manually
    protected $override_validation_code = false;

    //custom error container
    protected $custom_errors = array();

    //when rendering error, exit if true
    protected $exit_when_have_error = true;

    //indicates whether using db transactions or not
    protected $use_db_trans = true;

    //var for Request container from Requestclass
    protected $Request = null;

    //var for IP Address
    protected $ip_address = null;

    //container for webservice response header
    protected $webservice_response_header = array();

    //container for webservice request parameter
    protected $webservice_request_parameter = array();

    //container for webservice context
    protected $webservice_context = array();

    //get current request url
    protected $request_url = array();

    //get current running query clause
    protected $last_running_query_clause = "";

    //get current running query parameter
    protected $last_running_query_param = array();

    //db parameter, not reset, you have to careful brah
    protected $db = null;

    //default db
    protected $db_default = 'main';

    //response status
    protected $http_response_code = null;

    //product id, set on controller
    protected $product_id = null;

    function test_print($item, $key)
    {
        echo "$key holds $item\n";
    }
    public function __construct(Request $request = null){
        $this->environment = \App::environment();

        //set the transaction
        $this->start();

        $this->Request = $request;

        $this->ip_address = $request->ip();
        $this->request_url = $request->fullUrl();
        $this->request = $request == null ? null : $request->json()->all();
        //sanitize
        array_walk_recursive(
            $this->request,
            function (&$value) {
                $value = pg_escape_string($value);
            }
        );

        //reset
        $this->validator = null;
        $this->response = new \stdClass();
        $this->status = 0;
        $this->override_validation_code = false;
        $this->custom_errors = array();
        $this->exit_when_have_error = true;
        $this->http_response_code = null;
        //load the constant 
        define('AUTH_URL', $_ENV["AUTH_URL"]);

    }




    function render($done=false){
        //check for validation
        if($this->validator!=null){


             //check the additional custom error
            foreach ($this->custom_errors as $ce) {
               $this->validator->errors()->add($ce["field"], $ce["message"]);
            }
            if(count($this->validator->errors()->all())>0){
                if($this->response == null){
                    $this->response = new \stdClass();
                }
                $this->response->Status = $this->override_validation_code == false ? 1 : $this->status;
                $this->response->Errors = $this->validator->errors()->all();
                //if failed, always exit, except when specified
                if($this->exit_when_have_error){
                    $this->http_response_code = 400;
                    $this->response_json();
                }
            }
        }

        if($this->response == null){
            $this->response = new \stdClass();
        }
        $this->response->Status = $this->override_validation_code == false ? 0 : $this->status;
        $this->response->Errors = count(@$this->response->Errors) == 0  ? array() : $this->response->Errors;
        //only called when all validations are finished
        if($done){
            $this->end();
            $this->response_json();
        }
    }

    function error($message, $field = 'msg'){
        $arr["field"] = $field;
        $arr["message"] = $message;
        return $arr;
    }
    function set_recursive_string(&$array) {
        foreach ($array as $key => &$value) {
            $array[$key] = (string) $value;
            if (is_array($value)) {
                set_recursive_string($value);
            }
        }
    }

    function rec(&$array){
        foreach ($array as $key=>$value) {
            if(is_array($array[$key])){
                $this->rec($array[$key]);
            }else{
                //heh
                $array[$key] = $value."";
            }
        }
    }

    function response_json(){
        //return service as json, and exit
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Expose-Headers: Access-Control-Allow-Origin");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header("Content-Type: application/json");
        if($this->http_response_code!=null){
            header("Content-Type: application/json", true, 400);
        }

        //rearrange for aesthetic
        $json = array();
        $json["Status"] = $this->response->Status;
        $json["Errors"] = $this->response->Errors;


        foreach ($this->response as $rk=>$rv){
            if(
                !in_array($rk, 
                        array('Status', 'Errors')
                    )
            ){
                $json[$rk]=$rv;
            }
        } 
        
        $js = json_decode(json_encode($this->response),true);
        $this->rec($js);
        $js["Status"] = (int)$js["Status"];
        echo json_encode($js);
        exit();
    }

    function webservice($url, $array=array()){

        $method = @$array["method"] == null ? "POST" : $array["method"];
        $data = @$array["data"] == null ? "" : $array["data"];
        $timeout = @$array["timeout"] == null ? 30000 : $array["timeout"];

        $this->webservice_request_parameter = $array;

        $opts = array('http' =>
            array(
                'method'  => $method,
                'header'  => 'Content-type: application/json',
                'content' => $data,
                'timeout' => $timeout,
                'ignore_errors' => true

            )
        );
        
        $this->webservice_context = $opts;

        $context  = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        $this->webservice_response_header = $http_response_header;
        $result = @json_decode($result,false);

        return $result;
    }

    public function set_image(&$image_str, $ifnull = null){
        $bmap["ret"] = 'https://s3-ap-southeast-1.amazonaws.com/hellobill-retail/';
        $image_str = $image_str == null ? $ifnull : $bmap[$this->db].$image_str;
    }

    public function is_empty($val){
        return 
            is_string(@$val) ? strlen(trim($val))==0 || $val===null :  $val===null;
    }
    public function std_to_json(&$array, $array_column){
		foreach ($array_column as $d) {
			foreach ($array as &$dd) {
				$dd->{$d} = json_decode($dd->{$d});
			}
		}
	}

    public function coalesce($val, $ret){
        if($this->is_empty(@$val)||@$val==null){
            return $ret;
        }else{
            return is_string(@$val) ? trim($val) : $val;
        }
    }

    public function insert_query($table, $array){
        $collist = "(";
            $questionlist = "(";
                $ct=0;
                $vallist = array();

        foreach ($array as $key => $value){
            if($ct!=(count((array)$array)-1)){
                $collist= $collist." \"".$key."\",";
                $questionlist= $questionlist." :$key,";

            }else{
                $collist= $collist." \"".$key."\")";
                $questionlist= $questionlist." :$key)";

        }
        $vallist[$key] = $value;
        $ct++;
        }
        $query = "insert into \"".($table)."\" ".$collist." values ".$questionlist;
        $this->query($query, $vallist);
        $newid = $this->query('SELECT lastval() id')[0]->id;
        return $newid;
    }

    public function update_query($table, $array, $identifier, $id){
        $collist = "(";
		$questionlist = "(";
			$ct=0;
			$vallist = array();
			$query = "Update \"".($table)."\" set ";

			foreach ($array as $key => $value){

				if($ct!=(count((array)$array)-1)){
					$query = $query." \"".$key."\" = :$key, ";
				}else{
					$query = $query." \"".$key."\" = :$key WHERE \"".$identifier."\" = :$identifier ";
				}
				$vallist[$key] = $value;
				$ct++;
			}

			$vallist[$identifier] = $id;
            $this->query($query,$vallist);
		}



	public function upsert($table, &$array, $id=null){
        $identifier = $this->getPK($table);

        $e = 0;
        if(isset($id)){

            if(trim($id)!=""){
                $e = \DB::connection($this->coalesce($this->db, $this->db_default))->select( \DB::connection($this->coalesce($this->db, $this->db_default))->raw('
                                SELECT COUNT(*) ct FROM "'.$table.'" where "'.$identifier.'" = :'.$identifier), array(
                            $identifier => $id,
                        ))[0]->ct;

            }else{
                $e=0;
            }
        }
        if($e==0||$e==null){
            unset($array->{$identifier});
            return $this->insert_query($table, $array);
        }else{
            unset($array->LocalID);
            unset($array["LocalID"]);
            $this->update_query($table, $array, $identifier, $id);
            return $id;
        }
	}




    function getPK($object){
        
        $pk = \DB::connection($this->coalesce($this->db, $this->db_default))->select( \DB::connection($this->coalesce($this->db, $this->db_default))->raw('
            select kc.column_name as "PK"
								from  
								    information_schema.table_constraints tc,  
								    information_schema.key_column_usage kc  
								where 
								    tc.constraint_type = \'PRIMARY KEY\' 
								    and kc.table_name = tc.table_name and kc.table_schema = tc.table_schema
								    and kc.constraint_name = tc.constraint_name
								    and tc.table_name = :object 
								    and kc.constraint_schema = \'public\'
        
        '), array(
            'object' => $object,
        ));
        return @$pk[0]->PK;
	}


    function query($clause, $params = array()){
        $this->last_running_query_clause = $clause;
        $this->last_running_query_param = $params;
        $res = \DB::connection($this->coalesce($this->db, $this->db_default))->select( \DB::connection($this->coalesce($this->db, $this->db_default))->raw($clause), $params);
        return @$res;
    }

    function start(){
        if($this->use_db_trans){
		    $this->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
		    $this->query('BEGIN;');
        }
    }
    function end(){
        if($this->use_db_trans){
		    $this->query('END;');
        }
    }

    //using database
    function now($timezone = 'Asia/Jakarta'){
        return $this->query('Select timezone(:timezone, now()) "ServerTime"',
            array(
                "timezone" => $timezone
            )
        )[0]->ServerTime;
    }


	public function validate_request(){
		//must be authorized for token
		$rules = [
			'Token' => 'required'
		];
        $this->validator = Validator::make($this->request, $rules);
		$this->render();

        $etoken = $this->query('SELECT * FROM "Authenticator" where "AuthenticatorToken" = :token and "DisabledDate" is null 
            and "ProductCode" = :product
        ',
			array(
				"token"=>$this->request["Token"],
				"product"=>$this->product_id
			)
		);

        if(count($etoken)==0){
			$this->custom_errors[] = $this->error("Token not found or not valid");
		}

        $this->render();

        $this->request["AccountID"] = $etoken[0]->AccountID;
        $this->request["BranchID"] = $etoken[0]->BranchID;
        $this->request["BrandID"] = $etoken[0]->MainID;
        $this->request["AuthenticatorID"] = $etoken[0]->AuthenticatorID;

	}

    public function group_record(
        $array, $key, $dataset_query, $dataset_parameter, $result_as, $turn_into_object = true
    ){
        if(is_array($key)){
            $alias = $key["Alias"];
            $key = $key["Key"];
            
            $ckey = '"'.$alias.'"."'.$key.'"';
        }else{
            $ckey = '"'.$key.'"';
        }
        $array = $array == null ? array() : json_decode(json_encode($array), true);
        $keys = array();
        $objects = array();

        foreach ($array as $a) {

            if($a[$key]==null||$a[$key]==''){
                $a[$key] = -1;//empty case
            }

            $keys[] = $a[$key];
            $objects[$a[$key]] = $a;
        }
    
        foreach ($objects as &$o) {
            $o[$result_as] = array();
        }

        if(count($keys)>0){
            //pasti ada and laahhh~
            $dataset_query = \str_replace('@key', 
                ' and '.$ckey.' in ('.implode($keys, ', ').') ',
                $dataset_query
            );

            $dataset = $this->query($dataset_query, $dataset_parameter);
            $dataset = json_decode(json_encode($dataset), true);

            foreach ($dataset as $d) {
                if(@$objects[@$d[$key]]!=null){
                    $k = @$d[$key];
                    unset($d[$key]);
                    $objects[$k][$result_as][]=$d;
                }
            }
        }
        $objects = array_values($objects);
        if($turn_into_object){
            return json_decode(json_encode($objects), false);
        }else{
            return json_decode(json_encode($objects), true);
        }
    }

    public function general_setting($code){
        return $this->query('SELECT 
			"gt"."GeneralSettingTypeID" as "ID", 
			"gt"."GeneralSettingTypeName" as "TypeName", 
			"gt"."AcceptedDataType" as "DataType",
			COALESCE(COALESCE("GeneralSetting"."GeneralSettingValue", gt."DefaultValue"),\'\') as 
			"Value", "Options"
			FROM 
			"GeneralSetting"
			RIGHT JOIN 
			"GeneralSettingType" gt on "GeneralSetting"."GeneralSettingTypeID" = gt."GeneralSettingTypeID"
            and 
			"GeneralSetting"."BranchID" = :branchid and 
			"GeneralSetting"."BrandID" = :brandid
			WHERE  
			gt."GeneralSettingTypeID" = :id
            ', [
                "id"=>$code,
                "branchid"=>$this->request["BranchID"],
                "brandid"=>$this->request["BrandID"],
            ])[0];
    }
    public function format_date($date, $format, $modifier = "0 seconds"){
        return 
            date($format, strtotime($modifier, strtotime($date)));
    }

    
	function get_client_ip() {
    	$ipaddress = '';
	    if (getenv('HTTP_CLIENT_IP'))
	        $ipaddress = getenv('HTTP_CLIENT_IP');
	    else if(getenv('HTTP_X_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	    else if(getenv('HTTP_X_FORWARDED'))
	        $ipaddress = getenv('HTTP_X_FORWARDED');
	    else if(getenv('HTTP_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_FORWARDED_FOR');
	    else if(getenv('HTTP_FORWARDED'))
	       $ipaddress = getenv('HTTP_FORWARDED');
	    else if(getenv('REMOTE_ADDR'))
	        $ipaddress = getenv('REMOTE_ADDR');
	    else
	        $ipaddress = 'UNKNOWN';
	    return $ipaddress;
	}


}
