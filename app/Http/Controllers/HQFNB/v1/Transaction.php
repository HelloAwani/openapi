<?php

namespace Service\Http\Controllers\HQFNB\v1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use DB;
use Service\Http\Services\v1\DimensionService;

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
		//standar validator
		$this->validator($rules);

		$this->render();

		$dimensionSvc = new DimensionService();
		$tokenData = $this->_token_detail;
		$request = $this->request;

		// set default response
		$this->response->Data = [];

		$dateRange = [
			'start' => $request['DateStart'],
			'end' => $request['DateEnd']
		];
		// get dimension
		$result = $dimensionSvc->getDimension($tokenData->MainID, $dateRange);

		if(!empty($result)){
			$responseData = 'content';
		}
		else $responseData = [];
		$this->response->Data = $responseData;

		$this->reset_db();
		$this->render(true);
	}

}
