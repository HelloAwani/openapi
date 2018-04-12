<?php

namespace Service\Http\Controllers\Device\v4;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Device extends _Base
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
        }
    }
	
    public function save_printer(){

        $this->validate_request();


        $rules = [
                    'PrinterName' => 'required',
                    'PrinterTypeID' => 'required',
                ];

        $this->validator = Validator::make($this->request, $rules);
		$this->render();


		$arr = array(
				"PrinterTypeID"=>$this->request["PrinterTypeID"],
				"BrandID"=>$this->request["BrandID"],
				"BranchID"=>$this->request["BranchID"],
				"IPAddress"=>$this->request["IPAddress"],
				"PrinterName"=>$this->request["PrinterName"],
				"Notes"=>@$this->request["Notes"],
			);

		$id = $this->upsert("Printer", $arr, @$this->request["PrinterID"]);

		$this->response->Printer = @$this->query('SELECT "IPAddress", "PrinterName", "PrinterTypeID", "PrinterID" from "Printer" where "PrinterID" = :id ', array("id"=>$id))[0];

		$this->render(true);
		
    }

    public function delete_printer(){

        $this->validate_request();


        $rules = [
                    'PrinterID' => 'required',
                ];

        $this->validator = Validator::make($this->request, $rules);
		$this->render();

		$arr = array(
				"Archived"=>$this->now(),
			);

		$id = $this->upsert("Printer", $arr, @$this->request["PrinterID"]);

		$this->render(true);
		
    }

}