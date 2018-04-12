<?php

namespace Service\Http\Controllers\Device\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Auth extends _Base
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
        }
    }
	
	public function attach(){

    	$rules = [
                    'Token' => 'required',
                    'DeviceTypeID' => 'required',
					'DeviceName'=> 'required',
                    'DeviceString' => 'required',
					'BranchID'=> 'required'
                ];

        $this->validator = Validator::make($this->request, $rules);
		$this->render();
		$etoken = $this->query('SELECT * FROM "Token" where "Token" = :token and "DisabledDate" is null ',
			array(
				"token"=>$this->request["Token"]
			)
		);

		$c_sync = new Sync;

		if(count($etoken)==0){
			$this->custom_errors[] = $this->error("Token Not Found");
		}else{
			$hasaccess = false;


			$this->response->Brands = @$c_sync->get_token_outlets($etoken[0]->AccountID);

			foreach($this->response->Brands as $o){
				foreach ($o->Outlets as $ot) {
						if($ot->BranchID==$this->request["BranchID"]){
						$hasaccess = true;break;
					}
				}
			}
			if(!$hasaccess){
				$this->custom_errors[] = $this->error("Invalid outlet access");
			}
		}

		$this->render();

		$this->response->Token = str_random(72).'.'.md5(microtime(true)).str_random(36);
		

		$branch = $this->query('select * from "Branch" where "BranchID" = :bid ',
			array(
				"bid"=>$this->request["BranchID"]
			)
		)[0];
		$token = [
			"AccountID" => $etoken[0]->AccountID,
			"Identifier" => $etoken[0]->Identifier,
			"DeviceTypeID" => $this->request["DeviceTypeID"],
			"DeviceName" => $this->request["DeviceName"],
			"DeviceString" => $this->request["DeviceString"],
			"BranchID" => $this->request["BranchID"],
			"BrandID" => $branch->BrandID,
			"IPAddress" => $this->ip_address,
			"Date" => $this->now(),
			"Token"=>$this->response->Token,
			"TokenRefID"=>$etoken[0]->TokenID
		];

		$this->upsert("Token", $token);

		$utoken = [
			"DisabledDate" => $this->now(),
			"DisablingMethod" => $this->request_url,
		];

		$this->upsert("Token", $utoken, $etoken[0]->TokenID);


		$this->render(true);

	}

	public function login(){

    	$rules = [
                    'Username' => 'required|min:3',
                    'Password' => 'required|min:6',
					'Identifier'=> 'required'
                ];

        $this->validator = Validator::make($this->request, $rules);
		$this->render();
		$this->request["ProductCode"] = PRODUCT_CODE;
        $login = $this->webservice(AUTH_URL.'account-v2/account/login_device', array(
        		"data"=>json_encode($this->request)
        	));

        if($login->Status==0){
        	$this->exit_when_have_error = false;
        	//just an example
        	$this->custom_errors[] = $this->error($login->Message);
			$this->response->Token = str_random(36).'.'.md5(microtime(true)).str_random(72);
        	$this->response->User = $login->User;
        	$this->response->Brands = $this->coalesce(@$login->Products->Outlets, array());

			$token = [
				"AccountID" => $this->response->User->AccountID,
				"Identifier" => $this->request["Identifier"],
				"IPAddress" => $this->ip_address,
				"Date" => $this->now(),
				"Token"=>$this->response->Token
			];

			$this->Upsert("Token", $token);

        }else{
        	$this->custom_errors[] = $this->error($login->Message);
        	foreach ($login->Errors as $e) {
        		$this->custom_errors[] = $this->error($e->ID.' '.$e->Msg, $e->ID);
        	}
        }

		$this->render(true);
	}   

	public function logout(){

		$this->validate_request();

		$utoken = [
			"DisabledDate" => $this->now(),
			"DisablingMethod" => $this->request_url,
		];

		$this->upsert("Token", $utoken, $this->request["TokenID"]);

		$this->render(true);

	}
	 
}