<?php

namespace Service\Http\Controllers\Auth\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Token extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
	}
	
	public function generate_access_token(){
		
		$header = trim(@getallheaders()["Authorization"]);

		$this->validator([]);

		if($header==null){	
			$this->add_error("Authorization", $header, "Authorization has ecountered a failure (x0078)");	
		}
		$this->render();

		$header = explode(' ', $header);

		
		if(@$header[0]!='Basic'){	
			$this->add_error("Authorization", $header[0], "Authorization has ecountered a failure (x0056)");	
		}
		
		if(@$header[1]==null){	
			$this->add_error("Authorization", $header[0], "Authorization has ecountered a failure (x0028)");	
		}
		$this->render();

		$rules = [
			'MappingNumber' => 'required',		
			'MappingPassword' => 'required'
		];
		//standar validator
		$this->validator($rules);
		
		$this->render();

		$hash  = $header[1];

		$outlet  = @$this->query('SELECT *, ac."APIKey", ac."APISecret", ac."Meta" from "OutletAPIMapping" oa
			join "APICredential" ac on ac."APICredentialID" =  oa."APICredentialID"
			where 
			oa."DisabledDate" is  null
			and ac."DisabledDate" is  null

			and "MappingNumber"  = :number
			and "MappingPassword"  = :password
		',
			[
				"number"=>$this->request["MappingNumber"],
				"password"=>$this->request["MappingPassword"],
			] 
		)[0];

		if($outlet==null){
			$this->add_error("Authorization", "Outlet", "Authorization has ecountered a failure (x0186)");	
		}
		$this->render();


		if(!\Hash::check($outlet->APIKey.':'.$outlet->APISecret,$hash)){
			$this->add_error("Authorization", "Outlet", "Authorization has ecountered a failure (x0158)");	
		}
		$this->render();
		$this->ACMeta = json_decode($outlet->Meta);
		$token =  [
			"APICredentialID"=>$outlet->APICredentialID,
			"OutletAPIMappingID"=>$outlet->OutletAPIMappingID,
			"ProductID"=>$outlet->ProductID,
			"MainID"=>$outlet->MainID,
			"BranchID"=>$outlet->BranchID,
			"CreatedDate"=>$this->now()->full_time,
			"ExpiryDate"=>$this->add_interval($this->now()->full_time,'48 hours'),
			"Token"=>$this->generate_token(),
			"CreatedUserAgent"=> $_SERVER["REMOTE_ADDR"],
			"CreateIPAddress"=> $_SERVER["HTTP_USER_AGENT"],

		];

		$this->upsert("Token",  $token);

		$this->response->Token = $token["Token"];
		$this->response->ExpiryDate = $token["ExpiryDate"];
		$this->response->ExpireIn = strtotime($token["ExpiryDate"])  - strtotime($this->now()->full_time);

		$this->render(true);
	}
	

	 
}