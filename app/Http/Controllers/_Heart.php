<?php
//used as a core and helper of every device controller, excluding business logics
//controller oop "style"
namespace Service\Http\Controllers;

use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Service\Http\Requests;
use Validator;
class _Heart 
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

    //s3 ui for "service" product
    protected $image_uri = 'https://s3-ap-southeast-1.amazonaws.com';

    //true  = use language  string  for validation  and other 
    protected $use_language_string = false;

    //if use  language string,it  will use this language
    protected $language_string = "ID";

    //timezone
    protected $gmt_mod_hour = 7;
    protected $gmt_mod_minute = 0;

    //to hold token data
    protected $_token_detail = null;
    
    //extend log bag
    protected $_extended_log = [];

    //to enforce   product type and token
    protected $enforce_product = null;

    //db
    protected $db  =  "";

    public function __construct(Request $request = null){
        $this->environment = \App::environment();
        $this->reset_db();
        //set the transaction
        $this->start();
        $this->_extended_log = [];
        $this->_token_detail = new \stdClass();

        $this->Request = $request;
        $this->ip_address = $request->ip();
        $this->request_url = $request->fullUrl();
        //reset
        $this->request = $request == null ? null : $request->json()->all();
        $this->validator = null;
        $this->response = new \stdClass();
        $this->status = 0;
        $this->override_validation_code = false;
        $this->custom_errors = array();
        $this->exit_when_have_error = true;
        //load the constant 

        $this->format_date["full"]  =  'Y-M-d H:i:s'; 
    }
    function generate_token(){  
        $t = bin2hex(openssl_random_pseudo_bytes(4));
        $t2 = bin2hex(openssl_random_pseudo_bytes(12));
        $t3 = bin2hex(openssl_random_pseudo_bytes(36));
        return $t.''.time().'.'.$t2.'_'.md5(time()).$t3;
    }   
    
    function get_glossaries($glossarry_id_list,  $lang_id  = 0){
        $lang_id =  $lang_id === 0  ? $this->language_string :  $lang_id;
        $ids = $glossarry_id_list;
        $clean_id = ["'_'"];
        foreach($ids as $d){
            $clean_id[] = "'$d'";
        }

        $data  =  [];
        $raw  = [];
        foreach($data as $d){
            $raw[$d->identifier] = $d->value;
        }
        
        return $raw;
    }


    function validation_lang_name($rules, $lang_id=0){
        $variables = [];
        foreach($rules as $k=>$v){
            $variables[]=$k;
        }
        $glossarries = $this->get_glossaries($variables,  $lang_id);
        $this->validator->setAttributeNames($glossarries); 
    }

    function form_object_to($from,$to, $keys, $is_std=false){
        $keys =  explode(',', $keys);
        $from =  json_decode(json_encode($from), true);
        $to =  json_decode(json_encode($to), true);
        $lkey = [];
        foreach($keys as $k){
            $k=preg_replace('/\s*/m','', $k);
            $lkey[]=$k;
        }
        $keys=$lkey;
        foreach($from as  $k=>$v){
            if(in_array($k, $keys)){
                $to[$k]=$v;
            }
        }
        if($is_std){
            $to  = json_decode(json_encode ($to), FALSE);
        }
        return $to;
    }

    function log($extend_data =  []){
        return;
        $DBTLog  = @\DB::connection('log_activity');
        //log time  must be utc
        $this->set_timezone(0);
        $object= [
            "ip_addrress" => @$_SERVER['REMOTE_ADDR'],
            "date"  => $this->utc()->complete_time,
            "user_agent"=>@$_SERVER['HTTP_USER_AGENT'],
            "api_version"=>@$this->api_version,
            "host"=>@$_SERVER['HTTP_HOST'],
            "route" => \Route::getCurrentRoute()->getActionName(),
            "_request"=>$this->request,
            
        ];
        $table_name  = "_unmanaged";
        foreach($extend_data as $k=>&$v){
            switch ($k) {
                case '_user':
                    $table_name = strtolower(trim($v));
                    break;
                default:
                    # code...
                    break;
            }
            $object[$k]=$v;
        }
        $log = $DBTLog->table($table_name)->insert([$object]);

    }

    function slog($object,$table_name){
        return;
       $DBTLog  = @\DB::connection('log_activity');
       $log = $DBTLog->table($table_name)->insert([$object]);
    }

    function add_error($attribute, $value,  $message_id){
        $glossary[]=$message_id;
        $message  = $message_id;
        $message  = str_replace(':value', $value, $message);
        $message  = str_replace(':attribute', $attribute, $message);
        $ce["field"]  = $attribute;
        $ce["message"] = $message;
        $this->custom_errors[]=$ce;
    }
    function render($done=false){
        //check for validation
        
        if($this->validator!=null){

             //check the additional custom error
            foreach ($this->custom_errors as $ce) {
               $this->validator->errors()->add($ce["field"], $ce["message"]);
            }
            if(count($this->validator->errors()->all())>0){
                $this->response->Status = $this->override_validation_code == false ? 1 : $this->status;
                $this->response->Errors = array_values((array) $this->validator->errors())[0];

                //if failed, always exit, except when specified
                if($this->exit_when_have_error){
                    $this->response_json();
                }
            }
        }
        $this->response->Status = $this->override_validation_code == false ? 0 : $this->status;
        $this->response->Errors = $this->coalesce(@$this->response->Errors, []);
        $this->response->Errors = count(@$this->response->Errors) == 0  ? array() : $this->response->Errors;
        //commit changes when all validations are finished
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
                if($value!=null){
                    $array[$key] = $value."";
                }
            }
        }
    }

    function response_json(){
        //return service as json, and exit
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Expose-Headers: Access-Control-Allow-Origin");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header("Content-Type: application/json");

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
        //always return glossaries
        if($this->count_array(@$this->request["_glossaries"])>0){

        }else{

        }
        
        $js = json_decode(json_encode($this->response),true);
        $this->rec($js);
        $js["Status"] = (int)$js["Status"];
        //log the response
        $this->_extended_log["_response"]  = $js;
        $this->log(
            $this->_extended_log
        );

        if(@$this->_token_detail->ResponseID!=null){
            $logobject = [
                "ResponseID" => $this->_token_detail->ResponseID,
                "Date"=>$this->now()->full_time,
                "Response"=>$js
            ];

            $this->slog($logobject, "LogResponse");
        }

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
                'egnore_errors' => true

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
        $image_str = $image_str == null ? $ifnull : $this->image_uri.$image_str;
    }

    public function is_empty($val){
        return 
            is_string(@$val) ? strlen(trim($val))==0 || $val===null :  $val===null;
    }

    public function coalesce($val, $ret){
        if($this->is_empty(@$val)||@$val==null){
            return $ret;
        }else{
            return is_string(@$val) ? trim($val) : $val;
        }
    }

    public function count_array($arr){
        return is_array($arr) ? count($arr) : 0;
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
        $identifier = $this->get_pk($table);

        $e = 0;
        if(isset($id)){

            if(trim($id)!=""){
                $e = \DB::connection($this->db)->select( \DB::raw('
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

    function get_pk($object){
        
        $pk = \DB::select( \DB::raw('
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
        $this->db = $this->db == "HQF" ? "RES" : $this->db;
        $res = \DB::connection($this->db)->select( \DB::raw($clause), $params);
        return @$res;
    }

    function reset_db(){
        $this->db = env("DEF_DB_CONNECTION");
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
    function now($format = ""){


        $data =  $this->query('
            select
            "ServerTime" as "complete_time",
            to_char("ServerTime", \'yyyy-MM-dd hh24:mi:ss\') "full_time",
            to_char("ServerTime", \'yyyy-MM-dd\') "date",
            to_char("ServerTime", \'HH:mm:ss\') "time"
            from  (
            Select timezone(\'UTC\', now())+ interval\' '.$this->gmt_mod_hour.'h '.$this->gmt_mod_minute.'m\' "ServerTime"
            )a 
        ',
            array(
            )
        )[0];
        $data->unix = strtotime ($data->complete_time);
        $data->formatted  = date($format, $data->unix);
        return $data;
    }


    function add_interval($date, $interval){
        $newtimestamp = strtotime($date.' '.$interval);
        $date = date('Y-m-d H:i:s', $newtimestamp);

        return $date;
    }



    function get_branch($main_id, $array = null){

        $data = $this->query('SELECT "BranchID", "BranchName", "Address" from "Branch" where "RestaurantID" = :MainID
            and "Active" = \'1\'
        ',
            [
                "MainID"=> $main_id
            ]
        );
        $rdata = [];
        if($array){
            foreach ($data as $d) {
                $d->{$array} = [];
                $rdata["br".$d->BranchID] = $d;
            }
            return $rdata;
        }

        return $data;
    }

    function get_branch_id(){

        $data = $this->get_branch($this->_token_detail->MainID);

        $col = $this->extract_column($data, "BranchID", [0]);

        return $col;
    }

    public function branchs(){

        $this->validate_request();
        $this->db  = $this->_token_detail->ProductID;
        
        $this->response->Branchs = $this->get_branch($this->_token_detail->MainID);

        $this->reset_db();
        $this->render(true);

    }

    function join($data, $object){

        $branch = $this->get_branch($this->_token_detail->MainID, $object);
        foreach ($data as $b) {
            $dt = @$b->BranchID;
            if(@$branch["br".$dt]!=null){
                unset($b->BranchID);
                $branch["br".$dt]->{$object}[]=$b;
            }
        }

        return array_values($branch);


    }

    //using database
    function utc($format = ""){


        $data =  $this->query('
            select
            "ServerTime" as "complete_time",
            to_char("ServerTime", \'yyyy-MM-dd hh24:mi:ss\') "full_time",
            to_char("ServerTime", \'yyyy-MM-dd\') "date",
            to_char("ServerTime", \'HH:mm:ss\') "time"
            from  (
            Select timezone(\'UTC\', now()) "ServerTime"
            )a 
        ',
            array(
            )
        )[0];
        $data->unix = strtotime ($data->complete_time);
        $data->formatted  = date($format, $data->unix);
        return $data;
    }

    function set_timezone($zone_id){
        $this->gmt_mod_hour = '07';
        $this->gmt_mod_minute = '00';
    }

    function validation_lang_string($lang_id=0){
        
        if($lang_id===0){
            $lang_id = $this->language_string;
        }

        $validator_list = [];
        $validation_mapping =  [];
        foreach($validator_list as $v){
            $validation_mapping[] = $v->identifier;
        }
        $glossarries = $this->get_glossaries($validation_mapping,  $lang_id);
        $languages["basic"] = [];
        $languages["conditional2seg"] = [];
        foreach($glossarries as  $k => $v){
            $key = explode('.', $k);
            $rule=$key[0];
            unset($key[0]);
            switch (count($key)) {
                case 1  :
                    $languages["basic"][implode('.', $key)] = $v;
                    break;
                case 2  :
                    $rule = [];
                    $rule["rule"] =   $key[1];
                    $rule["data_type"] =   $key[2];
                    $rule["value"] =  $v;
                    $languages["conditional2seg"][]=$rule;
            }
        }
        return $languages;
    }
    
    public function validator($rules,$must_validate_token=false){
        if(env('APP_DEBUG')){
            $this->response->_drequest = $this->request;
        }
        if(@$this->request["_language_id"]!=null){
            $this->language_string = $this->request["_language_id"];
        }

        if($must_validate_token){
            $this->request["token"]  = $this->coalesce(@$this->request["token"],  '');
            $token  = @$this->query('SELECT t.*, a."email", a."timezone" FROM "token"  t
                join "account"  a on a."account_id" = t."account_id"
                
                where 
                "token" =  :token
                and "disabled_date"  is  null
                and "app_code"  = :app_code
            ', [
                "token" =>$this->request["token"],
                "app_code"=> $this->app_code
            ])[0];
           if(@$token==null){
                $this->response->Status  = 1;
                $this->response->Errors[] =  $this->get_glossaries(["error.unauthorized"])["error.unauthorized"];
                $this->response_json();
           }else{
                $updated["last_accessed"]  =  $this->utc()->complete_time;

                $this->_token_detail = $this->form_object_to($token, $this->_token_detail,
                    "account_id,main_id,business_id,
                    app_code,meta,
                    email, timezone
                ");
                $this->_extended_log["_token_detail"]=$this->_token_detail;
                $this->_extended_log["_user"]  =  strtolower(trim($token->email));
                $this->upsert("token", $updated,$token->token_id);
           }
            
        }
        
        $vs  =  $this->validation_lang_string($this->coalesce((@$this->request["_language_id"]), 0));
        $cm = $vs["basic"];
        $applicable_rules =  [];
        foreach($rules as $k=> $v){
            $irule_  = explode('|', $v);
            $irule = [];
            foreach($irule_  as $i){
                $irule[]=explode(':',$i)[0];
            }
            $applicable_rules[$k]["rules"]=$irule;
        }
        foreach($applicable_rules as $k  => $v){
            $var  = @$this->request[$k];
            if(is_array($var)){
               $dtype =  'array';
            }else if(is_string($var)){
                $dtype =  'string';
            }else if($var==null){
                $dtype =  'undetermined';
            }
            else{
               $dtype =  'numeric';
            }
            $applicable_rules[$k]["data_type"]  = $dtype;
            foreach($vs["conditional2seg"] as $c){
                
                foreach($v["rules"] as  $r){
                    if($r==$c["rule"]&&$c["data_type"]==$dtype){
                        $cm[$k.'.'.$r] = $c["value"];
                    }
                }
            }
        }
        $this->validator = Validator::make($this->request,$rules, $cm);
        $this->validation_lang_name($rules, $this->coalesce((@$this->request["_language_id"]), 0));
        
    }

    //validate  token   request
    public function validate_request(){

        $header = trim(@getallheaders()["Authorization"]);

        $this->validator([]);

        if($header==null){  
            $this->add_error("Authorization", $header, "Authorization has ecountered a failure (x1078)");   
        }
        $this->render();

        $header = explode(' ', $header);

        
        if(@$header[0]!='Bearer'){  
            $this->add_error("Authorization", $header[0], "Authorization has ecountered a failure (x1056)");    
        }
        
        if(@$header[1]==null){  
            $this->add_error("Authorization", $header[0], "Authorization has ecountered a failure (x1028)");    
        }
        $this->render();

        $etoken = @$this->query('SELECT * FROM "Token" where "Token" = :token and "DisabledDate" is null 
            and :date <= "ExpiryDate"
        ',
            array(
                "token"=>$header[1],
                "date"=>$this->now()->full_time
            )
        )[0];
        

        if(@$etoken->TokenID==null){
            $this->add_error("Authorization", "Token", "Authorization has ecountered a failure (x1155)");   
        }

        $this->render();
        //check  branch is active

        $mapping = $this->query('SELECT * FROM "OutletAPIMapping" where "OutletAPIMappingID" = :id ', ["id"=>$etoken->OutletAPIMappingID])[0];
        $meta_mapping = json_decode(@$mapping->Meta);
        $this->MappingMeta = $meta_mapping;

        $apikey = $this->query('SELECT * FROM "APICredential" where "APICredentialID" = :id ', ["id"=>$etoken->APICredentialID])[0];
        $this->ACMeta = json_decode($apikey->Meta);

        $this->db = $etoken->ProductID;

        if(@$meta_mapping->SubProduct!=null){
            $this->db = $meta_mapping->SubProduct;
        }

        $branch = @$this->query('SELECT *  FROM  "Branch"  where "BranchID" = :id ',
        ["id"=>$etoken->BranchID])[0];
        $this->reset_db();
        $this->outlet_info = $branch;
        if((@$branch->Active)!='1'  
            && !in_array($this->enforce_product, ["HQF"])
            ){
            $this->add_error("Authorization", "Token", "Authorization has ecountered a failure (x5001)");   
        }

        $this->render();

        if($this->enforce_product!=null){
            if($this->enforce_product!=$etoken->ProductID){
                $this->add_error("Authorization", "Token", "Authorization has ecountered a failure (x5002)");   
                $this->render();
            }
        }



        $token["LastAccessed"] =  $this->now()->full_time;
        
        $this->upsert("Token", $token, $etoken->TokenID);
        
        $etoken->RequestID  =  uniqid ('req|',true);
        $etoken->ResponseID  =  uniqid ('res|',true);
        $this->_token_detail = $etoken;

        $history = [ 
            "Date" =>  $this->now()->full_time,
            "TokenID" =>  $etoken->TokenID,
            "Method" => \Route::getCurrentRoute()->getActionName(),
            "RequestID" => $etoken->RequestID,
            "ResponseID" =>  $etoken->ResponseID
        ];

        $this->upsert("TokenAccessHistory", $history);
        $logobject = [
            "RequestID" => $etoken->RequestID,
            "Date"=>$this->now()->full_time,
            "Request"=>$this->request
        ];

        $this->slog($logobject, "LogRequest");


    }

    public  function  extract_column($dataset, $colname, $return_if_empty=null){
        $data = [];
        foreach($dataset as $d){
            $data[]= \pg_escape_string($d->{$colname});
        }
        if($return_if_empty!=null&&count($data)==0){
            return $return_if_empty;
        }else{
            return  $data;
        }
    }

    public function map_record(&$dataset,$name, $key, $dataarray,$child=null){

        if($child==null){
            foreach($dataset  as $k=>$v){
                $v->{$name} = [];
                $dataset[$v->{$key}] = $v;            
                unset($dataset[$k]);
            }
            foreach($dataarray as $k=>&$v){
                $id = $v->{$key};
                unset($v->{$key});
                $dataset[$id]->{$name}  []= $v;
            }
        }else{
            $segments = explode('.', $child);
            $meta = new \stdClass();
            $meta->name = $name;
            $meta->key = $key;
            $rec  =  $dataarray;
            foreach($segments  as $s){
                $this->magic($segments,  $dataset,$rec, count($segments), $meta);
            }
        }
        $dataset = array_values($dataset);
        return array_values($dataset);
    }
    public function magic($segments, &$data,&$dataarray, $iteration, $meta){
        $segment = $segments[count($segments)-$iteration];
        $iteration--;
        foreach($data as  $d){
            foreach($d->{$segment} as $s){
                if($iteration>0){
                    $this->magic($segments, $d->{$segment}, $dataarray, $iteration, $meta);
                }else{
                    if(!is_array(@$s->{$meta->name})){
                        $s->{$meta->name}  = [];
                    }else{
                        continue;
                    }
                    foreach($dataarray  as $dk=>&$dv){
                        if($dv->{$meta->key}==$s->{$meta->key}){
                            unset($dv->{$meta->key});
                            $s->{$meta->name}[]=$dv;
                            unset($dataarray[$dk]);
                        }
                    }
                }
            }
        }
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
        if($date==null||$date==""){
            $date = null;
            return;
        }
        return date($format, strtotime($modifier, strtotime($date)));
    }


    public function set_timezone_date(&$date,  $format, $timezone  =  null){
        if($date==null||$date==""){
            $date = null;
            return;
        }
        if($timezone==null){
            $timezone = $this->_token_detail["timezone"];
        }

        $time =  $this->query('SELECT  *  FROM "timezone" where timezone_id =  :id ',
            ["id"=>$timezone]
        )[0];
        $date  =  $this->format_date($date, $format, $time->gmt_modifier_hour." hours");
        $date  =  $this->format_date($date, $format, $time->gmt_modifier_minute." minutes");
    }
}