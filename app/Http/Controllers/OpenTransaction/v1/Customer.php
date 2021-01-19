<?php

namespace Service\Http\Controllers\OpenTransaction\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Customer extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
		$this->enforce_product  = "OpenTransaction";
	}
	
	public function create_customer(){
		

		$this->validate_request();


		$rules = [
			'ReferenceID' => 'required',		
			'HandlerName' => 'required',		
			'FullName' => 'required',			
		];
		//standar validator
		$this->validator($rules);
		$this->render();
		$this->db  = $this->_token_detail->ProductID;

		$cust = [
			"FullName" => $this->request["FullName"],
			"DefaultAddress" => $this->request["DefaultAddress"],
			"FullName" => $this->request["FullName"],
			"DefaultPhone" => $this->request["DefaultPhone"],
			"DefaultEmail" => $this->request["DefaultEmail"],
			"CreatedDate" => $this->now()->full_time,
			"ExtensionID" => $this->ACMeta->ExtName,			
		];
		$handler = [
			"HandlerName" => $this->request["HandlerName"],
			"ReferenceID" => $this->request["ReferenceID"],
			"ExtensionID" => $this->ACMeta->ExtName,			
			"CreatedDate" => $this->now()->full_time
		];
		
		$cust_id = $this->upsert("ExtCustomer", $cust);

		$handler["ExtCustomerID"] = $cust_id;
		$handler_id = $this->upsert("Handler", $handler);

		$this->response->ExtCustomerID = $cust_id;
		$this->response->HandlerID = $handler_id;

		$this->render(true);
	}


	public function fetch_all(){


		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;

		$dt = $this->query('SELECT "ExtCustomerID", "FullName", "DefaultAddress", "DefaultPhone", "DefaultEmail", "CreatedDate" FROM "ExtCustomer" where "ExtensionID" = :eid 
			and  "DeletedDate" is null 

			order by "CreatedDate" desc 
			',
			["eid"=>$this->ACMeta->ExtName]
		);

		$cs = $this->extract_column($dt, "ExtCustomerID", [0]);
		$handlers = $this->query('SELECT "ExtCustomerID","HandlerID", "HandlerName", "ReferenceID" from "Handler" where "ExtCustomerID" in ('.implode(',', $cs).') ');
		
		$this->map_record($dt,"Handlers", "ExtCustomerID", $handlers);


		$this->response->Customers = $dt;

		$this->render(true);
	}
	public function fetch(){


		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;

		$filter = [
			 "eid"=> $this->ACMeta->ExtName,
			 "fname" => "%{$this->request["FullName"]}%",
			 "address" => "%{$this->request["Address"]}%",
			 "phone" => "%{$this->request["Phone"]}%",
			 "email" => "%{$this->request["Email"]}%",
			 "handler" => "%{$this->request["HandlerName"]}%",
			 "ref" => "%{$this->request["ReferenceID"]}%",
			];
		$dt = $this->query('SELECT ec."ExtCustomerID" FROM "ExtCustomer" ec 
			join "Handler" h on h."ExtCustomerID" = ec."ExtCustomerID"
			and ec."ExtensionID" = :eid 
			and ec."DeletedDate" is null 
			and h."DeletedDate" is null
			and 
			(
				lower(trim("FullName")) like lower(trim( :fname ))
				and 
				lower(trim("DefaultAddress")) like lower(trim( :address ))
				and 
				lower(trim("DefaultPhone")) like lower(trim( :phone ))
				and 
				lower(trim("DefaultEmail")) like lower(trim( :email ))
				and 
				lower(trim("HandlerName")) like lower(trim( :handler ))
				and 
				lower(trim("ReferenceID")) like lower(trim( :ref ))
			)
			limit 10 
			',
			$filter
		);

		$cs = $this->extract_column($dt, "ExtCustomerID", [0]);

		$dt = $this->query('SELECT "ExtCustomerID", "FullName", "DefaultAddress", "DefaultPhone", "DefaultEmail", "CreatedDate" FROM "ExtCustomer" where "ExtensionID" = :eid 
			and "ExtCustomerID" in ('.implode(',', $cs).') 
			
			order by "CreatedDate" desc 
			',
			["eid"=>$this->ACMeta->ExtName]
		);
		$handlers = $this->query('SELECT "ExtCustomerID","HandlerID", "HandlerName", "ReferenceID" from "Handler" where "ExtCustomerID" in ('.implode(',', $cs).') ');
		
		$this->map_record($dt,"Handlers", "ExtCustomerID", $handlers);


		$this->response->Customers = $dt;

		$this->render(true);
	}


}