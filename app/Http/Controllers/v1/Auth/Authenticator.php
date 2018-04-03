<?php

namespace Service\Http\Controllers\v1\Auth;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Authenticator extends \Service\Http\Controllers\v1\_Base
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
        }
	}
	
	public function generate_access_token(){

    	$rules = [
			'APIKey' => 'required',
		];
		$messages = [
			'APIKey.required' => 'API Key is Required',
		];
		$this->validator = Validator::make($this->request, $rules, $messages);
		$this->render();
		$key_tkn = $this->query('SELECT * FROM "AuthKey" where "Key" = :key and "DisabledAt" is null ',[
			"key"=>$this->request["APIKey"]
		]);

		if(@$key_tkn[0]==null){
			$this->custom_errors[] = $this->error("Key is not valid");
		}else{
			$t = bin2hex(openssl_random_pseudo_bytes(12));
			$t2 = bin2hex(openssl_random_pseudo_bytes(36));
			$t3 = bin2hex(openssl_random_pseudo_bytes(24));
			$array = array(
				"ProductCode"=>$key_tkn[0]->KeyTypeID,
				"CreatedDate"=>$this->now(),
				"LastAccessed"=>$this->now(),
				"KeyReference"=>$key_tkn[0]->Key,
				"AuthenticatorToken" => $t.time().$t2.md5(time()).$t3,
				"MainID" => @$key_tkn[0]->MainID,
				"BranchID" =>@$key_tkn[0]->BranchID,
				"IPAddress" => @$this->get_client_ip(),
				);
			$this->upsert("Authenticator", $array);
			$this->response->Token = $array["AuthenticatorToken"];

		}

		$this->render(true);
	}

	public function destroy(){
		

		$rules = [
			'Token' => 'required'
		];
        $this->validator = Validator::make($this->request, $rules);
		$this->render();

		$tid = $this->query('SELECT "AuthenticatorID" from "Authenticator" where "DisabledDate" is null 
			and "AuthenticatorToken" = :token
		', [ 
			"token" => $this->request["Token"]
		]);

		if(@$tid[0]!=null){
			$array = array(
				"DisabledDate"=>$this->now(),
				"DisablingMethod"=>"auth.destroy",
				);
			$this->upsert("Authenticator", $array, $tid[0]->AuthenticatorID);
		}


		$this->render(true);

	}

}

