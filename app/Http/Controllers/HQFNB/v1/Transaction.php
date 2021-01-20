<?php

namespace Service\Http\Controllers\HQFNB\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use DB;

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

		$tokenData = $this->_token_detail;
		$request= $this->request;
		$responseData = [];

		// get brand branch
		$branchId = $this->getBranchId($tokenData->MainID);

		// get all branch dimension within date range
		$dateRange = '';
		$this->getBranchDimension($branchId, $dateRange);

		$this->response->Transactions = $responseData;

		$this->reset_db();
		$this->render(true);
	}

	/**
	 * get brand branch
	 *
	 * @param [type] $brandId
	 * @return void
	 */
	private function getBranchId($brandId, $limit = 0){
		$query = DB::connection('res')
		->table('Branch')
		->select('BranchID')
		->where('RestaurantID', '=', $brandId);

		if($limit > 0){
			$query->limit($limit);
		}

		$dbResult = $query->get();

		$result = [];
		if($dbResult->isNotEmpty()){
			foreach ($dbResult as $value) {
				$result[] = $value->BranchID;
			}
		}

		return $result;
	}

	private function getBranchDimension($branchId, $dateRange){
		
	}
}
