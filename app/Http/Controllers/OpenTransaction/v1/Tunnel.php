<?php

namespace Service\Http\Controllers\OpenTransaction\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Tunnel extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
		//$this->enforce_product  = "OpenTransaction";
	}

	public function create_hellobill_token(){

		print_r($_SERVER);
		exit();
		$this->render(true);
	}


}