<?php

namespace Service\Http\Controllers\BFNB\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Inventory extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
		$this->enforce_product  = "HQF";
	}
	
	public function ingredient_usages(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;

		$rules = [
			'Date' => 'required|date_format:Y-m-d',		
		];
		//standar validator
		$this->validator($rules);
		

		$data  = $this->query('SELECT 
		it."IngredientTransactionID",
		it."ReferenceID" "OrderBillID",
		it."TransactionDate",
		itd."ReferenceID" "OrderBillDetailID",
		itd."InventoryID",
		itd."InventoryCode",
		itd."InventoryName", 
		itd."Qty",
		itd."Price",
		itd."ForUsageID" as "ItemID",
		itd."ForUsageName" as "ItemName",
		itd."UnitTypeID"
		from  "IngredientTransaction" it 
		join "IngredientTransactionDetail" itd on itd."IngredientTransactionID"  = it."IngredientTransactionID"

		where
		"ForUsageType" <> \'H\' 
		and
		it."TransactionType" = \'Ingredient Usage\'
		and 
		it."BranchID" = :BranchID
		and
		it."RestaurantID"  = :MainID
		and
		to_char(it."TransactionDate", \'yyyy-MM-dd\') = :Date 
		
		',[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID,
				"Date"=>$this->request["Date"]
			]);

			$replenish  = $this->query('SELECT 
			it."IngredientTransactionID",
			it."ReferenceID" "OrderBillID",
			it."TransactionDate",
			itd."ReferenceID" "OrderBillDetailID",
			itd."InventoryID",
			itd."InventoryCode",
			itd."InventoryName", 
			itd."Qty",
			itd."Price",
			itd."ForUsageID" as "ItemID",
			itd."ForUsageName" as "ItemName",
			itd."UnitTypeID"
			from  "IngredientTransaction" it 
			join "IngredientTransactionDetail" itd on itd."IngredientTransactionID"  = it."IngredientTransactionID"
	
			where
			"ForUsageType" <> \'H\' 
			and
			it."TransactionType" = \'Void Replenish\'
			and 
			it."BranchID" = :BranchID
			and
			it."RestaurantID"  = :MainID
			and
			to_char(it."TransactionDate", \'yyyy-MM-dd\') = :Date 
			
			',[
					"BranchID"=> $this->_token_detail->BranchID,
					"MainID"=> $this->_token_detail->MainID,
					"Date"=>$this->request["Date"]
				]);
	


		$this->render();

		$this->response->IngredientUsages = $data;
		$this->response->VoidReplenish = $replenish;

		$this->reset_db();
		$this->render(true);
	}


}