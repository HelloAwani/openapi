<?php

namespace Service\Http\Controllers\HQFNB\v1;

use Illuminate\Http\Request;
use Service\Http\Services\v1\FNBSalesDimension;
use Service\Http\Services\v1\FNBIngredientDimension;

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
		$this->db  = 'RES';

		$rules = [
			'DateStart' => 'required|date_format:Y-m-d',
			'DateEnd' => 'required|date_format:Y-m-d',
		];
		//standar validator
		$this->validator($rules);

		$this->render();

		$fnbDimension = new FNBSalesDimension();
		$tokenData = $this->_token_detail;
		$request = $this->request;

		// set default response
		$this->response->Data = [];

		$dateRange = [
			'start' => $request['DateStart'],
			'end' => $request['DateEnd']
		];
		// get dimension
		$result = $fnbDimension->getDimension($tokenData, $dateRange);

		if(!empty($result)){
			$responseData = $result;
		}
		else $responseData = [];
		$this->response->Data = $responseData;

		$this->reset_db();
		$this->render(true);
	}

	public function fetchSales()
	{
		$this->validate_request();
		$this->db  = 'RES';

		$rules = [
			'DateStart' => 'required|date_format:Y-m-d',
			'DateEnd' => 'required|date_format:Y-m-d',
		];
		//standar validator
		$this->validator($rules);

		$this->render();

		$fnbSalesDimension = new FNBSalesDimension();
		$tokenData = $this->_token_detail;
		$request = $this->request;

		// set default response
		$this->response->Data = [];

		$dateRange = [
			'start' => $request['DateStart'],
			'end' => $request['DateEnd']
		];
		// get dimension
		$result = $fnbSalesDimension->getDimension($tokenData, $dateRange);

		if(!empty($result)){
			$responseData = $result;
		}
		else $responseData = [];

		$this->response->Data = $responseData;

		return response()->json($responseData);
	}

	/**
	 * fetch sales with void status
	 *
	 * @return array
	 */
	public function fetchVoidSales()
	{
		$this->validate_request();
		$this->db  = 'RES';

		$rules = [
			'DateStart' => 'required|date_format:Y-m-d',
			'DateEnd' => 'required|date_format:Y-m-d',
		];
		//standar validator
		$this->validator($rules);

		$this->render();

		$fnbVoidSalesDimension = new FNBSalesDimension(true);
		$tokenData = $this->_token_detail;
		$request = $this->request;

		// set default response
		$this->response->Data = [];

		$dateRange = [
			'start' => $request['DateStart'],
			'end' => $request['DateEnd']
		];
		// get dimension
		$result = $fnbVoidSalesDimension->getDimension($tokenData, $dateRange);

		if(!empty($result)){
			$responseData = $result;
		}
		else $responseData = [];

		$this->response->Data = $responseData;

		return response()->json($responseData);
	}

	/**
	 * fetch ingredient dimension
	 *
	 * @return array
	 */
	public function fetchIngredient()
	{
		$this->validate_request();
		$this->db = 'RES';

		$rules = [
			'DateStart' => 'required|date_format:Y-m-d',
			'DateEnd' => 'required|date_format:Y-m-d',
		];
		//standar validator
		$this->validator($rules);

		$this->render();

		$ingredientDimension = new FNBIngredientDimension();
		$tokenData = $this->_token_detail;
		$request = $this->request;

		// set default response
		$this->response->Data = [];

		$dateRange = [
			'start' => $request['DateStart'],
			'end' => $request['DateEnd']
		];
		// get dimension
		$result = $ingredientDimension->getDimension($tokenData, $dateRange);

		if(!empty($result)){
			$responseData = $result;
		}
		else $responseData = [];
		$this->response->Data = $responseData;

		return response()->json($responseData);
	}
}
