<?php 
namespace Service\Interfaces;
 
interface Item {
	// update or insert
	public function upsert($data, $id = null);
	// get data by id
	public function find($id);
	// get first record and where column is value
	public function where($column, $value, $mode = 'all');
	// get for datatables
	public function get($perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param);
	// total item
	public function totalRecords($param);
	// check code and branch is existed
	public function checkBranchCode($code, $branchID, $brandID, $id = null);
	// destroy
	public function delete($id);
	//  get all data
	public function all($sortBy, $orderType = 'asc');
}
