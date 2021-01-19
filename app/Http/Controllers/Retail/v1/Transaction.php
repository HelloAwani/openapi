<?php

namespace Service\Http\Controllers\Retail\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Transaction extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
		$this->enforce_product  = "RET";
	}
	
	public function fetch(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;

		$rules = [
			'Date' => 'required|date_format:Y-m-d',		
		];
		//standar validator
		$this->validator($rules);
		
		$this->render();

		$data = $this->query('
		select 
		"SalesTransactionID",
		"Date",
		"SalesTransactionNumber",
		"Sales",
		"VAT",
		"Rounding"
		"Discount",
		"TotalSalesTransaction",
		"DiscountID",
		"DiscountName",
		"CustomerID",
		"CustomerName",
		"TotalPayment",
		"Changes",
		"RegisterNumber",
		case st."Void" when \'Y\'  then \'1\' else \'0\' END "Void",
		"VoidDate",
		"VoidBy",
		"VoidDescription",
		"Notes"
		from 
		"SalesTransaction" st 
		
		where 
		st."BranchID" = :BranchID
		and
		st."StoreID"  = :MainID
		and
		to_char(st."Date", \'yyyy-MM-dd\') = :Date

		order by st."SalesTransactionID" desc ',
		[
			"BranchID"=> $this->_token_detail->BranchID,
			"MainID"=> $this->_token_detail->MainID,
			"Date"=>$this->request["Date"]
		]);

		$sales_id = $this->extract_column($data, "SalesTransactionID", [0]);
		
		$sales_detail = $this->query('SELECT 
		"SalesTransactionID",
		"SalesTransactionDetailID",
		"ItemVariantID" "VariantID",
		"VariantName", 
		"ItemName",
		"Qty",
		"Price",
		"Discount",
		"DiscountID",
		"PromotionID",
		"SubTotal",
		"COGS",
		"Notes",
		"ModifierPriceID",
		case "Void" when \'Y\'  then \'1\' else \'0\' END "Void",
		"VoidDate",
		"VoidBy",
		"VoidDescription"
		
		from "SalesTransactionDetail"
		
		where "SalesTransactionID" in ('.implode(',', $sales_id).')
		and 
		"BranchID" = :BranchID
		and
		"StoreID"  = :MainID
		order by "SalesTransactionID"
		', 
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);

		$sales_detail_id = $this->extract_column($sales_detail, "SalesTransactionDetailID", [0]);
		$this->map_record($data, "Items", "SalesTransactionID", $sales_detail);


		$discount_detail_sales = $this->query('Select 
			"SalesTransactionDiscountDetailID" as "DiscountDetailID",
			"SalesTransactionID",
			"Discount",
			"DiscountID",
			"DiscountPercent",
			"DiscountName",
			"Discount"
			from "SalesTransactionDiscountDetail" 
			where "SalesTransactionID" in ('.implode(',', $sales_id).')
			order by "Order"
		'
		);

		$this->map_record($data, "DiscountDetails", "SalesTransactionID", $discount_detail_sales);

		$discount_detail_item = $this->query('Select 
			"SalesTransactionDiscountDetailID" as "DiscountDetailID",
			"SalesTransactionDetailID",
			"Discount",
			"DiscountID",
			"DiscountPercent",
			"DiscountName",
			"Discount"
			from "SalesTransactionDiscountDetail" 
			where "SalesTransactionDetailID" in ('.implode(',', $sales_detail_id).')
			order by "Order"
		'
		);
		$this->map_record($data, "DiscountDetails", "SalesTransactionDetailID", $discount_detail_item, "Items");


		$payments = $this->query('SELECT
			 "SalesTransactionID", 
			 "PaymentID",
			 "Date",
			 "PaymentMethodID",
			 "Payment" "Amount",
			 "PaymentMethodName" 
			from "Payment" 
			WHERE
			"BranchID" = :BranchID
			and
			"StoreID"  = :MainID 
			and "SalesTransactionID" in ('.implode(',', $sales_id).')
		',
		[
			"BranchID"=> $this->_token_detail->BranchID,
			"MainID"=> $this->_token_detail->MainID,
		]
		);
		foreach($payments  as &$d){
			$d->PaymentMethodName = $d->PaymentMethodID == null ? 'Cash' : $d->PaymentMethodName ;
		}

		$this->map_record($data,"Payments", "SalesTransactionID", $payments);
		


		
		$this->response->Transactions = $data;

		$this->reset_db();
		$this->render(true);
	}


}