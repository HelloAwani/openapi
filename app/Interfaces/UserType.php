<?php 
namespace Service\Interfaces;
 
interface UserType {
	// update or insert
	public function upsert($data, $id = null);
	// get data by id
	public function find($id);
	// get for datatables
	public function get($perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null);
	// where
	public function where($column, $value, $mode = 'all');
	// total records
	public function totalRecords();
	// check branch uniqueness
	public function checkBranchCode($code, $branchId);
	// destroy
	public function delete($id);
	
}