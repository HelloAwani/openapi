<?php 
namespace Service\Interfaces;
 
interface Meta {
	// check something
	public function checkUnique($table, $column, $code, $branchID, $brandID, $id = null);
    // get
	public function get($table, $data, $param = null, $join = null, $where = null);
    // upsert
    public function upsert($table, $data, $param, $id = null);
    // find
    public function find($table, $id, $first = false);
    // find detail
    public function findDetail($table, $id, $column);
    // get
    public function getDataTable($table, $display, $column, $perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param, $join = null);
    // get without check archived
    public function getDataTableTransaction($table, $display, $column, $perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param, $join = null);
    // total record
    public function totalRecords($table, $param);
    
    // delete
	public function delete($table, $id);
    // delete detail
	public function deleteDetail($table, $id, $column);
    // get last id
	public function getLastID();
}