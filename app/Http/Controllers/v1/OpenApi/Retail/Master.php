<?php

namespace Service\Http\Controllers\v1\OpenAPI\Retail;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Master extends \Service\Http\Controllers\v1\_Base
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->product_id = "BTPN";
		$this->Outlet = new \Service\Http\Controllers\v1\BTPN\Outlet;
	}
	
	public function discounts(){

		$this->validate_request();
		$this->db = "ret";
		$br = $this->Outlet->check_valid_outlet($this->request, $this->db);

		$this->response->GlobalDiscounts = $this->query('SELECT d."DiscountID",d."DiscountName",
		d."StartDate",d."EndDate",d."StartHour",d."EndHour",
		d."Description",d."PaymentMethodID",
		COALESCE(p."PaymentMethodName", \'Cash\') as "PaymentMethodName",
								d."DiscountValue",
								d."DiscountPercent",
								d."Active",d."AfterTax"
		FROM "Discount" d 
		LEFT JOIN "PaymentMethod" p on p."PaymentMethodID" = d."PaymentMethodID" 
		where d."BranchID" = :BranchID
		and d."Global" = \'Y\'
		and d."Archived" = \'N\'
		', 
		array("BranchID"=>$br->BranchID));

		$this->response->ItemDiscounts = $this->query('SELECT d."DiscountID",d."DiscountName",
		d."StartDate",d."EndDate",d."StartHour",d."EndHour",
		d."Description",d."PaymentMethodID",
		COALESCE(p."PaymentMethodName", \'Cash\') as "PaymentMethodName",
								d."DiscountValue",
								d."DiscountPercent",
								d."Active",d."AfterTax"
		FROM "Discount" d 
		LEFT JOIN "PaymentMethod" p on p."PaymentMethodID" = d."PaymentMethodID" 
		where d."BranchID" = :BranchID
		and d."Global" = \'N\'						
		and d."Archived" = \'N\'
		', 
		array("BranchID"=>$br->BranchID));
		foreach ($this->response->ItemDiscounts as $d) {
			$d->Detail = $this->query('SELECT  
				iv."VariantName", iv."VariantCode", iv."ItemVariantID", i."ItemCode", i."ItemName", c."CategoryCode", c."CategoryName"
				FROM 
				"DiscountDetail" dd JOIN
				"ItemVariant" iv on iv."ItemVariantID" = dd."ItemVariantID"
				JOIN "Item" i on iv."ItemID" = i."ItemID"
				JOIN "Category" c on c."CategoryID" = i."CategoryID"
				where iv."BranchID" = :BranchID and dd."DiscountID" = :DiscountID
				and iv."Archived" = \'N\'
				',
				array("BranchID"=>$br->BranchID,"DiscountID" => $d->DiscountID)
			);

		}


		
		$this->render(true);
	}

	public function payment_methods(){

		$this->validate_request();
		$this->db = "ret";
		$br = $this->Outlet->check_valid_outlet($this->request, $this->db);
		$this->response->PaymentMethods = $this->query('SELECT "PaymentMethodID", "PaymentMethodName" 
		FROM "PaymentMethod" where "BranchID" = :bid 
		and ("Archived" = \'N\' or "Archived" is null)
		order by "PaymentMethodName" asc
		', [
			"bid"=>$br->BranchID
		]

		);

		$this->render(true);
	}

	

	public function expense_types(){
		
		$this->validate_request();
		$this->db = "ret";
		$br = $this->Outlet->check_valid_outlet($this->request, $this->db);
		$this->response->ExpenseTypes = $this->query('SELECT "ExpenseTypeID", 
		"ExpenseTypeName" from "ExpenseType" where ("Archived" is null or "Archived" = \'N\')
		and "BranchID" = :bid

		', [
			"bid"=>$br->BranchID
		]

		);

		$this->render(true);
	}

	public function items(){

		$this->validate_request();
		$this->db = "ret";
		$br = $this->Outlet->check_valid_outlet($this->request, $this->db);

		$this->response->Categories = $this->query('SELECT "CategoryID", "CategoryCode", "CategoryName", "Image", "Description" 
			FROM "Category" where "BranchID" = :bid 
			and ("Archived" = \'N\' or "Archived" is null)
			order by "CategoryCode" asc
			', [
				"bid"=>$br->BranchID
			]
		);
		$this->response->Categories = $this->group_record(@$this->response->Categories, "CategoryID"
		, ' SELECT "CategoryID", "ItemID", "ItemCode", "ItemName", "Description","ItemImage" from "Item" where "BranchID" = :bid
			@key 
			and ("Archived" = \'N\' or "Archived" is null)
		',[
			"bid"=>$br->BranchID
		 ]
		, "Items");
		$item_ids = array();
		$reserve = array();
		foreach($this->response->Categories as $c){
			foreach($c->Items as $i){
				$i->Variants = array();
				$item_ids[] = $i->ItemID;
				$reserve[$i->ItemID] = $i;
			}
		}

		$variants = $this->query('SELECT "ItemID", "ItemVariantID", "VariantCode", "VariantName", "DefaultMinimumPrice", "DefaultSellingPrice",
		"UseManualCOGS", "COGS", "ManualCOGS", "Barcode", coalesce("Discontinued",\'0\') as "Discontinued", iut."InventoryUnitTypeName", "Stock"
		from "ItemVariant" iv
		left join "InventoryUnitType" iut on coalesce(iv."UnitTypeID",8) = iut."InventoryUnitTypeID"
		where iv."BranchID" = :bid
		and ("Archived" = \'N\' or "Archived" is null)
		',
			[
				"bid"=>$br->BranchID
			]
		);

		foreach($variants as $v){
			$reserve[$v->ItemID]->Variants[]=$v;
		}

		foreach($this->response->Categories as $c){
			$c->RealItem = array();
			foreach($c->Items as $i){
				$c->RealItem[] = $reserve[$i->ItemID];
			}
			$c->Items = $c->RealItem;
			unset($c->RealItem);
		}
		
		foreach($this->response->Categories as $c){
			$this->set_image($c->Image);
			foreach($c->Items as $i){
				$this->set_image($i->ItemImage);
				foreach($i->Variants as $v){
					unset($v->ItemID);
				}
			}
		}
		
		$this->render(true);
	
	}

}

