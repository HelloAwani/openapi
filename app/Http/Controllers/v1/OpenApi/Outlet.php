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


	function check_valid_outlet($request,$db){

		$this->db = $db;
		$br = $this->query('
			SELECT 
			b.*
			from "Branch" b 
			where b."Active" = \'1\'
			and "BranchID" = :id
		', ["id"=>$request["BranchID"]]);

		if(@$br[0]==null){
			$this->custom_errors[] = $this->error("Branch Not Found");
			$this->render();
		}else{
			return $br[0];
		}

	}
}

