<?php

namespace Service\Http\Controllers\v1\OpenAPI;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Outlet extends \Service\Http\Controllers\v1\_Base
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->product_id = "OpenAPI";
	}

	public function detail(){
		print_r("expression");
		$this->validate_request();



		$this->render(true);
	}
	
}

