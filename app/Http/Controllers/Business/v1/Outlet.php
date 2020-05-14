<?php

namespace Service\Http\Controllers\Business\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Outlet extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
	}
	
	public function detail(){
		$this->validate_request();

		$etoken  = $this->_token_detail;
		
		$this->db  = $etoken->ProductID;
		switch ($etoken->ProductID) {
			case 'RES':

				$branch = $this->query('SELECT 
					m."RestaurantID" "BrandID", b."BranchID", m."RestaurantName" "BrandName", "BranchName", "Address",  "Contact",  "Email" from  "Branch"  b
					join "RestaurantMaster" m  on m."RestaurantID" = b."RestaurantID"
					WHERE
					"BranchID"  = :id
				
				', ["id"=>$etoken->BranchID])[0];

				break;
			
			case 'RET':

				$branch = $this->query('SELECT m."StoreID" "BrandID", b."BranchID", m."StoreName" "BrandName", "BranchName", "Address",  "Contact",  "Email" from  "Branch"  b
					join "StoreMaster" m  on m."StoreID" = b."StoreID"
					where 
					"BranchID"  = :id
				
				', ["id"=>$etoken->BranchID])[0];

				break;
			
			default:
				# code...
				break;
		}
		$branch->ProductID  = $etoken->ProductID;
		$this->response->BranchDetail =  $branch;
		$this->reset_db();
		$this->render(true);
	}

	 
}