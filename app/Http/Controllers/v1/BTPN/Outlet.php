<?php

namespace Service\Http\Controllers\v1\BTPN;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Outlet extends \Service\Http\Controllers\v1\_Base
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->product_id = "BTPN";
	}
	
	public function list(){
		
		$this->validate_request();

		$this->db = "ret";
		$this->response->RetailOutlets = $this->query('SELECT 
		"MetaValue" as "OutletCode", 
		sm."StoreName" as "BrandName",
		b."BranchName" as "OutletName",
		b."Address",
		b."Contact", 
		b."Email"
		from "BranchMeta" bm 
		join "Branch" b 
		on b."BranchID" = bm."BranchID"
		join "StoreMaster" sm 
		on sm."StoreID" = b."StoreID"
		and bm."MetaName" = \'BTPN.OutletCode\'
		and b."Active" = \'1\'
		');

		$this->render(true);
	
	}
	
	function outlet_current_setting(){

		$this->validate_request();
		$this->db = "ret";
		$br = $this->check_valid_outlet($this->request, $this->db);

		$data = $this->query('
		SELECT 
			"gt"."Category", 
			"gt"."GeneralSettingTypeStringID" as "GeneralSettingID", 
			"gt"."GeneralSettingTypeName" as "GeneralSettingTypeName", 
			COALESCE(COALESCE("StoreGeneralSetting"."GeneralSettingValue", gt."DefaultValue"),\'\') as 	"GeneralSettingValue", "Options"
		FROM 
			"StoreGeneralSetting"
		RIGHT JOIN 
			"GeneralSettingType" gt on "StoreGeneralSetting"."GeneralSettingTypeID" = gt."GeneralSettingTypeID"
		AND  
			"StoreGeneralSetting"."BranchID" = :id
		WHERE
			"GeneralSettingTypeStringID" not in (\'DefaultReportingEmail\', \'ReceiptFooterImage\', \'ReceiptHeaderImage\', \'SecurityTouchIDAuth\', \'SendDailyEmail\')
		AND
			"Category" not in (\'Printing\', \'Image\')
		ORDER BY "gt"."GeneralSettingTypeName"
		', 
			[
				"id"=>$br->BranchID
			]
		);
		foreach($data as $d){
			$d->Options = json_decode($d->Options);
		}

		$this->response->Settings = $data;

		$this->render(true);
	}

	function check_valid_outlet($request,$db){

		
		$rules = [
			'OutletCode' => 'required'
		];
        $this->validator = Validator::make($request, $rules);
		$this->render();


		$this->db = $db;
		$br = $this->query('
			SELECT 
			b.*
			from "BranchMeta" bm 
			join "Branch" b 
			on b."BranchID" = bm."BranchID"
			join "StoreMaster" sm 
			on sm."StoreID" = b."StoreID"
			and bm."MetaName" = \'BTPN.OutletCode\'
			and b."Active" = \'1\'
			and bm."MetaValue" = :code
		', ["code"=>$request["OutletCode"]]);

		if(@$br[0]==null){
			$this->custom_errors[] = $this->error("BTPN Outlet Code not found");
			$this->render();
		}else{
			return $br[0];
		}

	}

}

