<?php

namespace Service\Http\Controllers\BFNB\v1;

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
		$this->enforce_product  = "HQF";
	}

	public function categories(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$data = $this->query('

		SELECT b."BranchID", "CategoryID", "Color", 
		\'https://hellobill-fnb.s3.amazonaws.com/\'||c."Image" "Image", 
		c."Description", "CategoryCode","CategoryName",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "Category" c
		join "Branch" b on b."BranchID" = c."BranchID"
		where b."RestaurantID" = :MainID  and b."Active" = \'1\'
		AND (c."Archived" is null or c."Archived" = \'N\')
		order by 1,"CategoryCode"
		',
			[
				"MainID"=> $this->_token_detail->MainID
			]
		);

		$this->response->Data = $this->join($data, "Categories");



		$this->reset_db();
		$this->render(true);
	}
	
	public function items(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$data = $this->query('SELECT 

		b."BranchID",
		"CategoryID", "MenuID", 
		\'https://hellobill-fnb.s3.amazonaws.com/\'||"MenuImage" "Image", 
		"Color", "MenuCode", "MenuName", "Price", m."Description",  
		"TaxExclusive",
		case "UseManualFoodCost" when \'Y\'  then \'1\' else \'0\' END "UseManualFoodCost",
		"ManualFoodCost", 
		"FoodCost" "PerpetualFoodCost",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "Menu" m
		join "Branch" b on b."BranchID" = m."BranchID"
		where b."RestaurantID" = :MainID  and b."Active" = \'1\'

		AND (m."Archived" is null or m."Archived" = \'N\')
		order by 1, "MenuCode"
		',
			[
				"MainID"=> $this->_token_detail->MainID
			]
		);

		$this->response->Data = $this->join($data, "Items");

		$this->reset_db();
		$this->render(true);
	}

	
	public function inventory_categories(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$data = $this->query('SELECT 
		"InventoryCategoryID",
		"InventoryCategoryCode",
		"InventoryCategoryName",
		"Description",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "InventoryCategory" where "BranchID" = :BranchID and  "RestaurantID" = :MainID
		AND ("Archived" is null or "Archived" = \'N\')
		order by "InventoryCategoryCode"
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		$this->response->InventoryCategories = $data;

		$this->reset_db();
		$this->render(true);
	}

	public function unit_type(){
		
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;

		$this->response->unit_types = $this->query('SELECT 
			"InventoryUnitTypeID" "UnitTypeID",
			"InventoryUnitTypeAbbv" "UnitTypeAbbv",
			"InventoryUnitTypeName" "UnitTypeName"
			FROM "InventoryUnitType"
			ORDER BY "InventoryUnitTypeName"
		'
		);
		
		$this->reset_db();
		$this->render(true);
	}

	public function inventories(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$data = $this->query('SELECT 
		"InventoryCategoryID",
		"InventoryID",
		"InventoryCode",
		"InventoryName",
		"MinimumStock",
		"MaximumStock",
		"CurrentStock" "Stock",
		i."InventoryUnitTypeID",
		iut."InventoryUnitTypeName",
		"ManualCost",
		"PerpetualCost",
		case "UseManualCost" when \'Y\'  then \'1\' else \'0\' END "UseManualCost",
		"LastRestock",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "Inventory" i 
		left join "InventoryUnitType" iut on iut."InventoryUnitTypeID"  = coalesce(i."InventoryUnitTypeID",8)
		where "BranchID" = :BranchID and  "RestaurantID" = :MainID  AND ("Archived" is null or "Archived" = \'N\')
		order by "InventoryCode"
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		$this->response->Inventories = $data;

		$this->reset_db();
		$this->render(true);
	}

	public function modifiers(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		


		$bbid = $this->get_branch_id();


		$data = $this->query('SELECT 
		"BranchID",
		"ModifierGroupID",
		"ModifierGroupName",
		"Min",
		"Max",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "ModifierGroup" 
		where "BranchID" in ('.implode(',', $bbid).') and  "RestaurantID" = :MainID  AND ("Archived" is null or "Archived" = \'N\')
		order by "ModifierGroupName"
		',
			[
				"MainID"=> $this->_token_detail->MainID
			]
		);
		$id = $this->extract_column($data, "ModifierGroupID");

		$mods  = $this->query('SELECT 
			mi."BranchID",
			mg."ModifierGroupID", "ModifierName",  "PriceModifier",
			case mi."RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
			From "ModifierItem" mi
			join "ModifierGroup" mg on mi."ModifierGroupID" = mg."ModifierGroupID"

			where mi."BranchID"in ('.implode(',', $bbid).') and  mi."RestaurantID" = :MainID  AND (mi."Archived" is null or mi."Archived" = \'N\')
			and 
			mg."BranchID" in ('.implode(',', $bbid).') and  mg."RestaurantID" = :MainID  AND (mg."Archived" is null or mg."Archived" = \'N\')

			',
			[
				"MainID"=> $this->_token_detail->MainID
			]
		);


		$menu  = $this->query('SELECT m."BranchID", "ModifierGroupID", m."MenuID",m."MenuCode", m."MenuName"
			From "MenuModifier" mm
			join "Menu" m on m."MenuID" = mm."MenuID"
			where m."BranchID" in ('.implode(',', $bbid).') and  m."RestaurantID" = :MainID  AND (m."Archived" is null or m."Archived" = \'N\')
			order by m."MenuCode"
			',
			[
				"MainID"=> $this->_token_detail->MainID
			]
		);


		$this->map_record($data,"Modifiers", "ModifierGroupID", $mods);
		$this->map_record($data,"Items", "ModifierGroupID", $menu);
		


		$this->response->Data = $this->join($data, "Modifiers");

		$this->reset_db();
		$this->render(true);
	}
	
	public function ingredients(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$this->response->ItemIngredients =  $this->query('SELECT
		ig."IngredientForID" "ItemID",
		ig."InventoryID",
		m."MenuCode" "ItemCode",
		m."MenuName" "ItemName",
		i."InventoryCode" as "IngredientCode",
		i."InventoryName" as "IngredientName",
		ig."Qty",
		iut."InventoryUnitTypeID",
		iut."InventoryUnitTypeName"
		from  "Ingredient" ig 
		join "Inventory" i on i."InventoryID"  = ig."InventoryID"
		join "Menu" m  on m."MenuID"=  ig."IngredientForID"
		left join "InventoryUnitType" iut on  iut."InventoryUnitTypeID" = coalesce(ig."IngredientUnitTypeID", i."InventoryUnitTypeID")
		where 
		m."BranchID" = :BranchID and  m."RestaurantID" = :MainID  AND (m."Archived" is null or m."Archived" = \'N\')
		and
		i."BranchID" = :BranchID and  i."RestaurantID" = :MainID  AND (i."Archived" is null or i."Archived" = \'N\')
		and ig."IngredientForType"  =  \'1\'
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
	
		$this->response->InventoryIngredients =  $this->query('SELECT
		ig."IngredientForID" "ItemID",
		ig."InventoryID",
		m."InventoryCode" "InventoryCode",
		m."InventoryName" "InventoryName",
		i."InventoryCode" as "IngredientCode",
		i."InventoryName" as "IngredientName",
		ig."Qty",
		iut."InventoryUnitTypeID",
		iut."InventoryUnitTypeName"
		from  "Ingredient" ig 
		join "Inventory" i on i."InventoryID"  = ig."InventoryID"
		join "Inventory" m  on m."InventoryID"=  ig."IngredientForID"
		left join "InventoryUnitType" iut on  iut."InventoryUnitTypeID" = coalesce(ig."IngredientUnitTypeID", i."InventoryUnitTypeID")
		where 
		m."BranchID" = :BranchID and  m."RestaurantID" = :MainID  AND (m."Archived" is null or m."Archived" = \'N\')
		and
		i."BranchID" = :BranchID and  i."RestaurantID" = :MainID  AND (i."Archived" is null or i."Archived" = \'N\')
		and ig."IngredientForType"  =  \'3\'
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		
		$this->response->ModifierIngredients =  $this->query('SELECT
		ig."IngredientForID" "ItemID",
		ig."InventoryID",
		m."ModifierName" ,
		i."InventoryCode" as "IngredientCode",
		i."InventoryName" as "IngredientName",
		ig."Qty",
		iut."InventoryUnitTypeID",
		iut."InventoryUnitTypeName"
		from  "Ingredient" ig 
		join "Inventory" i on i."InventoryID"  = ig."InventoryID"
		join "ModifierItem" m  on m."ModifierItemID"=  ig."IngredientForID"
		left join "InventoryUnitType" iut on  iut."InventoryUnitTypeID" = coalesce(ig."IngredientUnitTypeID", i."InventoryUnitTypeID")
		where 
		m."BranchID" = :BranchID and  m."RestaurantID" = :MainID  AND (m."Archived" is null or m."Archived" = \'N\')
		and
		i."BranchID" = :BranchID and  i."RestaurantID" = :MainID  AND (i."Archived" is null or i."Archived" = \'N\')
		and ig."IngredientForType"  =  \'2\'
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
	
		$this->reset_db();
		$this->render(true);
	}
	 
	public function multi_prices(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$bbid = $this->get_branch_id();
		$data =  $this->query('SELECT 
		"BranchID",
		"ItemModifierPriceID" "MultiPriceID",
		"ItemModifierPriceCode" "MultiPriceCode",
		"ItemModifierPriceName" "MultiPriceName",
		"DefaultPercentage"*100 as "DefaultMarkupPercentage",
		"DefaultValue" as  "DefaultMarkupValue",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "ItemModifierPrice"
		where 
		"BranchID" in ('.implode(',', $bbid).')
		and 
		"RestaurantID" = :MainID

		order by "ItemModifierPriceCode"
		',
			[
				"MainID"=> $this->_token_detail->MainID
			]
		);
		
		$menu  =   $this->query('SELECT 
			m."BranchID",
			"ItemModifierPriceID"  "MultiPriceID", 
			m."MenuID"  "ItemID",
			m."MenuCode" "ItemCode",
			m."MenuName" "ItemName",
			"OverridePrice"  
			from "ItemModifierPriceMenu"  ipm  
			join  "Menu" m on ipm."MenuID" = m."MenuID"
			where 
			m."BranchID" in ('.implode(',', $bbid).')
			and 
			m."RestaurantID" = :MainID
			AND (m."Archived" is null or m."Archived" = \'N\')
			order by m."MenuCode"  
		',
			[
				"MainID"=> $this->_token_detail->MainID
			]
		);
		
		$xdata  =  $this->map_record($data, "OverridedItems", "MultiPriceID", $menu);
		$this->response->Data = $this->join($xdata, "MultiPrices");


		$this->reset_db();
		$this->render(true);
	}


	public function tags(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$data =  $this->query('
		select "TagID", "TagGroupName", "TagName",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "Tag"
		where 
		"BranchID" = :BranchID 
		and 
		"RestaurantID" = :MainID

		order by "TagGroupName",  "TagName"
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
		
		$menu = $this->query('
		select 
		"TagID", 
		m."MenuID"  "ItemID",
		m."MenuCode" "ItemCode",
		m."MenuName" "ItemName"
		from "MenuTag"  ipm  
		join  "Menu" m on ipm."MenuID" = m."MenuID"
		where 
		m."BranchID" = :BranchID 
		and 
		m."RestaurantID" = :MainID
		AND (m."Archived" is null or m."Archived" = \'N\')
		order by m."MenuCode"  
		',
			[
				"BranchID"=> $this->_token_detail->BranchID,
				"MainID"=> $this->_token_detail->MainID
			]
		);
	
		$this->response->Tags  =  $this->map_record($data, "Items", "TagID", $menu);

		$this->reset_db();
		$this->render(true);
	}

	
	public function promotions(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$bbid = $this->get_branch_id();
		$discounts =  $this->query('
		select "BranchID", "DiscountID", "DiscountName", "StartDate",
		"EndDate", "StartHour", "EndHour", "Description", "PaymentMethodID",
		"DiscountValue",  "DiscountPercent",  "DiscountFlat",
		case "Global" when \'N\' then  \'0\' else \'1\' end as  "IsGlobal",
		case "Active" when \'N\' then  \'0\' else \'1\' end as  "IsActive",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from "Discount"
		where 
		"BranchID" in ('.implode(',', $bbid).')
		and 
		"RestaurantID" = :MainID
		AND ("Archived" is null or "Archived" = \'N\')

		order by "DiscountName"
		',
			[
				"MainID"=> $this->_token_detail->MainID
			]
		);

		$discount_id = $this->extract_column($discounts, "DiscountID", [0]);
		
		$menu = $this->query('
		select 
		m."BranchID",
		"DiscountID", 
		m."MenuID"  "ItemID",
		m."MenuCode" "ItemCode",
		m."MenuName" "ItemName"
		from "DiscountMenu"  ipm  
		join  "Menu" m on ipm."MenuID" = m."MenuID"
		where 
		m."BranchID" in ('.implode(',', $bbid).')
		and 
		m."RestaurantID" = :MainID
		AND (m."Archived" is null or m."Archived" = \'N\')
		and "DiscountID" in ('.implode(',',$discount_id).')
		order by m."MenuCode"  
		',
			[
				"MainID"=> $this->_token_detail->MainID
			]
		);

		$this->map_record($discounts, "Items", "DiscountID", $menu);
		$this->response->Data  =  $this->join($discounts, "Discounts");
		

		$this->reset_db();
		$this->render(true);
	}

	public function payment_methods(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;
		
		$bbid = $this->get_branch_id();

		$data =  $this->query('SELECT "BranchID", "PaymentMethodID", "PaymentMethodName",
		case "RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
		from  "PaymentMethod"
		where 
		"BranchID" in ('.implode(',', $bbid).')
		and 
		"RestaurantID" = :MainID
		AND ("Archived" is null or "Archived" = \'N\')

		order by "PaymentMethodName"
		',
			[
				"MainID"=> $this->_token_detail->MainID
			]
		);
		$this->response->Data  =  $this->join($data, "PaymentMethods");


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
		$clause = ' AND "RestaurantID"  = :MainID ';
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
		$clause = ' AND "RestaurantID"  = :MainID ';
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
		and  "RestaurantID" = :MainID
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
		"RestaurantID" = :MainID

		order by "CustomerName"
		',
			[
				"MainID"=> $this->_token_detail->MainID
			]
		);
		
		$this->response->Customers = $data;
		$this->reset_db();
		$this->render(true);
	}






}