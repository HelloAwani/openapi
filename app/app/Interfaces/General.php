<?php 
namespace Service\Interfaces;
 
interface General {
	// get data by id
	public function get($param);
	// upsert
	public function upsert($data, $param, $id = null);
    // find
    public function find($param, $id);
}