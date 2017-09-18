<?php 
namespace Service\Interfaces;
 
interface Permission {
	// get all permission
	public function all();
	// conditional get
	public function where($column, $value, $mode = 'all');
    
    public function checkTokenDB($token);
}