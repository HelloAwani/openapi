<?php

namespace Service\Http\Controllers\v1\OpenAPI\Retail;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Transaction extends \Service\Http\Controllers\v1\_Base
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->product_id = "OpenAPI";
		$this->Outlet = new \Service\Http\Controllers\v1\OpenAPI\Outlet;
	}
	
	public function sales(){

		$this->validate_request();
		$this->db = "ret";
		$rules = [
			'FromDate' => 'required|date_format:"Y-m-d H:i:s',
			'ToDate' => 'required|date_format:"Y-m-d H:i:s'
		];
        $this->validator = Validator::make($this->request, $rules);
		$this->render();
		
		$br = $this->Outlet->check_valid_outlet($this->request, $this->db);


		$sales = $this->query('SELECT "Date", "Sales", "VATPercentage", "VAT", "Rounding",
		"TotalSalesTransaction", "TotalPayment", "Changes", coalesce("Notes", \'\') "Notes", "UserID", "DiscountID", "DiscountName", coalesce("Discount", 0) as "Discount", "DiscountPercent", coalesce("Void", \'N\') as "Void",
		coalesce("VoidDescription", \'\') as "VoidDescription", "VoidDate", "VoidBy", "SalesTransactionNumber", "CustomerName", "CustomerID", "LocalID", "SalesTransactionID"
		FROM "SalesTransaction"
		WHERE
		"BranchID" = :branchid and "Date" >= :from and "Date" <= :to 
		', array(
			"branchid" => $br->BranchID,
			"from" => $this->request["From"],
			"to" => $this->request["To"]
		));

		$sales = $this->group_record(@$sales, "SalesTransactionID"
		, ' SELECT iv."ItemID", 
				coalesce(sdt."Notes", \'\') "Notes", sdt."ItemVariantID",sdt."ItemName", sdt."VariantName", sdt."CategoryName", 
				"Qty", sdt."Price", "SubTotal", coalesce("Void", \'N\') as "Void",
			coalesce("VoidDescription", \'\') as "VoidDescription", "VoidDate", "DiscountID", "DiscountName", 
			coalesce("Discount", 0) as "Discount", "DiscountPercent",
				"VoidBy", sdt."COGS", "PromotionID" FROM "SalesTransactionDetail" sdt  
				join "ItemVariant" iv on iv."ItemVariantID" = sdt."ItemVariantID"
				WHERE 1 = 1 @key
		',[
		 ]
		, "Detail");

		$sales = $this->group_record(@$sales, "SalesTransactionID"
		, 'SELECT "DiscountID", "DiscountName", "DiscountPercent", "Discount"
					from "SalesTransactionDiscountDetail"
					where 1 = 1 @key
		',[
		 ]
		, "DiscountDetail");





		$this->render(true);
	}

}

