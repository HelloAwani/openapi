<?php

namespace Service\Http\Controllers\BFNB\v1;

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
		$this->enforce_product  = "HQF";
	}
	
	public function fetch(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		$bbid = $this->get_branch_id();

		$rules = [
			'Date' => 'required|date_format:Y-m-d',		
		];
		//standar validator
		$this->validator($rules);
		
		$this->render();
		$bbid = $this->get_branch_id();

		$data = $this->query('
		select 
		Distinct 
		o."BranchID",
		o."OrderID",
		o."UserID",
		o."ClockIn",
		o."ClockOut",
		coalesce(o."ByCustomerName",\'\') "CustomerName",
		o."OrderType",
		o."GuestNumber",
		o."DeliveryPhone",
		o."DeliveryAddress",
		o."ReceivedTime",
		o."TotalOrder",
		coalesce(o."TableName", \'\') "TableName",
		nullif(o."TableID",0) as "TableID",
		case o."Void" when \'Y\'  then \'1\' else \'0\' END "Void",
		o."VoidDate",
		o."VoidByUserID" "VoidBy" 
		from "Order" o 
		join "OrderBill" ob on ob."OrderID" = o."OrderID"
		where 
		o."BranchID" in ('.implode(',', $bbid).')
		and
		o."RestaurantID"  = :MainID
		and
		ob."BranchID" in ('.implode(',', $bbid).')
		and
		ob."RestaurantID"  = :MainID
		and
		to_char(ob."Date", \'yyyy-MM-dd\') = :Date
		order by "OrderID" desc
		',
			[
				"MainID"=> $this->_token_detail->MainID,
				"Date"=>$this->request["Date"]
			]
		);

		
		$order_id = $this->extract_column($data, "OrderID", [0]);

		$order_detail = $this->query('
		
			select 
			"BranchID",
			"OrderID",
			"OrderDetailID",
			"UserID",
			"SeatNumber",
			"MenuID",
			"MenuName",
			"FoodCategoryName" as "CategoryName",
			"Qty",
			"Price",
			"OrderDate",
			"TotalOrder",
			"Discount",
			case "Void" when \'Y\'  then \'1\' else \'0\' END "Void",
			"VoidByUserID" "VoidBy",
			"VoidLevel"
			
			from "OrderDetail" 
			where 
			"BranchID" in ('.implode(',', $bbid).')
			and
			"RestaurantID"  = :MainID 
			and "OrderID" in ('.implode(',', $order_id).')
		',
		[
			"MainID"=> $this->_token_detail->MainID,
		]
		);

		$this->map_record($data,"Orders", "OrderID", $order_detail);

		$order_bill = $this->query('
			select
			"BranchID",
			"OrderID",
			"OrderBillID",
			"BillPrintedBy",
			"Date",
			"OrderBillNumber",
			"TableID",
			"CustomerID",
			"SeatNumber",
			"Bill" as "NetBill",
			"VAT",
			"ServiceTax" as "ServiceCharge",
			"Rounding", 
			"TotalPayment",
			"Changes",
			"DiscountID",
			"Discount",
			"DiscountName", 
			"TotalBilling" "GrossBill",
			"Notes",
			case "Void" when \'Y\'  then \'1\' else \'0\' END "Void",			
			"VoidDate",
			nullif("VoidBy",0) "VoidBy",
			"VoidLevel",
			"VoidDescription"
			
			from "OrderBill"
			WHERE
			"BranchID" in ('.implode(',', $bbid).')
			and
			"RestaurantID"  = :MainID 
			and "OrderID" in ('.implode(',', $order_id).')
		',
		[
			"MainID"=> $this->_token_detail->MainID,
		]
		);

		
		$order_bill_id = $this->extract_column($order_bill, "OrderBillID", [0]);

		$this->map_record($data,"Bills", "OrderID", $order_bill);

		
		$order_bill_detail = $this->query('SELECT
			"BranchID",
			 "OrderBillID", 
			 "OrderBillDetailID", 
			"MenuID" "ItemID",
			"MenuName" "ItemName",
			"Qty",
			"Price",
			"SubTotal",
			nullif("TableID", 0) "TableID",
			"Discount",
			"DiscountID",
			"DiscountName",
			case "Void" when \'Y\'  then \'1\' else \'0\' END "Void",			
			"VoidBy",
			"VoidLevel",
			"VoidDate",
			nullif("ItemModifierPriceID", 0) "MultiPriceID"
			from "OrderBillDetail" 
			WHERE
			"BranchID" in ('.implode(',', $bbid).')
			and
			"RestaurantID"  = :MainID 
			and "OrderBillID" in ('.implode(',', $order_bill_id).')
		',
		[
			"MainID"=> $this->_token_detail->MainID,
		]
		);
		$order_bill_detail_id = $this->extract_column($order_bill_detail, "OrderBillDetailID", [0]);

		$this->map_record($data,"Items", "OrderBillID", $order_bill_detail, "Bills");

		$order_bill_modifier_detail = $this->query('SELECT 
			"BranchID",
			"OrderBillDetailID",
			"OrderBillModifierDetailID", 
			"ModifierID",
			"PriceModifier",
			"ModifierName",
			"UserID"
			from "OrderBillModifierDetail"
			WHERE
			"BranchID" in ('.implode(',', $bbid).')
			and
			"RestaurantID"  = :MainID 
			and "OrderBillDetailID" in ('.implode(',', $order_bill_detail_id).')
		',
		[
			"MainID"=> $this->_token_detail->MainID,
		]
		);
		
		$this->map_record($data,"Modifiers", "OrderBillDetailID", $order_bill_modifier_detail, "Bills.Items");

		$payments = $this->query('SELECT
			"BranchID",
			 "OrderBillID", 
			 "PaymentID",
			 "Date",
			 "PaymentMethodID",
			 "Payment" "Amount",
			 "ReferenceNumber",
			 "PaymentMethodName" 
			from "Payment" 
			WHERE
			"BranchID" in ('.implode(',', $bbid).')
			and
			"RestaurantID"  = :MainID 
			and "OrderBillID" in ('.implode(',', $order_bill_id).')
		',
		[
			"MainID"=> $this->_token_detail->MainID,
		]
		);
		foreach($payments  as &$d){
			$d->PaymentMethodName = $d->PaymentMethodID == null ? 'Cash' : $d->PaymentMethodName ;
		}

		$this->map_record($data,"Payments", "OrderBillID", $payments, "Bills");
		
		

		
		$order_open_detail = $this->query('SELECT
			"BranchID",
			 "OrderBillID", 
			 "OrderBillOpenItemID", 
			"UserID",
			"ItemName",
			"Qty",
			"Price",
			"Total" "SubTotal",
			nullif("TableID", 0) "TableID"

			from "OrderBillOpenItem" 
			WHERE
		"BranchID" in ('.implode(',', $bbid).')
			and
			"RestaurantID"  = :MainID 
			and "OrderBillID" in ('.implode(',', $order_bill_id).')
		',
		[
			"MainID"=> $this->_token_detail->MainID,
		]
		);
		$this->map_record($data,"OpenItems", "OrderBillID", $order_open_detail, "Bills");


		
		$this->response->Data = $this->join($data, "Transactions");

		$this->reset_db();
		$this->render(true);
	}


}