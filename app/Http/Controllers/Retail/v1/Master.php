<?php

namespace Service\Http\Controllers\Retail\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Master extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
		//enforce
		$this->enforce_product  = "RET";
	}
	
	public function categories(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$data = $this->query('SELECT "CategoryID", "Color", 
		\'https://hellobill-retail.s3.amazonaws.com/\'||"Image" "Image", 
		"Description", "CategoryCode","CategoryName",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"

		from "Category" where "BranchID" = :BranchID and  "StoreID" = :MainID
		AND ("Archived" is null or "Archived" = \'N\')
		order by "CategoryCode"
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		$this->response->Categories = $data;

		$this->reset_db();
		$this->render(true);
	}

	public function items(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$items = $this->query('SELECT distinct "CategoryID", i."ItemID", 
		\'https://hellobill-retail.s3.amazonaws.com/\'||"ItemImage" "Image", 
		"ItemCode", "ItemName",
		coalesce("AllowDecimalStock", \'0\') "AllowDecimalStock",
		coalesce("AllowVariant", \'0\') "SingleVariant",
		i."Description",
		case i."RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "Item" i 
		join "ItemVariant" v on v."ItemID" =  i."ItemID"
		where i."BranchID" = :BranchID and  i."StoreID" = :MainID
		AND (i."Archived" is null or i."Archived" = \'N\')
		AND (v."Archived" is null or v."Archived" = \'N\')
		order by "ItemCode"
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		$item_id  = $this->extract_column($items, "ItemID",[0]);

		$variants = $this->query('SELECT 
		"ItemID",
		"ItemVariantID"  "VariantID",
		"VariantCode",
		"VariantName",
		"Description",
		"Barcode",
		"DefaultMinimumPrice" "MinimumPrice",
		"DefaultSellingPrice" "DefaultPrice",
		"Stock",
		case "UseManualCOGS" when \'Y\'  then \'1\' else \'0\' END "UseManualCOGS",
		"ManualCOGS" "ManualCOGS",
		"COGS" "PerpetualCost",
		"UnitTypeID",
		coalesce("PrefixedBarcode", \'1\') as  "PrefixedBarcode",
		coalesce("StrictSerialNumber", \'1\') as  "UniqueSerialNumber",
		coalesce("AllowTax", \'1\') as  "Taxed",
		case v."RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		FROM "ItemVariant" v
		where v."BranchID" = :BranchID and  v."StoreID" = :MainID
		AND (v."Archived" is null or v."Archived" = \'N\')
		AND "ItemID" in ('.implode(',', $item_id).')
		order by "VariantCode"
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);

		
		$variant_id  = $this->extract_column($variants, "VariantID",[0]);
		
		$this->map_record($items,"Variants", "ItemID",$variants);

		$item_images = $this->query('SELECT 
			"ObjectID" as "ItemID",
			"Caption",
			\'https://hellobill-retail.s3.amazonaws.com/\'||"Image" "Image"
			from "MultiImage"
			where "ObjectID" in ('.implode(',', $item_id).')
			and "Type" = \'I\'
		order by  "Caption"
		');
		
		$this->map_record($items,"Images", "ItemID",$item_images);

		$variant_images = $this->query('SELECT 
			"ObjectID" as "VariantID",
			"Caption",
			\'https://hellobill-retail.s3.amazonaws.com/\'||"Image" "Image"
			from "MultiImage"
			where "ObjectID" in ('.implode(',', $variant_id).')
			and "Type" = \'V\'
		');
		
		$this->map_record($items,"Images", "VariantID",$variant_images, "Variants");




		$this->response->Items = $items;

		$this->reset_db();
		$this->render(true);
	}


	
	public function multi_prices(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$data =  $this->query('SELECT 
		"ModifierPriceID" "MultiPriceID",
		"ModifierPriceCode" "MultiPriceCode",
		"ModifierPriceName" "MultiPriceName",
		"DefaultPercentage"*100 as "DefaultMarkupPercentage",
		"DefaultValue" as  "DefaultMarkupValue",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "ModifierPrice"
		where 
		"BranchID" = :BranchID 
		and 
		"StoreID" = :MainID

		order by "ModifierPriceCode"
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		
		$item  =   $this->query('SELECT 
			"ModifierPriceID"  "MultiPriceID", 
			m."ItemVariantID"  "ItemID",
			m."VariantCode",
			m."VariantName",
			"OverridePrice"  
			from "ModifierPriceItem"  ipm  
			join  "ItemVariant" m on ipm."ItemVariantID" = m."ItemVariantID"
			where 
			m."BranchID" = :BranchID 
			and 
			m."StoreID" = :MainID
			AND (m."Archived" is null or m."Archived" = \'N\')
			order by m."VariantCode"  
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		
		$this->response->MultiPrices  =  $this->map_record($data, "OverridedItems", "MultiPriceID", $item);

		$this->reset_db();
		$this->render(true);
	}

	public function payment_methods(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$data =  $this->query('SELECT "PaymentMethodID", "PaymentMethodName",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from  "PaymentMethod"
		where 
		"BranchID" = :BranchID 
		and 
		"StoreID" = :MainID
		AND ("Archived" is null or "Archived" = \'N\')

		order by "PaymentMethodName"
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		$this->response->PaymentMethods =  $data;


		$this->reset_db();
		$this->render(true);
	}


	
	public function shifts(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$param   =  [
			"BranchID"=> $this->_token_detail->BranchID,
			"MainID"=> $this->_token_detail->MainID
		];
		$clause = ' AND "StoreID"  = :MainID ';
		if($this->_token_detail->BranchID>0){
			unset($param["MainID"]);
			$clause =  "";
		}

		$data =  $this->query('SELECT 
		"ShiftID",
		coalesce("ShiftCode",   "ShiftName") "ShiftCode",
		"ShiftName",
		"From",
		"To"
		from "Shift"
		where 
		"BranchID" = :BranchID 
		 
		'.$clause.'
		AND ("Archived" is null or "Archived" = \'N\')

		order by "ShiftCode"
		',
			$param
		);
		$this->response->Shifts =  $data;


		$this->reset_db();
		$this->render(true);
	}





	public function users(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$param   =  [
			"BranchID"=> $this->_token_detail->BranchID,
			"MainID"=> $this->_token_detail->MainID
		];
		$clause = ' AND "StoreID"  = :MainID ';
		if($this->_token_detail->BranchID>0){
			unset($param["MainID"]);
			$clause =  "";
		}

		$user_type =  $this->query('SELECT
		"UserTypeID", "UserTypeCode", "UserTypeName"
		FROM "UserType"
		WHERE  
		"BranchID" = :BranchID 
		AND ("Archived" is null or "Archived" = \'N\')
		'.$clause.'
		order by "UserTypeCode"
		',
			$param
		);

		$uid = $this->extract_column($user_type, "UserTypeID", [0]);

		
		$users =  $this->query('SELECT 
		"UserTypeID", 
		"UserID",
		"PIN",
		"UserCode",
		"Fullname"  "Fullname",
		"ShiftID",
		"JoinDate"   
		
		from "Users" where
		"BranchID" = :BranchID 
		and  "StoreID" = :MainID
		AND ("Archived" is null or "Archived" = \'N\')
		and "UserTypeID" in ('.implode(',',  $uid).')   
		order by "UserCode"
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);

		$this->map_record($user_type,"Users", "UserTypeID", $users);


		$this->response->UserTypes =  $user_type;

		$this->reset_db();
		$this->render(true);

	}

	
	public function promotions(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;

		$discounts =  $this->query('
		select "DiscountID", "DiscountName", "StartDate",
		"EndDate", "StartHour", "EndHour", "Description", "PaymentMethodID",
		"DiscountValue",  "DiscountPercent",  "DiscountFlat",
		case "Global" when \'N\' then  \'0\' else \'1\' end as  "IsGlobal",
		case "Active" when \'N\' then  \'0\' else \'1\' end as  "IsActive",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "Discount"
		where 
		"BranchID" = :BranchID 
		and 
		"StoreID" = :MainID
		AND ("Archived" is null or "Archived" = \'N\')

		order by "DiscountName"
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		$discount_id = $this->extract_column($discounts, "DiscountID", [0]);
		
		$items = $this->query('
		select 
		"DiscountID", 
		m."ItemVariantID"  "ItemID",
		m."VariantCode",
		m."VariantName"
		from "DiscountDetail"  ipm  
		join  "ItemVariant" m on ipm."ItemVariantID" = m."ItemVariantID"
		where 
		m."BranchID" = :BranchID 
		and 
		m."StoreID" = :MainID
		AND (m."Archived" is null or m."Archived" = \'N\')
		and "DiscountID" in ('.implode(',',$discount_id).')
		order by m."VariantCode"  
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
	
		$this->response->Discounts  =  $this->map_record($discounts, "Items", "DiscountID", $items);










		$promotions =  $this->query('
		select "PromotionID", 
		"PaymentMethodID",
		"PromotionType",
		"PromotionName",
		\'https://hellobill-retail.s3.amazonaws.com/\'||"Image" "Image", 
		"NextItemDiscountValue",
		"NextItemDiscount" "NextItemDiscountPercent",
		"MinimumPurchaseQty",
		"MinimumPurchase"  "MinimumPurchaseValue",
		"GetQty" "PromotionItemQty",
		case "ApplyMultiple" when \'Y\' then  \'1\' else \'0\' end as  "ApplyMultiple",
		case "Active" when \'Y\' then  \'1\' else \'0\' end as  "Active",
		"DateFrom",
		"DateTo",
		"HourFrom",
		"HourTo",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "Promotion"
		where 
		"BranchID" = :BranchID 
		and 
		"StoreID" = :MainID
		AND ("Archived" is null or "Archived" = \'N\')

		order by "PromotionName"
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		$promotion_id = $this->extract_column($promotions, "PromotionID", [0]);
		
		$applied_promotion = $this->query('SELECT 
			"PromotionID",
			v."ItemVariantID" as "VariantID",
			v."VariantCode" as "VariantCode",
			v."VariantName" as "VariantName"
			FROM 
			"PromotionPurchaseDetail" d 
			join "ItemVariant" v on d."ItemVariantID" = v."ItemVariantID"
			where
			"BranchID" = :BranchID 
			and 
			"StoreID" = :MainID
			AND ("Archived" is null or "Archived" = \'N\')
			and "PromotionID" in ('.implode(',',$promotion_id).')

		', 
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		
		$promotion_item = $this->query('SELECT
		"PromotionID",
		v."ItemVariantID" as "VariantID",
		v."VariantCode" as "VariantCode",
		v."VariantName" as "VariantName"
		FROM 
		"PromotionItem" d 
		join "ItemVariant" v on d."ItemVariantID" = v."ItemVariantID"
		where
		"BranchID" = :BranchID 
		and 
		"StoreID" = :MainID
		AND ("Archived" is null or "Archived" = \'N\')
		and "PromotionID" in ('.implode(',',$promotion_id).')
		', 
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		$this->map_record($promotions, "AppliedItems", "PromotionID", $applied_promotion);
		$this->map_record($promotions, "PromotionItems", "PromotionID", $promotion_item);



		$this->response->Promotions =  $promotions;
		$this->reset_db();
		$this->render(true);

	}

	public function customers(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$data =  $this->query('
		select "CustomerID", 
		"CustomerCode",  "CustomerName", 
		"PhoneNumber", "Email",
		"DOB",
		"Gender",
		"Note"
		from "Customer"
		where 
		"StoreID" = :MainID

		order by "CustomerName"
		',
			[
				"MainID"=> $this->_token_detail->MainID,
			]
		);
		
		$this->response->Customers = $data;
		$this->reset_db();
		$this->render(true);
	}



}