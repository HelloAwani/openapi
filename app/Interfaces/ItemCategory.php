<?php 
namespace Service\Interfaces;
 
interface ItemCategory {

	// update or insert
	public function upsert($data, $id = null);
	// get data by id
	public function find($id);
	// get first record and where column is value
	public function where($column, $value, $mode = 'all');
	// datatable
	public function get($perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param);
	// total records
	public function totalRecords($param);
	// check branch and code existed
	public function checkBranchCode($code, $branchId, $brandID, $id = null);
	// destroy
	public function delete($id);
	//  get all data
	public function all($sortBy, $orderType = 'asc');
}