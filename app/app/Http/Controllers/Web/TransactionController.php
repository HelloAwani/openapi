<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use DB;

class TransactionController extends BaseController
{
    
    public function __construct(Meta $meta, Request $request){
        $this->meta = $meta;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }
    
    
    public function getHeader(){
        $input = json_decode($this->request->getContent(),true);
        $draw = $this->coalesce(@$input['Draw'],1);
        $orderBy = $this->coalesce(@$input['OrderBy'], 'Date');
        $sort = $this->coalesce(@$input['OrderDirection'], 'desc');
        $perPage = @$input['PageSize'];
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $sortableColumn = ['Date', 'SalesTransactionNumber'];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

		$total = DB::table('SalesTransaction AS st')->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Date', '>=', $input['DateFrom'])->where('st.Date', '<=', $input['DateTo'])->count();
        
        $this->give_hour($input);
        $select = [
            'Date', 
	        'SalesTransactionID',
            'SalesTransactionNumber', 
            'Void',
            'VoidDescription',
            'u.Fullname',
            'VoidByAccountID',
            DB::raw('coalesce(st."CustomerName", c."CustomerName") as "CustomerName"'),
            'TotalSalesTransaction AS Gross',
            'TotalPayment',
            'Note'
        ];
        
        $result = DB::table('SalesTransaction AS st')
            ->select($select)
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->leftJoin('User AS u','u.UserID', '=', 'st.VoidBy')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Date', '>=', $input['DateFrom'])->where('st.Date', '<=', $input['DateTo']);
        $offset = $start;
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword){
        		 $query->orWhere(DB::raw('lower(trim("SalesTransactionNumber"::varchar))'),'like','%'.strtolower($keyword).'%')
                            ->orWhere(DB::raw('lower(trim("Fullname"::varchar))'),'like','%'.strtolower($keyword).'%')
        		 			->orWhere(DB::raw('lower(trim(st."CustomerName"::varchar))'),'like','%'.strtolower($keyword).'%');
        	});	
        }
        $totalFiltered = $result->count();
        $maxPage =  $perPage==null ? 1 : ceil($totalFiltered/$perPage);
        if(!empty($orderBy)){
        	if(strtolower($sort) != 'asc' && strtolower($sort) != 'desc') $sort = 'asc';
        	$result = $result->orderBy($orderBy,$sort);
        }
        $result = $result->skip($offset);
        $result = $perPage==null ? $result->get() : $result->take($perPage)->get();
		$response = ['recordsFiltered' => $totalFiltered, 'maxPage' => $maxPage, 'data' => $result];
        
        //display account name
        $unknown_users = array();
            if(count($result) > 0){
                $unknown_users = [];
                $temp_report = [];
                foreach ($result as $key => $val) {
                    if(@$val->VoidByAccountID != null && !in_array($val->VoidByAccountID, $unknown_users)){
                        $unknown_users[] = $val->VoidByAccountID;
                        $temp_report[] = $key;
                    }
                }
		}
        $unknown_users['AccountIDs'] = $unknown_users;
		$opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/json',
                'content' => json_encode($unknown_users)
            )
        );
        $context  = stream_context_create($opts);
        $auth = file_get_contents($this->get_env('AUTH_URL')."general/get_account_name", false, $context);
        $auth = json_decode($auth,false);
        
        if(count(@$temp_report)>0){
	        foreach ($temp_report as $temp_val) {
	        	foreach($auth->Raw as $uid => $user){
	        		if($result[$temp_val]->VoidByAccountID == $uid){
	        			$result[$temp_val]->Fullname = $user; 
	        		}
	        	}
	        }
    	}       
        //end display account name
        $data = $response;
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'Data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }
    
    public function getDetail(){
        
        $input = json_decode($this->request->getContent(),true);
        $select = [
            'Date', 
            'SalesTransactionNumber', 
            'SalesTransactionID', 
            DB::raw('coalesce(st."CustomerName", c."CustomerName") as "CustomerName"'),
            'TotalSalesTransaction AS Gross',
            'TotalPayment',
            'Note',
            DB::raw('coalesce(w."Fullname", \'\') as "CashierName"'),
            DB::raw('"Sales"-"Discount"-"TotalCommission" AS "Net"'),
            "VAT",
            "VATPercentage",
            'Void',
            'VoidDescription',
            "Rounding",
            "Changes",
            "Discount"
        ];
        $result = DB::table('SalesTransaction AS st')
            ->select($select)
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->leftJoin('User AS w','w.UserID', '=', 'st.UserID')
            ->where('st.SalesTransactionID', $input['SalesTransactionID'])->get();        
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function getDetailService(){
        
        $input = json_decode($this->request->getContent(),true);
        $select = [
            "ServiceCode AS ProductCode", 
            "ServiceName AS ProductName", 
            "ServiceSalesTransactionDetailID", 
            DB::raw('\'Service\' AS "ProductType"'),
            'VoidByAccountID',
            "ServicePrice AS Price",
            "TotalCommission",
            "Void",
            'VoidDescription',
            'u.Fullname',
            'Discount',
            'SubTotal',
            DB::raw('coalesce(std."WorkerName", w."Fullname") as "WorkerName"')
        ];
        $selectSub = [
            "SubServiceCode AS ProductCode", 
            "SubServiceName AS ProductName", 
            "SubServiceSalesTransactionDetailID", 
            DB::raw('\'Sub Service\' AS "ProductType"'),
            'VoidByAccountID',
            "SubServicePrice AS Price",
            "TotalCommission",
            "Void",
            'VoidDescription',
            'u.Fullname',
            DB::raw('\'0\' AS "Discount"'),
            "SubServicePrice AS SubTotal",
            DB::raw('coalesce(std."WorkerName", w."Fullname") as "WorkerName"')
        ];
        
        $sub = DB::table('SubServiceSalesTransactionDetail AS std')
            ->select($selectSub)
            ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
            ->leftJoin('User AS u','u.UserID', '=', 'std.VoidBy')
            ->where('std.SalesTransactionID', $input['SalesTransactionID']);
        
        $result = DB::table('ServiceSalesTransactionDetail AS std')
            ->select($select)
            ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
            ->leftJoin('User AS u','u.UserID', '=', 'std.VoidBy')
            ->where('std.SalesTransactionID', $input['SalesTransactionID'])->union($sub)->get();
        
        $unknown_users = array();
            if(count($result) > 0){
                $unknown_users = [];
                $temp_report = [];
                foreach ($result as $key => $val) {
                    if($val->VoidByAccountID != null && !in_array($val->VoidByAccountID, $unknown_users)){
                        $unknown_users[] = $val->VoidByAccountID;
                        $temp_report[] = $key;
                    }
                }
		}
		$unknown_users['AccountIDs'] = $unknown_users;
		$opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/json',
                'content' => json_encode($unknown_users)
            )
        );
        $context  = stream_context_create($opts);
        $auth = file_get_contents($this->get_env('AUTH_URL')."general/get_account_name", false, $context);
        $auth = json_decode($auth,false);
        
        if(count(@$temp_report)>0){
	        foreach ($temp_report as $temp_val) {
	        	foreach($auth->Raw as $uid => $user){
	        		if($result[$temp_val]->VoidByAccountID == $uid){
	        			$result[$temp_val]->Fullname = $user; 
	        		}
	        	}
	        }
    	}       
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function getDetailItem(){
        
        $input = json_decode($this->request->getContent(),true);
        $select = [
            "ItemCategoryCode",
            "ItemCategoryName", 
            "ItemName",
            "ItemCode",
            "ItemSalesTransactionDetailID",
            'VoidByAccountID',
            "ItemCode",
            "Qty", 
            "Price", 
            'u.Fullname',
            "Discount",
            "SubTotal",
            "TotalCommission",
            "Void",
            DB::raw('coalesce(std."WorkerName", w."Fullname") as "WorkerName"'),
        ];
        $result = DB::table('ItemSalesTransactionDetail AS std')
            ->select($select)
            ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
            ->leftJoin('User AS u','u.UserID', '=', 'std.VoidBy')
            ->where('std.SalesTransactionID', $input['SalesTransactionID'])->get();    
        
        $unknown_users = array();
            if(count($result) > 0){
                $unknown_users = [];
                $temp_report = [];
                foreach ($result as $key => $val) {
                    if($val->VoidByAccountID != null && !in_array($val->VoidByAccountID, $unknown_users)){
                        $unknown_users[] = $val->VoidByAccountID;
                        $temp_report[] = $key;
                    }
                }
		}
        $unknown_users['AccountIDs'] = $unknown_users;
		$opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/json',
                'content' => json_encode($unknown_users)
            )
        );
        $context  = stream_context_create($opts);
        $auth = file_get_contents($this->get_env('AUTH_URL')."general/get_account_name", false, $context);
        $auth = json_decode($auth,false);
        
        if(count(@$temp_report)>0){
	        foreach ($temp_report as $temp_val) {
	        	foreach($auth->Raw as $uid => $user){
	        		if($result[$temp_val]->VoidByAccountID == $uid){
	        			$result[$temp_val]->Fullname = $user; 
	        		}
	        	}
	        }
    	}  
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function getDetailPayment(){
        
        $input = json_decode($this->request->getContent(),true);
        $select = [ 
            "SalesTransactionID",
            "PaymentMethodID", 
            "Payment", 
            "Date",
            DB::raw('coalesce("PaymentMethodName", \'Cash\') "PaymentMethodName"'),
        ];
        $result = DB::table('Payment')
            ->select($select)
            ->where('SalesTransactionID', $input['SalesTransactionID'])->get();        
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function VoidBill(){
        
        $input = json_decode($this->request->getContent(),true);
        $data = array(
            'VoidByAccountID' => $this->param->AccountID,
            'VoidType' => '1',
            'Void' => '1',
            'VoidDate' => $this->get_date_now(),
            'VoidDescription' => @$input['VoidDescription']
        );
        $this->db_trans_start();
        $result = DB::table('SalesTransaction')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('SalesTransactionID', @$input['SalesTransactionID'])->update($data);
        $result = DB::table('ServiceSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('SalesTransactionID', @$input['SalesTransactionID'])->update($data);
        $result = DB::table('SubServiceSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('SalesTransactionID', @$input['SalesTransactionID'])->update($data);
        $result = DB::table('ItemSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('SalesTransactionID', @$input['SalesTransactionID'])->update($data);
        $dtls = DB::table('ItemSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('SalesTransactionID', @$input['SalesTransactionID'])->get();
        $arr = array(
            "BranchID"=>$this->param->BranchID,
            "BrandID"=>$this->param->MainID,
            "VoidByAccountID"=> $this->param->AccountID,
            "TransactionType"=>"Void Transaction",
            "ReferenceID"=> $input['SalesTransactionID'],
            "ReferenceFrom"=> 'SalesTransaction',
            "ReCalculatePrice"=>"0",
            "TransactionDate"=>$this->get_date_now(),
            "RestrictDate"=>"0"
        );
        
        $readid = DB::table('SalesTransaction')->insert($data);
        $readid = $this->getLastVal();
        foreach ($dtls as $dss) {
            $vr = DB::table('Item')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ItemID', $dss->ItemID);
            if($vr->count()==0){
                //invalid
                continue;
            }
            $vr = $vr->first();
            
            $cat = DB::table('Category')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('CategoryID', $vr->CategoryID)->first();

            $darr = array(
                "BranchID"=>$this->param->BranchID,
                "BrandID"=>$this->param->MainID,
                "ItemID"=>$dss->ItemID,
                "ItemCode"=>$vr->ItemCode,
                "ItemName"=>$vr->ItemName,
                "CategoryID"=>$cat->CategoryID,
                "CategoryCode"=>$cat->CategoryCode,
                "CategoryName"=>$cat->CategoryName,
                "Qty"=>$dss->Qty,
                "Price"=>$dss->Price,
                "SubTotal"=>$dss->Qty * $dss->Price,
                "InventoryUnitTypeID"=>$vr->InventoryUnitTypeID,
                "OldStock"=>$vr->CurrentStock,
                "OldCOGS"=>$vr->COGS,
                "NewStock"=>$vr->CurrentStock + (int) $dss->Qty,
                "NewCOGS"=>$vr->COGS,
                "InventoryTransactionID"=>@$readid
            );

            $result = DB::table('InventoryTransactionDetail')->insert($darr);
            $result = DB::table('Item')->where('ItemID', $dss->ItemID )->update(array('CurrentStock' => DB::raw('coalesce("CurrentStock",0) + '.$dss->Qty)));
        }

        $this->db_trans_end();
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function VoidService(){
        $input = json_decode($this->request->getContent(),true);
        $data = array(
            'VoidByAccountID' => $this->param->AccountID,
            'VoidType' => '2',
            'Void' => '1',
            'VoidDate' => $this->get_date_now(),
            'VoidDescription' => @$input['VoidDescription']
        );
        $this->db_trans_start();
        $result = DB::table('ServiceSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ServiceSalesTransactionDetailID', @$input['ServiceSalesTransactionDetailID'])->update($data);
        $result = DB::table('SubServiceSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ServiceSalesTransactionDetailID', @$input['ServiceSalesTransactionDetailID'])->update($data);
        $transactionID = DB::table('ServiceSalesTransactionDetail')->select(['SalesTransactionID'])->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ServiceSalesTransactionDetailID', @$input['ServiceSalesTransactionDetailID'])->first()->SalesTransactionID;
        $item = DB::table('ItemSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ItemSalesTransactionDetailID',$transactionID);
        $service = DB::table('ServiceSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ServiceSalesTransactionDetailID',$transactionID);
        $total = $item->count() + $service->count();
        $totalVoid = $item->where('Void', 1)->count() + $service->where('Void', 1)->count();
        if($total > 0 && $totalVoid > 0 && $total == $totalVoid)
            $result = DB::table('SalesTransaction')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('SalesTransactionID', $transactionID)->update($data);
        $this->db_trans_end();
        $response = $this->generateResponse(0, [], "Success");
        return response()->json($response);
    }
    
    public function VoidSubService(){
        $input = json_decode($this->request->getContent(),true);
        $data = array(
            'VoidByAccountID' => $this->param->AccountID,
            'VoidType' => '2',
            'Void' => '1',
            'VoidDate' => $this->get_date_now(),
            'VoidDescription' => @$input['VoidDescription']
        );
        $this->db_trans_start();
        $result = DB::table('SubServiceSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('SubServiceSalesTransactionDetailID', @$input['SubServiceSalesTransactionDetailID'])->update($data);
        $this->db_trans_end();
        $response = $this->generateResponse(0, [], "Success");
        return response()->json($response);
    }
    
    public function VoidItem(){
        
        $input = json_decode($this->request->getContent(),true);
        $data = array(
            'VoidByAccountID' => $this->param->AccountID,
            'VoidType' => '3',
            'Void' => '1',
            'VoidDate' => $this->get_date_now(),
            'VoidDescription' => @$input['VoidDescription']
        );
        $this->db_trans_start();
        $result = DB::table('ItemSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ItemSalesTransactionDetailID', @$input['ItemSalesTransactionDetailID'])->update($data);
        
        $dtls = DB::table('ItemSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ItemSalesTransactionDetailID', @$input['ItemSalesTransactionDetailID'])->get();
        $arr = array(
            "BranchID"=>$this->param->BranchID,
            "BrandID"=>$this->param->MainID,
            "VoidByAccountID"=> $this->param->AccountID,
            "TransactionType"=>"Void Transaction",
            "ReferenceID"=> $input['ItemSalesTransactionDetailID'],
            "ReferenceFrom"=> 'ItemSalesTransactionDetail',
            "ReCalculatePrice"=>"0",
            "TransactionDate"=>$this->get_date_now(),
            "RestrictDate"=>"0"
        );
        
        $readid = DB::table('SalesTransaction')->insert($data);
        $readid = $this->getLastVal();
        foreach ($dtls as $dss) {
            $vr = DB::table('Item')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ItemID', $dss->ItemID);
            if($vr->count()==0){
                //invalid
                continue;
            }
            $vr = $vr->first();
            
            $cat = DB::table('Category')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('CategoryID', $vr->CategoryID)->first();

            $darr = array(
                "BranchID"=>$this->param->BranchID,
                "BrandID"=>$this->param->MainID,
                "ItemID"=>$dss->ItemID,
                "ItemCode"=>$vr->ItemCode,
                "ItemName"=>$vr->ItemName,
                "CategoryID"=>$cat->CategoryID,
                "CategoryCode"=>$cat->CategoryCode,
                "CategoryName"=>$cat->CategoryName,
                "Qty"=>$dss->Qty,
                "Price"=>$dss->Price,
                "SubTotal"=>$dss->Qty * $dss->Price,
                "InventoryUnitTypeID"=>$vr->InventoryUnitTypeID,
                "OldStock"=>$vr->CurrentStock,
                "OldCOGS"=>$vr->COGS,
                "NewStock"=>$vr->CurrentStock + (int) $dss->Qty,
                "NewCOGS"=>$vr->COGS,
                "InventoryTransactionID"=>@$readid
            );

            $result = DB::table('InventoryTransactionDetail')->insert($darr);
            $result = DB::table('Item')->where('ItemID', $dss->ItemID )->update(array('CurrentStock' => DB::raw('coalesce("CurrentStock",0) + '.$dss->Qty)));
        }

        
        $transactionID = DB::table('ItemSalesTransactionDetail')->select(['SalesTransactionID'])->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ItemSalesTransactionDetailID', @$input['ItemSalesTransactionDetailID'])->first()->SalesTransactionID;
        $item = DB::table('ItemSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ItemSalesTransactionDetailID',$transactionID);
        $service = DB::table('ServiceSalesTransactionDetail')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ServiceSalesTransactionDetailID',$transactionID);
        $total = $item->count() + $service->count();
        $totalVoid = $item->where('Void', 1)->count() + $service->where('Void', 1)->count();
        if($total > 0 && $totalVoid > 0 && $total == $totalVoid)
            $result = DB::table('SalesTransaction')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('SalesTransactionID', $transactionID)->update($data);
        $this->db_trans_end();
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
}
