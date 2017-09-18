<?php

namespace Service\Http\Controllers\v1\BTPN\Retail;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Report extends \Service\Http\Controllers\v1\_Base
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->product_id = "BTPN";
		$this->Outlet = new \Service\Http\Controllers\v1\BTPN\Outlet;
	}

	public function expenses(){

		$this->validate_request();
		$breakdown = \Route::current()->parameter('breakdown');
		$this->request["Breakdown"] = $breakdown;
		$this->db = "ret";
			
		$br = $this->Outlet->check_valid_outlet($this->request, $this->db);
		
		$rules = [
			'FromDate' => 'required|date_format:"Y-m-d H:i:s',
			'ToDate' => 'required|date_format:"Y-m-d H:i:s'
		];
        $this->validator = Validator::make($this->request, $rules);
		$this->render();

		$data = $this->query('SELECT 
				et."ExpenseTypeName",ex."Date",ex."Note",ex."Amount" 
				from "Expense" ex
				join "ExpenseType" et on ex."ExpenseTypeID" = et."ExpenseTypeID"
				where et."Archived" = \'N\' and ex."BranchID" = :branchid and ex."Date" >= :from and ex."Date" <= :to
			',
			array(
				"branchid"=>$br->BranchID,
				"from"=>$this->request["FromDate"],
				"to"=>$this->request["ToDate"],
			)
		);



		$this->response->Data = @$data;

		$this->render(true);

	}
	
	public function sales(){
		$this->validate_request();
		$breakdown = \Route::current()->parameter('breakdown');
		$this->request["Breakdown"] = $breakdown;
		$this->db = "ret";
		$rules = [
			'Breakdown' => 'required',
			'FromDate' => 'required|date_format:"Y-m-d H:i:s',
			'ToDate' => 'required|date_format:"Y-m-d H:i:s'
		];
        $this->validator = Validator::make($this->request, $rules);
		$this->render();
		
		$br = $this->Outlet->check_valid_outlet($this->request, $this->db);

		switch ($breakdown) {
			case 'variant':
				$data = $this->query('SELECT 
			 	iv."ItemVariantID", iv."VariantCode", i."ItemCode", i."ItemName",iv."VariantName", COALESCE(SUM("Qty"),0) as "Qty", COALESCE(SUM("SubTotal"),0) as "Amount"
				from "SalesTransactionDetail" std
				join "SalesTransaction" st on st."SalesTransactionID" = std."SalesTransactionID"
				join "ItemVariant" iv on iv."ItemVariantID" = std."ItemVariantID"
				join "Item" i on i."ItemID" = iv."ItemID"
				join "Category" c on c."CategoryID" = i."CategoryID"
				where st."BranchID" = :branchid
				and st."Date" >= :from
				and st."Date" <= :to 
				and (st."Void" = \'N\' or st."Void" is null)
				and (std."Void" = \'N\' or std."Void" is null)
				group by iv."ItemVariantID", iv."VariantCode", i."ItemCode", i."ItemName",iv."VariantName"
				order by iv."ItemVariantID", iv."VariantCode", i."ItemCode", i."ItemName",iv."VariantName"
				',
				array(
					"branchid"=>$br->BranchID,
					"from"=>$this->request["FromDate"],
					"to"=>$this->request["ToDate"],
				)
				);
				break;

		case 'item':
				$data = $this->query('SELECT 			
					c."CategoryCode", c."CategoryName", i."ItemCode", i."ItemName", i."ItemID", COALESCE(SUM("Qty"),0) as "Qty", COALESCE(SUM("SubTotal"),0) as "Amount"
					from "SalesTransactionDetail" std
					join "SalesTransaction" st on st."SalesTransactionID" = std."SalesTransactionID"
					left join "ItemVariant" iv on iv."ItemVariantID" = std."ItemVariantID"
					join "Item" i on i."ItemID" = iv."ItemID"
					join "Category" c on c."CategoryID" = i."CategoryID"
					where st."BranchID" = :branchid
					and st."Date" >= :from
					and st."Date" <= :to 
					and (st."Void" = \'N\' or st."Void" is null)
					and (std."Void" = \'N\' or std."Void" is null)
					group by c."CategoryCode", c."CategoryName", i."ItemCode", i."ItemName", i."ItemID"
					order by c."CategoryCode", c."CategoryName", i."ItemCode", i."ItemName", i."ItemID"
				',
				array(
					"branchid"=>$br->BranchID,
					"from"=>$this->request["FromDate"],
					"to"=>$this->request["ToDate"],
				)
				);
		break;
			
		case 'category':

				$data = $this->query('SELECT 			 
					c."CategoryID", c."CategoryCode", c."CategoryName", 
					c."CategoryID", i."ItemName", i."ItemCode", COALESCE(SUM("Qty"),0) as "Qty", COALESCE(SUM("SubTotal"),0) as "Amount"
					from "SalesTransactionDetail" std
					join "SalesTransaction" st on st."SalesTransactionID" = std."SalesTransactionID"
					join "ItemVariant" iv on iv."ItemVariantID" = std."ItemVariantID"
					join "Item" i on i."ItemID" = iv."ItemID"
					join "Category" c on c."CategoryID" = i."CategoryID"
					where st."BranchID" = :branchid
					and st."Date" >= :from
					and st."Date" <= :to 
					and (st."Void" = \'N\' or st."Void" is null)
					and (std."Void" = \'N\' or std."Void" is null)
					group by c."CategoryID", c."CategoryCode", c."CategoryName", i."ItemName", i."ItemCode"
					order by c."CategoryID", c."CategoryCode", c."CategoryName", i."ItemName", i."ItemCode"
				',
				array(
					"branchid"=>$br->BranchID,
					"from"=>$this->request["FromDate"],
					"to"=>$this->request["ToDate"],
				)
				);
				break;

		case 'payment':
		
						$data = $this->query('SELECT coalesce("PaymentMethodID",0) "PaymentMethodID", "PaymentMethodName", SUM("Received") as "Amount", sum("Count") as "Qty"
								FROM 
								(
								SELECT p.*,COALESCE(ob."Changes",0) as "Changes",
								p."Total" - COALESCE(ob."Changes",0) as "Received",
								(
									SELECT COUNT(*) FROM "Payment" ip WHERE ip."SalesTransactionID" = p."SalesTransactionID" 
									AND COALESCE(ip."SalesTransactionID",-1) = COALESCE(p."SalesTransactionID",-1)
								) as "Count"
								FROM
								(
									SELECT
										COALESCE (
											"PaymentMethod"."PaymentMethodName",
											\'Cash\'
										) AS "PaymentMethodName",
										"Payment"."PaymentMethodID",
										SUM ("Payment"."Payment") AS "Total",
										"Payment"."SalesTransactionID"
									FROM
										"Payment"
									LEFT JOIN "PaymentMethod" ON "PaymentMethod"."PaymentMethodID" = "Payment"."PaymentMethodID"
									JOIN "SalesTransaction" ob ON ob."SalesTransactionID" = "Payment"."SalesTransactionID"
									and (
										ob."Void" = \'N\'
										OR ob."Void" IS NULL
									)
									WHERE
										"ob"."BranchID" = :branchid
									AND "Payment"."Date" >= :from
									AND "Payment"."Date" <= :to
									GROUP BY
										"Payment"."SalesTransactionID",
										"Payment"."PaymentMethodID",
										COALESCE (
											"PaymentMethod"."PaymentMethodName",
											\'Cash\'
										)													
									) as p
								LEFT JOIN "SalesTransaction" ob ON ob."SalesTransactionID" = p."SalesTransactionID" and p."PaymentMethodID" is 
								null where  (ob."Void" = \'N\' or ob."Void" is null)
								)d
								GROUP BY "PaymentMethodID", "PaymentMethodName" 

						',
						array(
							"branchid"=>$br->BranchID,
							"from"=>$this->request["FromDate"],
							"to"=>$this->request["ToDate"],
						)
						);
						break;
			case "shift" :

				$shifts = $this->query('
				select "ShiftName", "From", "To"
				from "Shift"
				where "BranchID" = :BranchID and
				"Archived" = \'N\'
					',array(
							"BranchID" => $br->BranchID
						));
				if(count($shifts) > 0){
					foreach ($shifts as $index => $shift) {
						$payments = $this->query('
										select "PaymentMethodID", "PaymentMethodName",SUM("Payment") "TotalPayment" from (
										select COALESCE(pm."PaymentMethodID", 0) as "PaymentMethodID",  COALESCE(pm."PaymentMethodName", \'Cash\') "PaymentMethodName", py."Payment", to_char(py."Date", \'HH24:MI:SS\') "Time"
										from "Payment" py left join "PaymentMethod" pm on py."PaymentMethodID" = pm."PaymentMethodID" 
										where "Date" >= :FromDate and "Date" <= :ToDate
										and py."BranchID" = :BranchID
										) "Payments" where "Time" >= :From and "Time" <= :To
										group by "PaymentMethodID", "PaymentMethodName" 

									',array(
											"BranchID" => $br->BranchID,
											"From" => $shift->From,
											"To" => $shift->To,
											"FromDate"=>$this->request["FromDate"],
											"ToDate"=>$this->request["ToDate"],
										));
						$shifts[$index]->PaymentMethods = $payments;
					}
				}
				$data = $shifts;


				break;

			case "discount" :
				
				$data = $this->query('SELECT "DiscountID", "DiscountName",sum("Amount") as "TotalDiscountedAmount",  sum("TransQty") as "TotalDiscountedBill", sum("ItemQty") as "TotalDiscountedItem"
				FROM 
				(

				SELECT "DiscountID", "DiscountName",sum(coalesce("Amount",0)) as "Amount", count(*) as "TransQty", sum("ItemQty") as
					"ItemQty"
				FROM (
				select d."DiscountID", d."DiscountName", obd."SalesTransactionID", SUM(obd."Discount") as "Amount",SUM(obd."Qty") as "ItemQty" 
				from "SalesTransaction" ob 
				join "SalesTransactionDetail" obd on obd."SalesTransactionID" = ob."SalesTransactionID"
				join "Discount" d on d."DiscountID" = obd."DiscountID" 
				where ob."BranchID" = :branchid
				and ob."Date" >= :from
				and ob."Date" >= :to  
				and ("ob"."Void" =  \'N\' or "ob"."Void" is null)
				and ("obd"."Void" =  \'N\' or "obd"."Void" is null)
				GROUP BY d."DiscountID", d."DiscountName", obd."SalesTransactionID"
				) fd
				group by fd."DiscountID", fd."DiscountName"

				Union 
				
				select d."DiscountID", d."DiscountName", SUM(coalesce(ob."Discount",0)) as "Amount",count(*) as "TransQty", 0 as "ItemQty" 
				from "SalesTransaction" ob 
				join "Discount" d on d."DiscountID" = ob."DiscountID" 
				where ob."BranchID" = :branchid
				and ob."Date" >= :from
				and ob."Date" >= :to  
				and ("ob"."Void" =  \'N\' or "ob"."Void" is null) 
				GROUP BY d."DiscountID", d."DiscountName"
				) dt 
				group by 1,2
				order by 2
				', array(
						"branchid"=>$br->BranchID,
						"from"=>$this->request["FromDate"],
						"to"=>$this->request["ToDate"],
					));


			break;

			default:
				$data = array();
				break;
		}

		$this->response->Data = @$data;
		$this->render(true);
	
	}

	public function pnl(){

		$this->validate_request();
		$this->db = "ret";
		$rules = [
			'FromDate' => 'required|date_format:"Y-m-d H:i:s',
			'ToDate' => 'required|date_format:"Y-m-d H:i:s'
		];
		

		$br = $this->Outlet->check_valid_outlet($this->request, $this->db);
		$fdsales = $this->query('

		SELECT 
		SUM(coalesce(std."COGS",0)*coalesce(std."Qty",0)) as "COGS", SUM(
			case when coalesce(std."Discount",0) = 0
			then std."Price" - (std."SubTotal"/std."Qty")
			else std."Discount" end 
		) as "Discount",
		sum(std."SubTotal") as "SubTotal",
		sum(std."Qty"*std."Price") as "ItemSubtotal"
		FROM "SalesTransaction" st 
		JOIN "SalesTransactionDetail" std on st."SalesTransactionID" = std."SalesTransactionID"
		where std."BranchID" = :BranchID
		and st."Date" >= :FromDate
		and st."Date" <= :ToDate 
		and (std."Void" = \'N\' or std."Void" is null)
		',

		array(
			"BranchID"=>$br->BranchID,
			"FromDate"=>$this->request["FromDate"],
			"ToDate"=>$this->request["ToDate"],
			)
		)[0];

		$fsales = $this->query('
						SELECT 
						COALESCE(SUM("TotalSalesTransaction"),0) as "Sales",
						COALESCE(SUM("VAT"),0) as "Tax",
						COALESCE(SUM("Rounding"),0) as "Rounding",
						SUM(
							case when COALESCE("Rounding",0) <= 0 then COALESCE("Rounding",0)
							else 0 end
						) as "RoundingMin",
						SUM(
							case when COALESCE("Rounding",0) > 0 then COALESCE("Rounding",0)
							else 0 end
						) as "RoundingPlus",
						COALESCE(SUM("Discount"),0) as "Discount"
						FROM "SalesTransaction"
						where "BranchID" = :BranchID
						and "Date" >= :FromDate
						and "Date" <= :ToDate 
						and ("Void" = \'N\' or "Void" is null)
						',

						array(
							"BranchID"=>$br->BranchID,
							"FromDate"=>$this->request["FromDate"],
							"ToDate"=>$this->request["ToDate"],
							)
						)[0];
		$expense = $this->query('
						SELECT et."ExpenseTypeName", sum("Amount") as "Amount" from "Expense" e 
						join "ExpenseType" et 
						on et."ExpenseTypeID" = e."ExpenseTypeID"
						WHERE  e."BranchID" = :BranchID
						and "Date" >= :FromDate
						and "Date" <= :ToDate 
						and (e."Archived" is null or e."Archived" = \'N\' )
						GROUP BY 1
						',

						array(
							"BranchID"=>$br->BranchID,
							"FromDate"=>$this->request["FromDate"],
							"ToDate"=>$this->request["ToDate"],
							)
						);

		$itrans = $this->query('SELECT 

			case when 
			"Qty" <= 0 then "TransactionType"||\' Out\'
			when 
			"Qty" > 0 then "TransactionType"||\' In\'
			end
			as "Type", 
			sum("Qty") as "Qty"
			from "InventoryTransaction" it 
			join "InventoryTransactionDetail" itd on itd."InventoryTransactionID" = it."InventoryTransactionID"
			where it."BranchID" = :BranchID
			and "TransactionDate" >= :FromDate
			and "TransactionDate" <= :ToDate 
			and "TransactionType" in  
			(
			\'Stock Readjustment\',
			\'Stock Opname\'
			)
			group by 1
		',

						array(
							"BranchID"=>$br->BranchID,
							"FromDate"=>$this->request["FromDate"],
							"ToDate"=>$this->request["ToDate"],
							)
						);

		$iadjst["In"] = 0;
		$iadjst["Out"] = 0;

		foreach ($itrans as $it) {
		switch ($it->Type) {

			case 'Stock Readjustment In':
				$iadjst["In"] += (float) $it->Qty;
				break;
			case 'Stock Readjustment Out':
				$iadjst["Out"] += (float) $it->Qty;
				break;
			case 'Stock Opname In':
				$iadjst["In"] += (float) $it->Qty;
				break;
			case 'Stock Opname Out':
				$iadjst["Out"] += (float) $it->Qty;
				break;
			
			default:
				# code...
				break;
		}
		}

		$expense_total = 0;
		foreach ($expense as $e) {
		$expense_total += (float) $e->Amount;
		}



		$this->response->Data = new \stdClass();

		$this->response->Data->TotalItemSales = (float) $fdsales->ItemSubtotal;
		$this->response->Data->TotalDiscountItem = (float) $fdsales->Discount;
		$this->response->Data->SubTotal = $this->response->Data->TotalItemSales  - $this->response->Data->TotalDiscountItem; 

		$this->response->Data->Tax = (float) $fsales->Tax; 
		$this->response->Data->TotalDiscountBill = (float) $fsales->Discount;
		$this->response->Data->TotalRounding = (float) $fsales->Rounding;
		$this->response->Data->TotalRoundingMin = abs((float) $fsales->RoundingMin);
		$this->response->Data->TotalRoundingPlus = abs((float) $fsales->RoundingPlus);
		$this->response->Data->Gross = $this->response->Data->SubTotal - $this->response->Data->TotalDiscountBill + $this->response->Data->TotalRounding;


		$this->response->Data->Net = $this->response->Data->Gross - $this->response->Data->Tax;
		$this->response->Data->TotalCOGS = (float) $fdsales->COGS;
		$this->response->Data->AdjustmentTotal = $iadjst["In"] - $iadjst["Out"] ;
		$this->response->Data->AdjustmentIn = $iadjst["In"];
		$this->response->Data->AdjustmentOut = $iadjst["Out"];



		$this->response->Data->TotalExpenses = (float) $expense_total;
		$this->response->Data->ExpenseDetail = $expense;



		$this->response->Data->Profit = $this->response->Data->Net - $this->response->Data->TotalCOGS + $this->response->Data->AdjustmentTotal - $this->response->Data->TotalExpenses;

		print_r($this->response);

	
		$this->render(true);
	}

	public function sales_summary(){


		$this->validate_request();
		$this->db = "ret";
		$rules = [
			'FromDate' => 'required|date_format:"Y-m-d H:i:s',
			'ToDate' => 'required|date_format:"Y-m-d H:i:s'
		];


		$limit = @$this->request["Limit"] == null ? '10' : @$this->request["Limit"]; 
		$br = $this->Outlet->check_valid_outlet($this->request, $this->db);
		$fsales = $this->query('

									SELECT 
									COALESCE(SUM("Sales"),0) - COALESCE(SUM("Discount"),0) as "Sales",
									COALESCE(SUM("TotalSalesTransaction"),0) as "TotalSalesTransaction",
									COUNT(*) as "TotalTransaction"
									FROM "SalesTransaction"
									where "BranchID" = :BranchID
									and "Date" >= :FromDate
									and "Date" <= :ToDate 
									and ("Void" = \'N\' or "Void" is null)',

									array(
										"BranchID"=>$br->BranchID,
										"FromDate"=>$this->request["FromDate"],
										"ToDate"=>$this->request["ToDate"],
										)
									)[0];
		$gross_sales = (float)$fsales->TotalSalesTransaction;
		$sales = (float)$fsales->Sales;
		$total_transactions = (float)$fsales->TotalTransaction;

		$this->response->Sales = $sales;
		$this->response->GrossSales = $gross_sales;
		$this->response->TotalTransaction = $total_transactions;
		$this->response->TotalTransaction = $total_transactions;
		$this->response->AverageTransaction = $total_transactions == 0 ? 0 : round($gross_sales/$total_transactions,2);

		$this->response->TopCategoryByAmount = $this->query('
										select 
										c."CategoryName", COALESCE(SUM("SubTotal"),0) as "Amount"
										from "SalesTransactionDetail" std
										join "SalesTransaction" st on st."SalesTransactionID" = std."SalesTransactionID"
										join "ItemVariant" iv on iv."ItemVariantID" = std."ItemVariantID"
										join "Item" i on i."ItemID" = iv."ItemID"
										join "Category" c on c."CategoryID" = i."CategoryID"
										where st."BranchID" = :BranchID
										and st."Date" >= :FromDate
										and (st."Void" = \'N\' or st."Void" is null)
										and (std."Void" = \'N\' or std."Void" is null)
										and st."Date" <= :ToDate 
										group by c."CategoryName"
										Order By "Amount" desc
										LIMIT '.$limit,

										array(
											"BranchID"=>$br->BranchID,
											"FromDate"=>$this->request["FromDate"],
											"ToDate"=>$this->request["ToDate"],
										)
										);


		$this->response->TopCategoryByQty = $this->query('
										select 
										c."CategoryName", COALESCE(SUM("Qty"),0) as "Qty"
										from "SalesTransactionDetail" std
										join "SalesTransaction" st on st."SalesTransactionID" = std."SalesTransactionID"
										join "ItemVariant" iv on iv."ItemVariantID" = std."ItemVariantID"
										join "Item" i on i."ItemID" = iv."ItemID"
										join "Category" c on c."CategoryID" = i."CategoryID"
										where st."BranchID" = :BranchID
										and st."Date" >= :FromDate
										and st."Date" <= :ToDate 
										and (st."Void" = \'N\' or st."Void" is null)
										and (std."Void" = \'N\' or std."Void" is null)
										group by c."CategoryName"
										Order By "Qty" desc
										LIMIT '.$limit,

										array(
											"BranchID"=>$br->BranchID,
											"FromDate"=>$this->request["FromDate"],
											"ToDate"=>$this->request["ToDate"],
										)
										);

		$this->response->TopItemByAmount = $this->query('
										select 
										i."ItemName", COALESCE(SUM("SubTotal"),0) as "Amount"
										from "SalesTransactionDetail" std
										join "SalesTransaction" st on st."SalesTransactionID" = std."SalesTransactionID"
										join "ItemVariant" iv on iv."ItemVariantID" = std."ItemVariantID"
										join "Item" i on i."ItemID" = iv."ItemID"
										join "Category" c on c."CategoryID" = i."CategoryID"
										where st."BranchID" = :BranchID
										and st."Date" >= :FromDate
										and st."Date" <= :ToDate 
										and (st."Void" = \'N\' or st."Void" is null)
										and (std."Void" = \'N\' or std."Void" is null)
										group by i."ItemName"
										Order By "Amount" desc
										LIMIT '.$limit,

										array(
											"BranchID"=>$br->BranchID,
											"FromDate"=>$this->request["FromDate"],
											"ToDate"=>$this->request["ToDate"],
										)
										);


		$this->response->TopItemByQty = $this->query('
										select 
										i."ItemName", COALESCE(SUM("Qty"),0) as "Qty"
										from "SalesTransactionDetail" std
										join "SalesTransaction" st on st."SalesTransactionID" = std."SalesTransactionID"
										join "ItemVariant" iv on iv."ItemVariantID" = std."ItemVariantID"
										join "Item" i on i."ItemID" = iv."ItemID"
										join "Category" c on c."CategoryID" = i."CategoryID"
										where st."BranchID" = :BranchID
										and st."Date" >= :FromDate
										and st."Date" <= :ToDate 
										and (st."Void" = \'N\' or st."Void" is null)
										and (std."Void" = \'N\' or std."Void" is null)
										group by i."ItemName"
										Order By "Qty" desc
										LIMIT '.$limit,

										array(
											"BranchID"=>$br->BranchID,
											"FromDate"=>$this->request["FromDate"],
											"ToDate"=>$this->request["ToDate"],
										)
										);

		$this->response->TopPaymentByAmount = $this->query('
										

									SELECT "PaymentMethodName", SUM("Received") as "Amount"
													FROM 
													(
													SELECT p.*,COALESCE(ob."Changes",0) as "Changes",
													p."Total" - COALESCE(ob."Changes",0) as "Received",
													(
														SELECT COUNT(*) FROM "Payment" ip WHERE ip."SalesTransactionID" = p."SalesTransactionID" 
														AND COALESCE(ip."SalesTransactionID",-1) = COALESCE(p."SalesTransactionID",-1)
													) as "Count"
													FROM
													(
														SELECT COALESCE("PaymentMethod"."PaymentMethodName", \'Cash\') as "PaymentMethodName", "Payment"."PaymentMethodID",  SUM("Payment"."Payment") as "Total",
														"SalesTransactionID"
														FROM "Payment"
														LEFT JOIN "PaymentMethod" on "PaymentMethod"."PaymentMethodID" = "Payment"."PaymentMethodID"
															where "Payment"."BranchID" = :BranchID
															and "Date" >= :FromDate
															and "Date" <= :ToDate 
														GROUP BY "SalesTransactionID", "Payment"."PaymentMethodID", COALESCE("PaymentMethod"."PaymentMethodName", \'Cash\') 
													) as p
													LEFT JOIN "SalesTransaction" ob ON ob."SalesTransactionID" = p."SalesTransactionID" and p."PaymentMethodID" is 
													null where  (ob."Void" = \'N\' or ob."Void" is null)
													)d
													GROUP BY "PaymentMethodName"
										Order By "Amount" desc
										LIMIT '.$limit,

										array(
											"BranchID"=>$br->BranchID,
											"FromDate"=>$this->request["FromDate"],
											"ToDate"=>$this->request["ToDate"],
										)
										);

			$this->response->TopPaymentByQty = $this->query('
										
									SELECT "PaymentMethodName", SUM("Count") as "Qty"
													FROM 
													(
													SELECT p.*,COALESCE(ob."Changes",0) as "Changes",
													p."Total" - COALESCE(ob."Changes",0) as "Received",
													(
														SELECT COUNT(*) FROM "Payment" ip WHERE ip."SalesTransactionID" = p."SalesTransactionID" 
														AND COALESCE(ip."SalesTransactionID",-1) = COALESCE(p."SalesTransactionID",-1)
													) as "Count"
													FROM
													(
														SELECT COALESCE("PaymentMethod"."PaymentMethodName", \'Cash\') as "PaymentMethodName", "Payment"."PaymentMethodID",  SUM("Payment"."Payment") as "Total",
														"SalesTransactionID"
														FROM "Payment"
														LEFT JOIN "PaymentMethod" on "PaymentMethod"."PaymentMethodID" = "Payment"."PaymentMethodID"
															where "Payment"."BranchID" = :BranchID
															and "Date" >= :FromDate
															and "Date" <= :ToDate 
														GROUP BY "SalesTransactionID", "Payment"."PaymentMethodID", COALESCE("PaymentMethod"."PaymentMethodName", \'Cash\') 
													) as p
													LEFT JOIN "SalesTransaction" ob ON ob."SalesTransactionID" = p."SalesTransactionID" and p."PaymentMethodID" is 
													null where  (ob."Void" = \'N\' or ob."Void" is null)
													)d
													GROUP BY "PaymentMethodName"
										Order By "Qty" desc
										LIMIT '.$limit,

										array(
											"BranchID"=>$br->BranchID,
											"FromDate"=>$this->request["FromDate"],
											"ToDate"=>$this->request["ToDate"],
										)
										);


			$plot = $this->query('
			select to_char("Date", \'yyyy-MM-dd HH\') as "DateHour",  
			to_char("Date", \'yyyy-MM-dd\') as "Date",  
			to_char("Date", \'Day\') as "Day",
			to_char("Date", \'HH\') as "Hour",
			count(*) as "TotalSales",
			sum("TotalSalesTransaction") as "TotalAmount"
			from "SalesTransaction"
			where "BranchID" =:BranchID
			and "Date" >= :FromDate
			and "Date" < :ToDate
			and ("Void" is null or "Void" = \'N\')
			group by 1,2,3,4
			order by 1,2,3,4
			',
			array(
				"BranchID"=>$br->BranchID,
				"FromDate"=>$this->request["FromDate"],
				"ToDate"=>$this->request["ToDate"],
			)
			);
			$dt = array();
			foreach($plot as $p){
				$dt[$p->Date]["Date"] = $p->Date;
				$dt[$p->Date]["Day"] = $p->Day;
			}		
			foreach($dt as &$p){
				for($i=0; $i<=23; $i++){
					$h["Hour"] = str_pad($i, 2, '0', STR_PAD_RIGHT);
					$h["Visitor"] = 0;
					$h["Sales"] = 0;
					$p["Hour"][$i] = $h;
				}
			}
			$dt = json_decode(json_encode($dt), true);
			foreach($plot as $p){
				@$dt[$p->Date]["Hour"][$p->Hour]["Visitor"] = @$p->TotalSales;
				@$dt[$p->Date]["Hour"][$p->Hour]["Sales"] = @$p->TotalAmount;
			}
			foreach($dt as &$d){
				$d["Hour"] = array_values($d["Hour"]);
			}

			$this->response->SalesPlot = array_values($dt);

			$this->render(true);

	}

	public function void(){
		
		$this->validate_request();
		$this->db = "ret";
		$rules = [
			'FromDate' => 'required|date_format:"Y-m-d H:i:s',
			'ToDate' => 'required|date_format:"Y-m-d H:i:s'
		];
        $this->validator = Validator::make($this->request, $rules);
		$this->render();
		$br = $this->Outlet->check_valid_outlet($this->request, $this->db);
		

		$data = $this->query('
		select   std."ItemVariantID", std."VariantName", std."ItemName", std."CategoryName", std."Qty", std."Price", std."SubTotal", u."Fullname", std."VoidDescription", std."VoidDate", std."VoidByType", std."VoidBy" 
		from "SalesTransactionDetail" std 
		left join "Users" u on std."VoidBy" = u."UserID"
		where std."VoidDate" >= :from and std."VoidDate" <= :to and std."Void" = \'Y\'
		and std."BranchID" = :branchid
			',
			array(
				"branchid"=>$br->BranchID,
				"from"=>$this->request["FromDate"],
				"to"=>$this->request["ToDate"],
			));

		$this->response->Data = $data;

		$this->render(true);
		
	}

}

