<?php

namespace Service\Http\Controllers\HQFNB\v1;

use Illuminate\Http\Request;
use Validator;
use Service\Http\Services\v1\FNBDimension;

class Transaction extends \Service\Http\Controllers\_Heart
{
	public function __construct(Request $request = null)
	{
		$this->use_db_trans = false;
		// $this->db = 'ser';
		if ($request != null) {
			parent::__construct($request);
		}
		$this->api_version =  "v1";
		$this->enforce_product  = "HQF";
	}

	public function test()
	{
		return 'test';
	}

	public function fetch()
	{
		$this->validate_request();
		$this->db  = 'res';

		$rules = [
			'DateStart' => 'required|date_format:Y-m-d',
			'DateEnd' => 'required|date_format:Y-m-d',
		];

		//validation
		$validator = Validator::make($this->request, $rules);
		if ($validator->fails()) {
			$error = $validator->errors();	
			return response()->json($error);
		}

		$fnbDimension = new FNBDimension();
		$tokenData = $this->_token_detail;
		$request = $this->request;

		// set default response
		$this->response->Data = [];

		$dateRange = [
			'start' => $request['DateStart'],
			'end' => $request['DateEnd']
		];
		
		// get dimension
		$result = $fnbDimension->getSalesDimension($tokenData, $dateRange);

		if(!empty($result)){
			$responseData = $result;
		}
		else $responseData = [];
		$this->response->Data = $responseData;

		return response()->json($this->response);
		// $this->reset_db();
		// $this->render(true);
	}

}
