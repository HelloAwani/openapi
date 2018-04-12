<?php

namespace Service\Http\Controllers\Device\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Transaction extends _Base
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
        }
    }

    function generate_trans_number($branchid, $add = 0){

		$pmon = date('m');
		$pyear = date('y');

		$pclause = $pyear.$pmon;
		
		$n = $this->query('select COALESCE
							((MAX(substr("SalesTransactionNumber",7))::int),0) as "NextVal" from "SalesTransaction" where 
							"BranchID" = ? AND
							"SalesTransactionNumber" like \''.$pclause.'%\' 	
							', array($branchid))[0]->NextVal;

		return $pclause.str_pad(intval($n) + $add  ,6, '0', STR_PAD_LEFT);
    
    }


    public function generate_data(){

        ///
       // $howmuch = $howmuch >= 50 ? 50 : 50;
        $howmuch = 4;
        $allsals = array();
        for ($i=0; $i <$howmuch ; $i++) { 
          
          

            $startdate = date("Y-m-d H:i:s",mt_rand(1499187600,1501430400));
            $cid = mt_rand(4,7);
            $cname = $this->query('SELECT * FROM "Customer" where "CustomerID" = :id ', ["id"=>$cid])[0]->CustomerName;

            $sales = 
            [
                "ReceivedTime"=>$this->now(),
                "Date"=>@$startdate,
                "FinishedAt"=>$this->format_date($startdate, "Y-m-d H:i:s", "1 hours"),
                "BrandID"=>1,
                "BranchID"=>2,
                "Sales"=>0,
                "CustomerID"=>$cid,
                "CustomerName"=>@$cname,                
                "SalesTransactionNumber"=>@$this->generate_trans_number(2,1),
                "VATPercentage"=>@$sales_obj["VATPercentage"],
                "VAT"=>@$sales_obj["VAT"],
                "Rounding"=>0,
                "LocalID"=>uniqid("gen1_"),
                "TotalSalesTransaction"=>@$sales_obj["TotalSalesTransaction"],
                "TotalPayment"=>@$sales_obj["TotalPayment"],
                "TotalDuration"=>@$sales_obj["TotalDuration"],
                "TotalCommission"=>0,  
                "TotalDuration"=>60,
                "Changes"=>0,
                "Notes"=>@$sales_obj["Notes"],
                "UserID"=>16,
                "ClientVersion"=>@$sales_obj["ClientVersion"],
                "TransportVersion"=>@$sales_obj["TransportVersion"]
            ];
            //$sales_id = $this->upsert("SalesTransaction", $sales);
            $sales_id = 1;
            $hasitem = mt_rand(1,10) >= 8;

            $service_id = mt_rand(7,12);

            $subservices = $this->query('SELECT * FROM "SubService" where "ServiceID" = :id ', ["id"=>$service_id]);

            $how_many_ss = mt_rand(2, count($subservices));

            $ssid = array();
            foreach ($subservices as $s) {
                $ssid[$s->SubServiceID] = $s;               
            }
            
            $rand_ss = array_rand($ssid, $how_many_ss);
            
            $service_detail = array();
            foreach ($rand_ss as $k) {
                
                    $meta = $this->query('
                        select ss."SubServiceName", ss."SubServiceCode",
                        s."ServiceCode", s."ServiceName", s."ServiceID"
                        from "SubService" ss  
                        left join "Service" s on s."ServiceID" = ss."ServiceID"
                        where "SubServiceID" = :id 
                    ', ["id"=>(float)$ssid[$k]->SubServiceID]);
                  

                   $wkid = @$this->query('SELECT * FROM "ServiceUserMapping" where "SubServiceID" = :id 
                   ', [
                       "id"=>$ssid[$k]->SubServiceID
                   ])[0]->UserID;
                   $wkid = $this->coalesce($wkid, mt_rand(17,19));
                   $worker = @$this->query('SELECT * FROM "User" where "UserID" = :id ', ["id"=>(float)@$wkid])[0];



                   $service_detail[] = [
                        "SalesTransactionID"=>@$sales_id,
                        "BrandID"=>1,
                        "BranchID"=>2,
                        "Discount"=>0,
                        "SubServicePrice"=>$ssid[$k]->SubPrice,
                        "ServicePrice"=>0,
                        "SubTotal"=>$ssid[$k]->SubPrice,
                        "WorkerName"=>$this->coalesce(@$dtl["WorkerName"], @$worker->Fullname),
                        "WorkerID"=>@$wkid,
                        "SpaceID"=>mt_rand(5,12),
                        "Duration"=>60,
                        "SubServiceID"=>@$ssid[$k]->SubServiceID,
                        "StartedAt"=>@$sales["Date"],
                        "FinishedAt"=>@$sales["FinishedAt"],
                        "ServiceCode"=>$this->coalesce(@$dtl["ServiceCode"], @$meta[0]->ServiceCode),
                        "ServiceID"=>$this->coalesce(@$dtl["ServiceID"], @$meta[0]->ServiceID),
                        "ServiceName"=>$this->coalesce(@$dtl["ServiceName"], @$meta[0]->ServiceName),
                        "SubServiceName"=>$this->coalesce(@$dtl["ProductName"], @$meta[0]->SubServiceName),
                        "SubServiceCode"=>$this->coalesce(@$dtl["ProductCode"], @$meta[0]->SubServiceCode),
                        "TotalCommission"=>@$ssid[$k]->SubPrice * 0.2,
                        "CommissionPercent"=>0.2
                    ];

                    $sales["Sales"]+=$ssid[$k]->SubPrice;
                    $sales["TotalCommission"]+=$ssid[$k]->SubPrice * 0.2;
                
            }
            $item_detail = array();

            if(@$hasitem){
                $howmn = mt_rand(2,4);

                $items = $this->query('SELECT * FROM "Item" where "BranchID" = 2 ', []);

                $how_many_item = mt_rand(1, count($items));

                $ssid = array();
                foreach ($items as $s) {
                    $ssid[$s->ItemID] = $s;               
                }
                
                $rand_ii = array_rand($ssid, $howmn);

                
                foreach ($rand_ii as $k) {


                    $meta = $this->query('
                        select ss."ItemName", ss."ItemCode",
                        s."CategoryCode", s."CategoryName", s."CategoryID"
                        from "Item" ss  
                        left join "Category" s on s."CategoryID" = ss."CategoryID"
                        where "ItemID" = :id 
                    ', ["id"=>(float)@$ssid[$k]->ItemID]);

                    
                   $wkid = mt_rand(16,19);
                   $worker = @$this->query('SELECT * FROM "User" where "UserID" = :id ', ["id"=>(float)@$wkid])[0];

                    $item_detail[] = [ 
                        "SalesTransactionID"=>$sales_id,
                        "BrandID"=>1,
                        "BranchID"=>2,
                        "Qty"=>1,
                        "Price"=>@$ssid[$k]->Price,
                        "SubTotal"=>@$ssid[$k]->Price,
                        "Void"=>$this->coalesce(@$dtl["Void"], '0'),
                        "WorkerName"=>$this->coalesce(@$dtl["WorkerName"], @$worker->Fullname),
                        "WorkerID"=>@$wkid,
                        "LocalID"=>@$dtl["LocalID"],
                        "DiscountPercent"=>@$dtl["DiscountPercent"],
                        "ItemCategoryCode"=>$this->coalesce(@$dtl["ItemCategoryCode"], @$meta[0]->CategoryCode),
                        "ItemID"=>@$dtl["ProductID"],
                        "ItemName"=>$this->coalesce(@$dtl["ProductName"], @$meta[0]->ItemName),
                        "ItemCode"=>$this->coalesce(@$dtl["ProductCode"], @$meta[0]->ItemCode),
                        "ItemCategoryName"=>$this->coalesce(@$dtl["ItemCategoryName"], @$meta[0]->CategoryName),
                        "TotalCommission"=>@$ssid[$k]->Price * 0.05,
                        "CommissionPercent"=>0.05
                    ];
                    
                    $sales["Sales"]+=$ssid[$k]->Price;
                    $sales["TotalCommission"]+=$ssid[$k]->Price * 0.05;

                }
            }

            $sales["VAT"] = ((float) $sales["Sales"]) *0.1;
            $sales["VATPercentage"] = 0.1;
            $sales["TotalSalesTransaction"] = (float)$sales["Sales"] + (float)$sales["VAT"];
            $sales["TotalPayment"] = $sales["TotalSalesTransaction"];

            $payment_method = mt_rand(6,10);
            $payment_method = $payment_method == 6 ? null : $payment_method;

            $pm_name = @$this->query('SELECT * FROM "PaymentMethod" where "PaymentMethodID" = :id 
                    and "PaymentMethodID" <> 6
            ', ["id"=>$payment_method])[0]->PaymentMethodName;
            

          $payment[] = [
                "SalesTransactionID"=>$sales_id,
                "BrandID"=>1,
                "BranchID"=>2,
                "Date"=> @$sales["FinishedAt"],
                "PaymentMethodID"=> @$payment_method,
                "Payment"=> @$sales["TotalPayment"],
                "PaymentMethodName"=>$pm_name,
            ];

            $sl_i = array();
            $sl_i["ServiceDetail"] = $service_detail;
            $sl_i["ItemDetail"] = $item_detail;
            $sl_i["Payment"] = $payment;


            $sales_id = $this->upsert("SalesTransaction", $sales);

            foreach ($sl_i["ServiceDetail"] as $dtl) {
                $dtl["SalesTransactionID"] = $sales_id;
                $this->upsert("ServiceSalesTransactionDetail", $dtl);
            }


            foreach ($sl_i["ItemDetail"] as $dtl) {
                $dtl["SalesTransactionID"] = $sales_id;
                $this->upsert("ItemSalesTransactionDetail", $dtl);
            }


            foreach ($sl_i["Payment"] as $dtl) {
                $dtl["SalesTransactionID"] = $sales_id;
                $this->upsert("Payment", $dtl);
            }

            $allsals[] = $sales;




        }
        $this->end();
    }
	
	public function sales(){
        $this->validate_request();
        $rules = [
                    'LocalID' => 'required'
                ];

        $this->validator = Validator::make($this->request, $rules);
		$this->render();

        //check duplicate  
        $log["Data"] = json_encode($this->request);
        $this->upsert("DBLog", $log);
        
        $ex = $this->query('SELECT * FROM "SalesTransaction" where "LocalID" = :id ', ["id"=>$this->request["LocalID"]]);
        $localidex = false;
        if(@$ex[0]->LocalID!=null){
            $sales_id = $ex[0]->SalesTransactionID;
            $localidex = true;
            $this->custom_errors[] = $this->error("Local ID ".$this->request["LocalID"]." already exists");
            $this->status = 0;
            $this->override_validation_code = true;
        }
        
        //get request's value
        if(@$sales_id==null){
            $sales_obj = $this->request["Sales"];

            if(@$sales_obj["Customer"]!=null) {
                $c = $sales_obj["Customer"];
                $customer = [
                    "BrandID"=>@$this->request["BrandID"],
                    "BranchID"=>@$this->request["BranchID"],
                    "CustomerName"=>@$c["CustomerName"],
                    "PhoneNumber"=>@$c["PhoneNumber"],
                    "Email"=>@$c["Email"],
                    "Note"=>@$c["Note"],
                    "DOB"=>@$c["DOB"],
                    "Gender"=>@$c["Gender"],
                    "IDNumberType"=>@$c["IDNumberType"],
                    "IDNumber"=>@$c["IDNumber"],
                    "Photo"=>@$c["Photo"],
                    "CustomerCode"=>@$c["CustomerCode"],
                    "LocalID"=>@$c["LocalID"],
                    "Archived"=>@$c["Archived"],
                    "Address"=>@$c["Address"],
                ];
                $customer_id = $this->upsert("Customer", $customer, $sales_obj["CustomerID"]);
            }

            $fullusage = array();
            $fullitem = array();
            $fullitemid = array();

            $sales = 
            [
                "ReceivedTime"=>$this->now(),
                "Date"=>@$sales_obj["Date"],
                "FinishedAt"=>@$sales_obj["FinishedAt"],
                "BrandID"=>@$this->request["BrandID"],
                "BranchID"=>@$this->request["BranchID"],
                "Sales"=>@$sales_obj["Sales"],
                "CustomerID"=>$this->coalesce(@$sales_obj["CustomerID"], @$customer_id),
                "CustomerName"=>@$sales_obj["CustomerName"],
                "VATPercentage"=>@$sales_obj["VATPercentage"],
                "SalesTransactionNumber"=>@$sales_obj["SalesTransactionNumber"],
                "Void"=>@$sales_obj["Void"],
                "VoidBy"=>@$sales_obj["VoidBy"],
                "VoidDate"=>@$sales_obj["VoidDate"],
                "VoidDescription"=>@$sales_obj["VoidDescription"],
                "VAT"=>@$sales_obj["VAT"],
                "Rounding"=>@$sales_obj["Rounding"],
                "LocalID"=>$this->request["LocalID"],
                "Discount"=>$this->coalesce(@$sales_obj["Discount"],0),
                "DiscountPercent"=>@$sales_obj["DiscountPercent"],
                "DiscountID"=>@$sales_obj["DiscountID"],
                "DiscountName"=>@$sales_obj["DiscountName"],
                "TotalSalesTransaction"=>@$sales_obj["TotalSalesTransaction"],
                "TotalPayment"=>@$sales_obj["TotalPayment"],
                "TotalDuration"=>@$sales_obj["TotalDuration"],
                "TotalCommission"=>$this->coalesce(@$sales_obj["TotalCommission"],0),
                "Changes"=>@$sales_obj["Changes"],
                "Notes"=>@$sales_obj["Notes"],
                "UserID"=>@$sales_obj["UserID"],
                "ClientVersion"=>@$sales_obj["ClientVersion"],
                "TransportVersion"=>@$sales_obj["TransportVersion"]
            ];
            $discount = $this->query('SELECT * FROM "Discount" where "DiscountID" = :id ', ["id"=>(float)@$sales_obj["DiscountPercent"]]);
            $sales["DiscountName"] = $this->coalesce(@$sales["DiscountName"], @$discount[0]->DiscountName);
            $sales_id = $this->upsert("SalesTransaction", $sales);



            foreach ($sales_obj["Detail"] as $dtl) {

                $discount = @$this->query('SELECT * FROM "Discount" where "DiscountID" = :id ', ["id"=>(float)@$sales_obj["DiscountPercent"]]);
        
                switch (strtolower(trim($dtl["ProductType"]))) {
                    case 'service':
                        
                        $meta = $this->query('
                            select * from "Service"
                            where "ServiceID" = :id 
                        ', ["id"=>(float)@$dtl["ProductID"]]);

                        $service_detail = [
                            "SalesTransactionID"=>@$sales_id,
                            "BrandID"=>@$this->request["BrandID"],
                            "BranchID"=>@$this->request["BranchID"],
                            "Discount"=>$this->coalesce(@$dtl["Discount"],0),
                            "Qty"=>@$dtl["Qty"],
                            "SubServicePrice"=>@$dtl["Price"],
                            "ServicePrice"=>@$dtl["ServicePrice"],
                            "SubTotal"=>@$dtl["SubTotal"],
                            "Void"=>@$sales_obj["Void"],
                            "VoidBy"=>@$dtl["VoidBy"],
                            "VoidDate"=>@$dtl["VoidDate"],
                            "VoidDescription"=>@$dtl["VoidDescription"],
                            "WorkerName"=>$this->coalesce(@$dtl["WorkerName"], @$worker[0]->Fullname),
                            "WorkerID"=>@$dtl["WorkerID"],
                            "LocalID"=>@$dtl["LocalID"],
                            "DiscountName"=>$this->coalesce(@$sales["DiscountName"], @$discount[0]->DiscountName),
                            "DiscountID"=>@$dtl["DiscountID"],
                            "DiscountPercent"=>@$dtl["DiscountPercent"],
                            "SpaceID"=>@$dtl["SpaceID"],
                            "Duration"=>@$dtl["Duration"],
                            "ServiceID"=>@$dtl["ProductID"],
                            "StartedAt"=>@$dtl["StartedAt"],
                            "FinishedAt"=>@$dtl["FinishedAt"],
                            "ServiceCode"=>$this->coalesce(@$dtl["ProductCode"], @$meta[0]->ServiceCode),
                            "ServiceName"=>$this->coalesce(@$dtl["ProductName"], @$meta[0]->ServiceName),
                            "TotalCommission"=>$this->coalesce(@$dtl["TotalCommission"],0),
                            "CommissionPercent"=>@$dtl["CommissionPercent"],
                        ];

                        $dtlid = $this->upsert("ServiceSalesTransactionDetail", $service_detail);
                        //check usage

                        if(count($dtl["SubServices"]>0)){
                            
                            foreach ($dtl["SubServices"] as $subs) {
                                
                                $meta_sub = $this->query('
                                select ss."SubServiceName", ss."SubServiceCode",
                                s."ServiceCode", s."ServiceName", s."ServiceID"
                                from "SubService" ss  
                                left join "Service" s on s."ServiceID" = ss."ServiceID"
                                where "SubServiceID" = :id 
                                ', ["id"=>(float)@$subs["SubServiceID"]]);


                                $worker = @$this->query('SELECT * FROM "User" where "UserID" = :id ', ["id"=>(float)@$subs["WorkerID"]]);

                                $subservice_detail = [
                                    "SalesTransactionID"=>@$sales_id,
                                    "ServiceSalesTransactionDetailID"=>@$dtlid,
                                    "BrandID"=>@$this->request["BrandID"],
                                    "BranchID"=>@$this->request["BranchID"],
                                    "SubServicePrice"=>@$subs["Price"],
                                    "WorkerName"=>$this->coalesce(@$subs["WorkerName"], @$worker[0]->Fullname),
                                    "WorkerID"=>@$subs["WorkerID"],
                                    "LocalID"=>@$subs["LocalID"],
                                    "SpaceID"=>@$subs["SpaceID"],
                                    "Duration"=>@$subs["Duration"],
                                    "SubServiceID"=>@$subs["SubServiceID"],
                                    "StartedAt"=>@$subs["StartedAt"],
                                    "FinishedAt"=>@$subs["FinishedAt"],
                                    "SubServiceName"=>$this->coalesce(@$subs["SubServiceName"], @$meta_sub[0]->SubServiceName),
                                    "SubServiceCode"=>$this->coalesce(@$subs["SubServiceCode"], @$meta_sub[0]->SubServiceCode),
                                    "TotalCommission"=>$this->coalesce(@$subs["TotalCommission"],0),
                                    "CommissionPercent"=>@$dtl["CommissionPercent"],
                                ];
        
                                $subdtlid = $this->upsert("SubServiceSalesTransactionDetail", $subservice_detail);

                                
                                $usage = $this->query('SELECT * FROM "ServiceInventoryUsage" where "SubServiceID" = :id 
                                ', ["id"=>(float)@$dtl["ProductID"]]);


                                foreach ($usage as $u) {
                                    $u->RefID = $subdtlid;
                                    $u->Meta = $subservice_detail;
                                    $fullitemid[] = $u->ItemID;
                                    $fullusage[] = $u;
                                }


                            }


                        }

                        
                        break;
                    case 'item':
                        

                        $worker = @$this->query('SELECT * FROM "User" where "UserID" = :id ', ["id"=>(float)@$dtl["WorkerID"]]);
                        $meta = $this->query('
                            select ss."ItemName", ss."ItemCode",
                            s."CategoryCode", s."CategoryName", s."CategoryID"
                            from "Item" ss  
                            left join "Category" s on s."CategoryID" = ss."CategoryID"
                            where "ItemID" = :id 
                        ', ["id"=>(float)@$dtl["ProductID"]]);

                        $item_detail = [ 
                            "SalesTransactionID"=>$sales_id,
                            "BrandID"=>@$this->request["BrandID"],
                            "BranchID"=>@$this->request["BranchID"],
                            "Discount"=>$this->coalesce(@$dtl["Discount"],0),
                            "Qty"=>@$dtl["Qty"],
                            "Price"=>@$dtl["Price"],
                            "SubTotal"=>@$dtl["SubTotal"],
                            "Void"=>@$sales_obj["Void"],
                            "VoidBy"=>@$dtl["VoidBy"],
                            "VoidDate"=>@$dtl["VoidDate"],
                            "VoidDescription"=>@$dtl["VoidDescription"],
                            "WorkerName"=>$this->coalesce(@$dtl["WorkerName"], @$worker[0]->Fullname),
                            "WorkerID"=>@$dtl["WorkerID"],
                            "LocalID"=>@$dtl["LocalID"],
                            "DiscountName"=>$this->coalesce(@$sales["DiscountName"], @$discount[0]->DiscountName),
                            "DiscountID"=>@$dtl["DiscountID"],
                            "DiscountPercent"=>@$dtl["DiscountPercent"],
                            "ItemCategoryCode"=>$this->coalesce(@$dtl["ItemCategoryCode"], @$meta[0]->CategoryCode),
                            "ItemID"=>@$dtl["ProductID"],
                            "ItemName"=>$this->coalesce(@$dtl["ProductName"], @$meta[0]->ItemName),
                            "ItemCode"=>$this->coalesce(@$dtl["ProductCode"], @$meta[0]->ItemCode),
                            "ItemCategoryName"=>$this->coalesce(@$dtl["ItemCategoryName"], @$meta[0]->CategoryName),
                            "TotalCommission"=>$this->coalesce(@$dtl["TotalCommission"],0),
                            "CommissionPercent"=>@$dtl["CommissionPercent"],
                        ];

                        $isaleid = $this->upsert("ItemSalesTransactionDetail", $item_detail);

                        foreach ($dtl["StaffCommissionDetail"] as $sdt) {          
                              $uname = @$this->query('SELECT * FROM "User" where "UserID" = :id ', ["id"=>(float)@$sdt["UserID"]])[0]->Fullname;
                              $comdtl = [ 
                                "ItemSalesTransactionDetailID"=>$isaleid,
                                "BrandID"=>@$this->request["BrandID"],
                                "BranchID"=>@$this->request["BranchID"],
                                "UserFullname"=>$this->coalesce(@$sdt["UserFullname"],$uname),
                                "UserID"=>$sdt["UserID"],
                                "Commission"=>@$sdt["Commission"],
                            ];
                            $this->upsert("ItemSalesTransactionCommissionDetail", $comdtl);
                        }
                        $item_detail["RefID"] = $isaleid;
                        $fullitem[] = $item_detail;
                        $fullitemid[] = (float)@$dtl["ProductID"];

                        break;
                    
                    default:
                        # code...
                        break;
                }

            }

            foreach ($sales_obj["Payment"] as $p) {

                $pmethod = $this->query('SELECT * FROM "PaymentMethod" where "PaymentMethodID" = :id ', ["id"=>(float)@$p["PaymentMethodID"]]);

                $payment = [
                    "SalesTransactionID"=> $sales_id,
                    "BrandID"=>@$this->request["BrandID"],
                    "BranchID"=>@$this->request["BranchID"],
                    "Date"=> @$p["Date"],
                    "PaymentMethodID"=> @$p["PaymentMethodID"],
                    "Payment"=> @$p["Payment"],
                    "PaymentMethodName"=>$this->coalesce(@$p["PaymentMethodName"], @$pmethod[0]->PaymentMethodName),
                    "RefPaymentDate"=> @$p["RefPaymentDate"],
                    "RefCardNo"=> @$p["RefCardNo"],
                    "RefApproveNumber"=> @$p["RefApproveNumber"]

                ];

                $this->upsert("Payment", $payment);

            }


              //the usages

        //get the items meta
        if(count($fullitemid) > 0 ){
            $itemsmeta = $this->query('SELECT * FROM "Item" i join "Category" c on c."CategoryID" = i."CategoryID" where "ItemID" in ('.implode(',', $fullitemid).')');
            $r = array();
            foreach ($itemsmeta as $i) {
               $r[$i->ItemID] = $i;
            }
            $itemsmeta = $r;
        }

        if(count($fullitem)>0){

           $itrans = [
                "BrandID"=>@$this->request["BrandID"],
                "BranchID"=>@$this->request["BranchID"],
                "TransactionDate"=> $this->now(),
                "UserID"=> @$sales_obj["UserID"],
                "ReferenceID"=> $sales_id,
                "TransactionType"=> "Item Sales",
                "ReCalculatePrice"=> "0",
                "ReCalculateStock"=> "1",
                "RestrictDate"=> "0"
            ];
            
            $iid = $this->upsert("InventoryTransaction", $itrans);

            foreach ($fullitem as $fi) {
                $im = $itemsmeta[$fi["ItemID"]];
                $idtl = [
                    "BrandID"=>@$this->request["BrandID"],
                    "BranchID"=>@$this->request["BranchID"],
                    "InventoryTransactionID"=> @$iid,
                    "ReferenceID"=> $fi["RefID"],
                    "ReferenceFrom"=> "Item Sales Detail",
                    "ItemCode"=> $fi["ItemCode"],
                    "ItemName"=> $fi["ItemName"],
                    "CategoryCode"=> $fi["ItemCategoryCode"],
                    "CategoryName"=> $fi["ItemCategoryName"],
                    "ItemID"=> $fi["ItemID"],
                    "CategoryID"=> $im->CategoryID,
                    "Qty"=> abs($fi["Qty"])*-1,
                    //price is cogs price
                    "Price"=> $im->UseManualCOGS == "1" ? $this->coalesce($im->ManualCOGS,0) : $this->coalesce($im->COGS,0),
                    //calc later
                    "InventoryUnitTypeID"=>$im->InventoryUnitTypeID,
                    "OldStock"=>$im->CurrentStock,
                ];

                $idtl["NewStock"] = ((float)$idtl["OldStock"]) - abs((float)$idtl["Qty"]);
                $idtl["SubTotal"] = (float)$idtl["Price"] * abs((float)$idtl["Qty"]);
                $ivn = [
                    "CurrentStock" => $idtl["NewStock"]
                ];
                $this->upsert("Item", $ivn, @$im->ItemID);

                $this->upsert("InventoryTransactionDetail", $idtl);

            }
            
        }
        
        if(count($fullusage)>0){

           $itrans = [
                "BrandID"=>@$this->request["BrandID"],
                "BranchID"=>@$this->request["BranchID"],
                "TransactionDate"=> $this->now(),
                "UserID"=> @$sales_obj["UserID"],
                "ReferenceID"=> $sales_id,
                "TransactionType"=> "SubService Usages",
                "ReCalculatePrice"=> "0",
                "ReCalculateStock"=> "1",
                "RestrictDate"=> "0"
            ];
            
            $iid = $this->upsert("InventoryTransaction", $itrans);

            foreach ($fullusage as $fi) {
                $im = $itemsmeta[$fi->ItemID];
                $idtl = [
                    "BrandID"=>@$this->request["BrandID"],
                    "BranchID"=>@$this->request["BranchID"],
                    "InventoryTransactionID"=> @$iid,
                    "ReferenceID"=> $fi->RefID,
                    "ReferenceFrom"=> "Service Sales Detail",
                    "ItemCode"=> $im->ItemCode,
                    "ItemName"=> $im->ItemName,
                    "CategoryCode"=> $im->CategoryCode,
                    "CategoryName"=> $im->CategoryName,
                    "ItemID"=> $fi->ItemID,
                    "CategoryID"=> $im->CategoryID,
                    "Qty"=> abs($fi->Qty)*-1,
                    //price is cogs price
                    "Price"=> $im->UseManualCOGS == "1" ? $this->coalesce($im->ManualCOGS,0) : $this->coalesce($im->COGS,0),
                    //calc later
                    "InventoryUnitTypeID"=>$im->InventoryUnitTypeID,
                    "OldStock"=>$im->CurrentStock,
                ];

                $idtl["NewStock"] = ((float)$idtl["OldStock"]) - abs((float)$idtl["Qty"]);
                $idtl["SubTotal"] = (float)$idtl["Price"] * abs((float)$idtl["Qty"]);
                $ivn = [
                    "CurrentStock" => $idtl["NewStock"]
                ];
                $this->upsert("Item", $ivn, @$im->ItemID);

                $this->upsert("InventoryTransactionDetail", $idtl);

            }
            
        }

      

        }




        $this->response->CurrentBillSequenceNumber = $this->generate_trans_number($this->request["BranchID"]);
        $this->response->LocalIDExists = $localidex;

        $this->response->LocalID = @$this->request["LocalID"];

        $sales = $this->get_transaction('where "SalesTransactionID" = :id ', ["id"=>$sales_id]);

        $this->response->Sales = @$sales[0];

        $this->render(true);
	}

    public function daily_history(){
        $this->validate_request();
        $rules = [
                    'Date' => 'required',
                ];

        $this->validator = Validator::make($this->request, $rules);
		$this->render();

        $hour = $this->general_setting("EndOfDay")->Value;
        $start = $this->request["Date"].' '.$hour.':00';
        $end = $this->format_date($start, "Y-m-d H:i:s", "-1 seconds 1 days");

        $e = $this->query('SELECT COUNT(*) FROM "SalesTransaction" 
            where "BranchID" = :branchid and "BrandID" = :brandid and "Date" >= :start
            and "Date" < :end 
        ', [
                "branchid"=>$this->request["BranchID"],
                "brandid"=>$this->request["BrandID"],
                "start" => $start,
                "end" => $end
        ]);

        if(($e[0]->count)==0){
            if($this->Request->FromDayBefore===true){
                $this->request["Date"] = @$this->query('SELECT 
                    to_char("Date", \'yyyy-MM-dd\') as "Date" from "SalesTransaction"
                    where 
                    "BranchID" = :branchid and "BrandID" = :brandid
                    and "Date" is not null
                    order by "Date" desc limit 1
                ', [
                    "branchid"=>$this->request["BranchID"],
                    "brandid"=>$this->request["BrandID"],
                ])[0]->Date;
                $hour = $this->general_setting("EndOfDay")->Value;
                $start = $this->request["Date"].' '.$hour.':00';
                $end = $this->format_date($start, "Y-m-d H:i:s", "-1 seconds 1 days");
            }
        }
        $sales = $this->get_transaction('where "BranchID" = :branchid and "BrandID" = :brandid and "Date" >= :start
            and "Date" < :end 
            order by "Date" desc
        ', [
                "branchid"=>$this->request["BranchID"],
                "brandid"=>$this->request["BrandID"],
                "start" => $start,
                "end" => $end
        ]);

        $this->response->Transaction = $this->coalesce(@$sales, array());

        $this->render(true);
    }


    function get_transaction($object_clause,
        $object_parameter
    ){
        $object = $this->query('SELECT 
            "SalesTransactionID", "Date", "FinishedAt", "VAT", "VATPercentage", "Rounding", "TotalCommission", "Sales", "TotalSalesTransaction", "TotalPayment", "Changes", "Notes", "UserID", "Discount", 
            "Void", "VoidBy", "VoidDate", "VoidDescription", 
            "SalesTransactionNumber", "CustomerName", "CustomerID", "LocalID", "DiscountName", "DiscountID", "DiscountPercent", "VoidByType", "ClientVersion", "TransportVersion", "ReceivedTime", 
            "TotalDuration"
            FROM "SalesTransaction" '.$object_clause, $object_parameter);

        $object = $this->group_record(@$object, "CustomerID"
            , 'SELECT "CustomerID", "CustomerName", "PhoneNumber", "Email", 
                "Note", "DOB", "Gender", "IDNumberType", "IDNumber", 
                "Photo", "CustomerCode", "LocalID" from "Customer" where 1 = 1  @key
            ',
             [
             ]
            , "Customer");

         $object = $this->group_record(@$object, "SalesTransactionID"
            , ' SELECT "SalesTransactionID", "ServiceSalesTransactionDetailID" as "SalesDetailID", \'Service\' "ProductType", "SubServiceID" as "ProductID", "SubServiceCode" as "ProductCode",
                "SubServiceName" as "ProductName",
                1 "Qty", "SubServicePrice" "Price", "Duration", "LocalID", "ServicePrice",
                "Discount", "DiscountPercent", "DiscountName", "SubTotal", "WorkerID", "WorkerName", "TotalCommission",
                "CommissionPercent", "SpaceID", "StartedAt", "FinishedAt", "Void", "VoidBy", "VoidDate", "VoidDescription"
                from "ServiceSalesTransactionDetail" where 1 = 1 @key
                
                Union 

                SELECT "SalesTransactionID", "ItemSalesTransactionDetailID" as "SalesDetailID", \'Item\' "ProductType", "ItemID" as "ProductID", "ItemCode" as "ItemCode",
                "ItemName" as "ProductName",
                "Qty", "Price", null "Duration", "LocalID", null "ServicePrice",
                "Discount", "DiscountPercent", "DiscountName", "SubTotal", "WorkerID", "WorkerName", "TotalCommission",
                "CommissionPercent", null "SpaceID", null "StartedAt", null "FinishedAt", "Void", "VoidBy", "VoidDate", "VoidDescription"
                from "ItemSalesTransactionDetail" where 1 = 1 @key
            ',
             [

             ]
            , "Detail");

        //fine lahh

        foreach ($object as $o) {
            foreach ($o->Detail as $d ) {
                if($d->ProductType == 'Item'){
                    $d->StaffCommissionDetail = $this->query('SELECT  "UserID", "UserFullname", "Commission" from "ItemSalesTransactionCommissionDetail" where "ItemSalesTransactionDetailID" = :id ', 
                        ["id"=>$d->SalesDetailID]
                    );
                }
                if($d->ProductType == 'Service'){
                    $d->SubServices = $this->query('
                                        
                    select "SubServiceSalesTransactionDetailID", "SubServicePrice", "WorkerName", "WorkerID", 
                    "TotalCommission", "CommissionPercent", "SubServiceName", "SubServiceCode", "Duration", "StartedAt", "FinishedAt"
                    "LocalID", "FinishedAt"
                    from "SubServiceSalesTransactionDetail"
                    where "ServiceSalesTransactionDetailID" = :id ', 
                        ["id"=>$d->SalesDetailID]
                    );
                }
            }
        }
            
        $object = $this->group_record(@$object, "SalesTransactionID",
                'SELECT "SalesTransactionID", "PaymentMethodID", "Payment", 
                "Date", coalesce("PaymentMethodName", \'Cash\') "PaymentMethodName", "RefPaymentDate", "RefCardNo", "RefApproveNumber" from "Payment"
                where 1 = 1 @key
            ',
             [

             ]
            , "Payment");

        foreach ($object as $o) {
            $o->Customer = @$o->Customer[0];
        }

        return $object;
    }




    public function void()
    {
        $this->validate_request();
        $rules = [
                    'Date' => 'required',
                ];

        $this->validator = Validator::make($this->request, $rules);
        $this->render();
        








        
		$this->render(true);
    }







}