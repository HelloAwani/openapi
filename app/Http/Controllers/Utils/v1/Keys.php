<?php

namespace Service\Http\Controllers\Utils\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Keys extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
	}
	
	public function generate(){
		
		$data["Key"] =  $guid = bin2hex(openssl_random_pseudo_bytes(6)).'-'.sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
		$data["Secret"] = $this->gen_uuid().'-'.$this->gen_uuid();
		
		$this->response->Data =  $data;

		$this->render(true);
	}

	public function pair($qty){
		$data["Pairs"] = [];
		for ($i=0; $i < $qty; $i++) { 
			$pair["Number"] = bin2hex(openssl_random_pseudo_bytes(4)).rand(10000,99999);
			$pair["Password"] = bin2hex(openssl_random_pseudo_bytes(7));
			$data["Pairs"] []= $pair;
		}
		$this->response->Data =  $data;
		$this->render(true);
	
	}

	function gen_uuid() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x'.bin2hex(openssl_random_pseudo_bytes(4)),
			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	
			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),
	
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,
	
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,
	
			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}
	

	 
}