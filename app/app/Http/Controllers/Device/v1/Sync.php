<?php

namespace Service\Http\Controllers\Device\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;


class Sync extends _Base
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
        }
    }
    
    function get_token_outlets($account_id){
        
        $valids = $this->webservice(AUTH_URL.'account-v2/account/get_permissions/get_managed_outlets', 
				[
					"data"=>
					json_encode([
						"AccountID"=>$account_id,
						"Interop" => "cross_server"
					])
				]
		);

		$prd = array();
		foreach ($valids->Products as $p) {
			if($p->ProductID==PRODUCT_CODE){
				return @$p->Outlets;
			}
		}
		return array();
    }
    
    public function room(){
        $this->validate_request();

        $this->response->SpaceSections =  $this->query('SELECT 
			"SpaceSectionID", "SpaceSectionName", "Description", "Order"
			FROM
			"SpaceSection" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null 
            order by "Order" asc
			', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        )
        );


        $this->response->SpaceSections = $this->group_record(@$this->response->SpaceSections, "SpaceSectionID", 
            'SELECT "SpaceSectionID", "SpaceID", "SpaceName", "Description", "Order" FROM
			"Space" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null @key
            order by "Order" asc ',
            array(
                "BrandID"=>$this->request["BrandID"],
                "BranchID"=>$this->request["BranchID"]
            )
            , "Spaces");


        $this->render(true);
    }


	public function master(){

        $this->validate_request();
        $c_trans = new Transaction;
        $brands = $this->get_token_outlets($this->request["AccountID"]);
        foreach ($brands as $b) {
           foreach ($b->Outlets as $br) {
               $br->IsCurrent = $br->BranchID = $this->request["BranchID"]; 
           }
        }
        $this->response->Outlets = $brands;

        $this->response->InventoryUnitTypes = $this->query('SELECT "InventoryUnitTypeID", "InventoryUnitTypeName", "InventoryUnitTypeAbbv" from 
            "InventoryUnitType"
        ');

        $this->response->CurrentBillSequenceNumber = $c_trans->generate_trans_number($this->request["BranchID"]);

		$gt = $this->query('SELECT DISTINCT "Category" from "GeneralSettingType" order by "Category" ');
		foreach ($gt as $d) {
			$d->Settings = $this->query('
			SELECT 
			"gt"."GeneralSettingTypeID" as "GeneralSettingID", 
			"gt"."GeneralSettingTypeName" as "GeneralSettingTypeName", 
			"gt"."AcceptedDataType" as "DataType",
			COALESCE(COALESCE("GeneralSetting"."GeneralSettingValue", gt."DefaultValue"),\'\') as 
			"GeneralSettingValue", "Options"
			FROM 
			"GeneralSetting"
			RIGHT JOIN 
			"GeneralSettingType" gt on "GeneralSetting"."GeneralSettingTypeID" = gt."GeneralSettingTypeID"
            and 
			"GeneralSetting"."BranchID" = :BranchID and 
			"GeneralSetting"."BrandID" = :BrandID 
			WHERE  
			gt."Category" = :Category
			ORDER BY "gt"."GeneralSettingTypeName"
			', array(
                "Category" => $d->Category,
                "BranchID" => $this->request["BranchID"],
                "BrandID" => $this->request["BrandID"]
            ));
            foreach ($d->Settings as $s) {
				$s->Options = json_decode($s->Options);
			}
			
		}
		$this->response->GeneralSettings = $gt;

        $this->response->UserTypes = $this->query('SELECT "UserTypeID", "UserTypeCode", "UserTypeName" from 
            "UserType" where "BranchID" = :BranchID and "BrandID" = :BrandID
            and "Archived" is null
        ', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        )
        );
        
        $this->response->UserTypes = $this->group_record(@$this->response->UserTypes, "UserTypeID", 
            'SELECT "UserTypeID", "PermissionID" FROM "UserTypePermission" WHERE 1 = 1 @key
             ',
            array(
            )
            , "Permissions");


        $general = $this->webservice(AUTH_URL."general/master");

		$this->response->Languages =  $general->Languages;
		$this->response->PredefinedPaymentMethods =  $general->PredefinedPaymentMethods;
		$this->response->DeviceTypes = $general->DeviceTypes;
		$this->response->PrinterTypes =  $general->PrinterTypes;


		$this->response->PaymentMethods =  $this->query('SELECT 
			"PaymentMethodID", "PaymentMethodName", "Type", "PredefinedPaymentMethodID"
			FROM
			"PaymentMethod" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null
			', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        )
        );

		$this->response->Shifts =  $this->query('SELECT 
			"ShiftID", "ShiftName", "ShiftCode", "From", "To"
			FROM
			"Shift" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null
			', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        )
        );

		$this->response->SpaceSections =  $this->query('SELECT 
			"SpaceSectionID", "SpaceSectionName", "Description", "Order"
			FROM
			"SpaceSection" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null 
            order by "Order" asc
			', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        )
        );


        $this->response->SpaceSections = $this->group_record(@$this->response->SpaceSections, "SpaceSectionID", 
            'SELECT "SpaceSectionID", "SpaceID", "SpaceName", "Description", "Order" FROM
            "Space" where "BranchID" = :BranchID and "BrandID" = :BrandID
            and "Archived" is null @key
            order by "Order" asc ',
            array(
                "BrandID"=>$this->request["BrandID"],
                "BranchID"=>$this->request["BranchID"]
            )
            , "Spaces");

        $this->response->Printers =  $this->query('SELECT "IPAddress", "PrinterName", "PrinterTypeID", "PrinterID" from "Printer" where "BranchID" = :BranchID and "BrandID" = :BrandID
            and "Archived" is null 
            
            ', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        )
        );


        $this->render(true);
    }


    public function user(){
        $this->validate_request();

        $this->response->Users = $this->query('SELECT 
            "UserID", "UserCode", "Fullname", "ShiftID", "Email", "UserTypeID", "Description", 
            "ActiveStatus", "PIN", "JoinDate"
            FROM
			"User" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null 
        ', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        ));


        $this->render(true);
    }

    public function promotion(){

        $this->validate_request();

        $this->response->GlobalDiscounts = $this->query('SELECT "DiscountID", "DiscountName",
            "StartDate", "EndDate", "StartHour", "EndHour", "Description", "PaymentMethodID", 
            "DiscountValue", "DiscountPercent", "DiscountFlat", "Active", "AfterTax", 
            "AfterServiceCharge" ,
            case "DiscountType" when \'P\' then \'Both Products\'
            when \'I\' then \'Items\'
            when \'S\' then \'Services\'
            end "DiscountTypeName",
            "DiscountType"

            from "Discount" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null 
            and ("Global" = \'1\'  or "DiscountType" = \'P\' )
        ', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        ));

        $this->response->ItemDiscounts = $this->query('SELECT "DiscountID", "DiscountName",
            "StartDate", "EndDate", "StartHour", "EndHour", "Description", "PaymentMethodID", 
            "DiscountValue", "DiscountPercent", "DiscountFlat", "Active", "AfterTax", 
            "AfterServiceCharge" 
            from "Discount" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null 
            and "Global" = \'0\'  and "DiscountType" = \'I\' 
        ', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        ));

        $this->response->ServiceDiscounts = $this->query('SELECT "DiscountID", "DiscountName",
            "StartDate", "EndDate", "StartHour", "EndHour", "Description", "PaymentMethodID", 
            "DiscountValue", "DiscountPercent", "DiscountFlat", "Active", "AfterTax", 
            "AfterServiceCharge" 
            from "Discount" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null 
            and "Global" = \'0\'  and "DiscountType" = \'S\' 
        ', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        ));

        $this->response->ServiceDiscounts = $this->group_record(@$this->response->ServiceDiscounts, [
                "Alias" => 'd',
                "Key" => 'DiscountID'
            ]
            , 'SELECT
            d."DiscountID",
            p."SubServiceID",
            p."SubServiceCode",
            p."SubServiceName",
            p."SubPrice",
            p."SubServiceDuration",
            s."ServiceCode",
            s."ServiceName",
            s."ServiceID",
            s."Price",
            s."ServiceDuration",
            s."DurationUnitID"
            from "Discount" d 
            join "DiscountDetail" dd on dd."DiscountID" = d."DiscountID"
            join "SubService" p on p."SubServiceID" = dd."ProductID"
            join "Service" s on s."ServiceID" = p."ServiceID"
            where 
            p."Archived" is null and s."Archived" is null @key
            ',
            array(

            )
            , "Services");


            $this->response->ItemDiscounts = $this->group_record(@$this->response->ItemDiscounts, [
                    "Alias" => 'd',
                    "Key" => 'DiscountID'
                ]
                , 'select d."DiscountID",
                p."ItemID",
                p."ItemCode",
                p."ItemName",
                p."Price"
                from "Discount" d 
                join "DiscountDetail" dd on dd."DiscountID" = d."DiscountID"
                join "Item" p on p."ItemID" = dd."ProductID"
                join "Category" c on c."CategoryID" = p."CategoryID"
                where 
                p."Archived" is null and c."Archived" is null @key
                ',
                array(

                )
                , "Items");

        $this->render(true);
    }



    public function customer(){
        $this->validate_request();

        $this->response->Customers = $this->query('SELECT 
            "CustomerID", "CustomerName", "PhoneNumber", "Email", "Note", "DOB", "Note", 
            "Gender", "IDNumberType", "IDNumber", "CustomerCode", coalesce("LocalID", \'local-web-\'||"CustomerID") "LocalID", "Address"
            FROM
			"Customer" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null 
        ', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        ));

        $this->response->Customers = $this->group_record(@$this->response->Customers, "CustomerID", 
            'SELECT "CustomerID", "CustomerAddressID", "Street", "City", "Province",
            "ZipCode", "Note", "Phone", coalesce("IsDefault",\'0\') "IsDefault"
            FROM
			"CustomerAddress" where 1 = 1 @key
            ',
            array()
            , "Addresses");


        $this->render(true);
    }
    public function product(){
        $this->validate_request();


        $this->response->Services = $this->query('SELECT 
            "ServiceID", "ServiceCode", "ServiceName", "Image",
            coalesce("LocalID", \'local-web-\'||"ServiceID") "LocalID",
            ("ServiceDuration"*"Multiplier")::int "ServiceDuration", "Price",
            coalesce("CommissionPercent",0) as "CommissionPercent",
            coalesce("CommissionValue",0) as "CommissionValue",
            coalesce("ServiceLevelCommission", \'0\') as "ServiceLevelCommission"
            FROM
			"Service" s 
            join "DurationUnit" d on d."DurationUnitID" = s."DurationUnitID"
            where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null 
        ', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        ));


        $this->response->SubServices = $this->query('SELECT 
            "SubServiceID", "SubServiceCode", "SubServiceName", "Image","ServiceID",
            coalesce("LocalID", \'local-web-\'||"SubServiceID") "LocalID",
            ("SubServiceDuration"*"Multiplier")::int "SubServiceDuration", "SubPrice",
            coalesce("CommissionPercent",0) as "SubCommissionPercent",
            coalesce("CommissionValue",0) as "SubCommissionValue",
            coalesce("Mandatory",\'0\') as "Mandatory"
            FROM
			"SubService"  s
            join "DurationUnit" d on d."DurationUnitID" = s."DurationUnitID"
            where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null 
        ', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        ));

        $this->response->SubServices = $this->group_record(@$this->response->SubServices, "SubServiceID", 
            'SELECT su."SubServiceID", su."UserID", u."UserCode", u."Fullname"
            FROM
			"ServiceUserMapping"  
            su join "User" u on u."UserID" = su."UserID"
            where su."BranchID" = :BranchID and su."BrandID" = :BrandID
            and u."Archived" is null
			@key
            ',
            array(
                "BrandID"=>$this->request["BrandID"],
                "BranchID"=>$this->request["BranchID"]
            )
            , "Workers");

        $this->response->Categories = $this->query('SELECT 
            "CategoryID", "CategoryCode", "CategoryName", "Image",
            coalesce("LocalID", \'local-web-\'||"CategoryID") "LocalID"
            FROM
			"Category" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null 
        ', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        ));

        foreach ($this->response->Categories as $c) {
            $this->set_image($c->Image);
        }

        $this->response->Items = $this->query('SELECT 
            "CategoryID", "ItemID", "ItemCode", "ItemName", "Image", "Price", 
            coalesce("LocalID", \'local-web-\'||"ItemID") "LocalID", "CurrentStock", 
            coalesce("CommissionPercent",0) as "CommissionPercent",
            coalesce("Discontinued",\'0\') as "Discontinued",
            coalesce("CommissionValue",0) as "CommissionValue"
            FROM
			"Item" where "BranchID" = :BranchID and "BrandID" = :BrandID
			and "Archived" is null 
        ', array(
            "BrandID"=>$this->request["BrandID"],
            "BranchID"=>$this->request["BranchID"]
        ));




        $this->render(true);
    }






	 
}