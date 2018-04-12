<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;

use Service\Http\Requests;

// for testing purpose only
class TestController extends Controller
{
	public function __construct(){
		
	}

	public function __invoke($id = 0)
    {
        return 'invoke '.$id;
    }

    public function index(){
    	return 'index';
    }
}
