<?php 
namespace Service\Interfaces;
 
interface Shift {
	// insert or update
	public function upsert($data, $id = null);
	// get data by id
	public function find($id);
	// get for datatable
	public function get($perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param);
	// get total records
	public function totalRecords($param);
	// destroy
	public function delete($id);
}