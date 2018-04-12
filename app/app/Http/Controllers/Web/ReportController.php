<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use DB;

class ReportController extends BaseController
{
    
    public function __construct(Meta $meta, Request $request){
        $this->meta = $meta;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
        $this->input = json_decode($this->request->getContent(),true);
        if(isset($this->input['DateFrom'])){
            $this->give_hour($this->input);
        }
    }

    public function serviceCommission()
    {
        $select = [
            'st.Date',
            DB::raw('coalesce(st."CustomerName", c."CustomerName") "CustomerName"'),
            DB::raw('coalesce(nullif("ServicePrice",0), "SubTotal") as "SubTotal"'),
            'ServiceName AS ProductName', 
            'ServiceCode AS ProductCode', 
            DB::raw('\'Service\' AS "ProductType"'),
            'Duration',
            'sdt.StartedAt',
            'sdt.FinishedAt',
            'sdt.TotalCommission', 
            'CommissionPercent'
        ];
        
        $selectSub = [
            'st.Date',
            DB::raw('coalesce(st."CustomerName", c."CustomerName") "CustomerName"'),
            DB::raw('coalesce(nullif("SubServicePrice",0), "SubServicePrice") as "SubServicePrice"'),
            'SubServiceName AS ProductName', 
            'SubServiceCode AS ProductCode', 
            DB::raw('\'Sub Service\' AS "ProductType"'),
            'Duration',
            'sdt.StartedAt',
            'sdt.FinishedAt',
            'sdt.TotalCommission', 
            'CommissionPercent'
        ];
        
        $sub = DB::table('SubServiceSalesTransactionDetail AS sdt')->select($selectSub)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('sdt.Void' , '<>' , 1)
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
        $result = DB::table('ServiceSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->union($sub)->get();         
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function stockBalance(){
        $select = array(
            'iut.*',
            'c.CategoryName',
            'i.ItemName',
            'i.ItemCode',
            'i.CategoryID',
            'COGS',
            'CurrentStock',
            'UseManualCOGS',
            'ManualCOGS'
        );
        $result = DB::table('Item AS i')->select($select)
            ->join('InventoryUnitType AS iut', 'iut.InventoryUnitTypeID', '=', 'i.InventoryUnitTypeID')
            ->join('Category AS c', 'c.CategoryID', '=', 'i.CategoryID')
            ->where('i.Archived', null)->where('c.Archived', null)
            ->where('i.BranchID', $this->param->BranchID)->where('i.BrandID', $this->param->MainID)
            ->orderBy('i.ItemCode')->get();  
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function itemCommission()
    {
        $select = [
            'st.Date',
            DB::raw('coalesce(st."CustomerName", c."CustomerName") "CustomerName"'),
            'sdt.Qty',
            'sdt.Price', 
            'sdt.SubTotal',
            'ItemCategoryName', 
            'ItemCategoryCode', 
            'ItemName',
            'ItemCode',
            'sdt.TotalCommission', 
            'CommissionPercent'];
        $result = DB::table('ItemSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get();       
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    
    public function dashboard()
    {
        
        $select = [
            DB::raw('coalesce(sum(st."TotalSalesTransaction"), 0) "Gross"'),
            DB::raw('coalesce(sum(st."Sales" - coalesce("Discount", 0)), 0) "Net"'),
            DB::raw('coalesce(count(*), 0) "Avg"'),
            DB::raw('coalesce(sum("TotalSalesTransaction")/count(*), 0) "AvgTransaction"')
        ];
        
        $result = DB::table('SalesTransaction AS st')->select($select)
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first();         
        return $result;
    }
    
    public function pie1(){
        $select = [
            'ItemName',
            DB::raw('sum(sdt."Qty") as "Qty"')
        ];
        $result = DB::table('ItemSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'))->orderBy(DB::raw('2'), 'desc')->limit(10)->get();         
        return $result;
    }
    
    public function pie2(){
        $select = [
            'ItemName',
            DB::raw('sum(sdt."SubTotal") as "SubTotal"')
        ];
        $result = DB::table('ItemSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'))->orderBy(DB::raw('2'), 'desc')->limit(10)->get();     
        return $result;
    }
    
    
    public function topItemByQty(){
        $selectService = [
            'ServiceName AS ItemName',
            DB::raw('count(*) as "Qty"')
        ];
        
        $selectItem = [
            'ItemName',
            DB::raw('sum(sdt."Qty") as "Qty"')
        ];
        
        $result = DB::table('ItemSalesTransactionDetail AS sdt')->select($selectItem)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'));
        
        $result = DB::table('ServiceSalesTransactionDetail AS sdt')->select($selectService)->union($result)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'))->orderBy(DB::raw('2'), 'desc')->limit(10)->get();  
        return $result;
    }
    
    public function topItemByAmount(){
        $selectService = [
            'ServiceName AS ItemName',
            DB::raw('sum(sdt."SubTotal") as "SubTotal"')
        ];
        
        $selectItem = [
            'ItemName',
            DB::raw('sum(sdt."SubTotal") as "SubTotal"')
        ];
        
        $result = DB::table('ItemSalesTransactionDetail AS sdt')->select($selectItem)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'));
        
        $result = DB::table('ServiceSalesTransactionDetail AS sdt')->select($selectService)->union($result)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'))->orderBy(DB::raw('2'), 'desc')->limit(10)->get();  
        return $result;
    }
    
    public function pie3(){
        $select = [
            'ServiceName',
            DB::raw('count(*) as "Qty"')
        ];
        $result = DB::table('ServiceSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'))->orderBy(DB::raw('2'), 'desc')->limit(10)->get();         
        return $result;
    }
    
    public function pie4(){
        $select = [
            'ServiceName',
            DB::raw('sum(sdt."SubTotal") as "SubTotal"')
        ];
        $result = DB::table('ServiceSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'))->orderBy(DB::raw('2'), 'desc')->limit(10)->get();         
        return $result;
    }
    
    public function pie5(){
        $select = [
            DB::raw('coalesce(c."CustomerName", st."CustomerName") "CustomerName"'),
            DB::raw('count(*) as "Qty"')
        ];
        $result = DB::table('ItemSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'))->orderBy(DB::raw('2'), 'desc')->limit(10)->get();         
        return $result;
    }
    
    public function pie6(){
        $select = [
            DB::raw('coalesce(c."CustomerName", st."CustomerName") "CustomerName"'),
            DB::raw('sum(sdt."SubTotal") as "SubTotal"')
        ];
        $result = DB::table('ItemSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'))->orderBy(DB::raw('2'), 'desc')->limit(10)->get();    
        return $result;
    }
    
    public function pie7(){
        $selectItem = [
            'UserCode AS StaffCode',
            DB::raw('coalesce("WorkerName", "Fullname") AS "StaffName"'),
            DB::raw('sum(sdtc."Commission") "TotalCommission"')
        ];
        
        $selectService = [
            'UserCode AS StaffCode',
            DB::raw('coalesce("WorkerName", "Fullname") AS "StaffName"'),
            DB::raw('sum(std."TotalCommission") "TotalCommission"')
        ];
        
        $selectSubService = [
            'UserCode AS StaffCode',
            DB::raw('coalesce("WorkerName", "Fullname") AS "StaffName"'),
            DB::raw('sum(std."TotalCommission") "TotalCommission"')
        ];
        $select = [
            'StaffCode',
            "StaffName",
            DB::raw('sum("TotalCommission") "TotalCommission"')
        ];
        
        $item = DB::table('ItemSalesTransactionCommissionDetail AS sdtc')->select($selectItem)
            ->join('ItemSalesTransactionDetail AS std','sdtc.ItemSalesTransactionDetailID', '=', 'std.ItemSalesTransactionDetailID')
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->join('User AS w','w.UserID', '=', 'sdtc.UserID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('std.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1,2'));
        
        $subService = DB::table('SubServiceSalesTransactionDetail AS std')->select($selectSubService)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->join('User AS w','w.UserID', '=', 'std.WorkerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('std.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1,2'));
        
        $service = DB::table('ServiceSalesTransactionDetail AS std')->union($item)->union($subService)->select($selectService)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->join('User AS w','w.UserID', '=', 'std.WorkerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('std.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1,2'))->orderBy(DB::raw('3'), 'desc')->limit(10);
        
        $result = DB::table( DB::raw("({$service->toSql()}) as sub") )
        ->addBinding($service->getBindings())
        ->select($select)->groupBy(DB::raw('1,2'))->get();
        
        return $result;
    }
    
	function bill_cycle_clause($cycle){
		switch ($cycle) {
			case '1':
				return 'to_char("SalesTransaction"."Date", \'YYYY-MM-DD\') as "Cycle", to_char("SalesTransaction"."Date", \'DD-Mon-YYYY\') as "CycleName" ';
				
			case '2':
				return 'to_char("SalesTransaction"."Date", \'YYYY-MM-W\') as "Cycle", to_char("SalesTransaction"."Date", \'YYYY Mon "Week" W\') as "CycleName"' ;
				
			case '3':
				return 'to_char("SalesTransaction"."Date", \'YYYY-MM\') as "Cycle", to_char("SalesTransaction"."Date", \'Mon-YYYY\') as "CycleName"';
				
			case '4':
				return 'to_char("SalesTransaction"."Date", \'YYYY\') as "Cycle", to_char("SalesTransaction"."Date", \'YYYY\') as "CycleName"';
				
			default:
				# code...
				break;
		}
	}
    
	function cycle_clause($cycle){
		switch ($cycle) {
			case '1':
				return 'to_char("SalesTransaction"."Date", \'YYYY-MM-DD\') as "Cycle", to_char("SalesTransaction"."Date", \'DD-Mon-YYYY\') as "CycleName" ';
				
			case '2':
				return 'to_char("SalesTransaction"."Date", \'YYYY-MM-W\') as "Cycle", to_char("SalesTransaction"."Date", \'YYYY Mon "Week" W\') as "CycleName"' ;
				
			case '3':
				return 'to_char("SalesTransaction"."Date", \'YYYY-MM\') as "Cycle", to_char("SalesTransaction"."Date", \'Mon-YYYY\') as "CycleName"';
				
			case '4':
				return 'to_char("SalesTransaction"."Date", \'YYYY\') as "Cycle", to_char("SalesTransaction"."Date", \'YYYY\') as "CycleName"';
				
			default:
				# code...
				break;
		}
	}
    
    
	function bill_cycle_clause_simple($cycle){
		switch ($cycle) {
			case '1':
				return 'to_char("SalesTransaction"."Date", \'YYYY-MM-DD\') as "Cycle", to_char("SalesTransaction"."Date", \'DD/MM\') as "CycleName" ';
				
			case '2':
				return 'to_char("SalesTransaction"."Date", \'YYYY-MM-W\') as "Cycle", to_char("SalesTransaction"."Date", \'YYYY Mon "Week" W\') as "CycleName"' ;
				
			case '3':
				return 'to_char("SalesTransaction"."Date", \'YYYY-MM\') as "Cycle", to_char("SalesTransaction"."Date", \'Mon-YYYY\') as "CycleName"';
				
			case '4':
				return 'to_char("SalesTransaction"."Date", \'YYYY\') as "Cycle", to_char("SalesTransaction"."Date", \'YYYY\') as "CycleName"';
				
			default:
				# code...
				break;
		}
	}
    
    public function matrix(){
		$cycle = $this->bill_cycle_clause(@$this->input['Cycle']);
        
//        $cycle = explode(', t', $cycle);
//        $cycle[1] = 't'.$cycle[1];
        $select = array(
            DB::raw($cycle),
            DB::raw('to_char("SalesTransaction"."Date", \'HH24\') as "Hour"'),
            DB::raw('sum("SalesTransaction"."TotalSalesTransaction") as "Sales"')
        );
		$revmatrix = DB::table('SalesTransaction')->select($select)
            ->where('Date', '>', @$this->input['DateFrom'])
            ->where('Date', '<', @$this->input['DateTo'])
            ->where('SalesTransaction.BranchID', $this->param->BranchID)
            ->where('Void', '<>', 1)
            ->groupBy(DB::raw('"Cycle", "CycleName", "Hour"'))
            ->orderBy(DB::raw('"Cycle", "Hour"'))
            ->get();
		$son = array();
		$max = 0;
		foreach ($revmatrix as $d) {
			$son[$d->CycleName][$d->Hour] = $d->Sales;
			if($d->Sales>$max){
				$max = $d->Sales;
			}
		}
		$salescooked = array();
		$cycle = $this->cycle_clause(@$this->input['Cycle']);
		foreach ($son as $key => $value) {
			$dt = array();
			$dt["Cycle"] = $key;
			for($i=0; $i<=23; $i++){
				$dt["H$i"] = @$value[sprintf('%02d', $i)] == null ? "0" : @$value[sprintf('%02d', $i)];
				if($dt["H$i"]=="0"){
					$dt["D$i"] = "0";
				}else{
					$den = ($dt["H$i"]*100)/$max;			
					if($den<=1){
						$den = 1;
					}	
					$dt["D$i"] = "$den";
				}
			}
			$salescooked[]=$dt;
		}
        
        $select = array(
            DB::raw($cycle),
            DB::raw('to_char("SalesTransaction"."Date", \'HH24\') as "Hour"'),
            DB::raw('count("SalesTransaction"."SalesTransactionID") as "Visitor"')
        );
        $vismatrix = DB::table('SalesTransaction')->select($select)
            ->where('Date', '>', @$this->input['DateFrom'])
            ->where('Date', '<', @$this->input['DateTo'])
            ->where('SalesTransaction.BranchID', $this->param->BranchID)
            ->groupBy(DB::raw('"Cycle", "CycleName", "Hour"'))
            ->orderBy(DB::raw('"Cycle", "Hour"'))
            ->get();
		$son = array();
		$max = 0;
		foreach ($vismatrix as $d) {
			$son[$d->CycleName][$d->Hour] = $d->Visitor;
			if($d->Visitor>$max){
				$max = $d->Visitor;
			}
		}
		$viscooked = array();
		foreach ($son as $key => $value) {
			$dt = array();
			$dt["Cycle"] = $key;
			for($i=0; $i<=23; $i++){
				$dt["H$i"] = @$value[sprintf('%02d', $i)] == null ? "0" : @$value[sprintf('%02d', $i)];
				
				if($dt["H$i"]=="0"){
					$dt["D$i"] = "0";
				}else{
					$den = ($dt["H$i"]*100)/$max;				
					$dt["D$i"] = "$den";
				}
			}
			$viscooked[]=$dt;
		}
		$json["VisitorMatrix"] = $viscooked;
		$json["Sales"] = $salescooked;
		$cycle = $this->bill_cycle_clause_simple(@$this->input['Cycle']);
        
        $select = array(
            DB::raw($cycle),
            DB::raw('sum("SalesTransaction"."Sales") as "Sales"'),
            DB::raw('sum("SalesTransaction"."TotalSalesTransaction") as "TotalSales"'),
            DB::raw('count("SalesTransaction"."SalesTransactionID") as "TotalTransactions"')
        );
        $json["SalesGraph"] = DB::table('SalesTransaction')->select($select)
            ->where('Date', '>', @$this->input['DateFrom'])
            ->where('Date', '<', @$this->input['DateTo'])
            ->where('SalesTransaction.BranchID', $this->param->BranchID)
            ->groupBy(DB::raw('"Cycle", "CycleName"'))
            ->orderBy(DB::raw('"Cycle"'))
            ->get();
        
        $select = array(
            DB::raw($cycle),
            DB::raw('sum("Payment"."Payment") as "TotalSales"'),
            DB::raw('coalesce("Payment"."PaymentMethodName", \'Cash\') as "PaymentMethodName"'),
            DB::raw('count("SalesTransaction"."SalesTransactionID") as "TotalTransactions"')
        );
        
        $paymentDate = DB::table('SalesTransaction')->select(array(DB::raw($cycle)))
            ->rightJoin('Payment', 'Payment.SalesTransactionID', '=', 'SalesTransaction.SalesTransactionID')
            ->where('SalesTransaction.Date', '>=', @$this->input['DateFrom'])
            ->where('SalesTransaction.Date', '<=', @$this->input['DateTo'])
            ->where('SalesTransaction.BranchID', $this->param->BranchID)
            ->orderBy(DB::raw('"Cycle"'))
            ->distinct()
            ->get();
        
        $paymentMethod = DB::table('SalesTransaction')->select(array(DB::raw('coalesce("Payment"."PaymentMethodName", \'Cash\') as "PaymentMethodName"')))
            ->rightJoin('Payment', 'Payment.SalesTransactionID', '=', 'SalesTransaction.SalesTransactionID')
            ->where('SalesTransaction.Date', '>=', @$this->input['DateFrom'])
            ->where('SalesTransaction.Date', '<=', @$this->input['DateTo'])
            ->where('SalesTransaction.BranchID', $this->param->BranchID)
            ->orderBy(DB::raw('"PaymentMethodName"'))
            ->distinct()
            ->get();
		$graph = DB::table('SalesTransaction')->select($select)
            ->rightJoin('Payment', 'Payment.SalesTransactionID', '=', 'SalesTransaction.SalesTransactionID')
            ->where('SalesTransaction.Date', '>=', @$this->input['DateFrom'])
            ->where('SalesTransaction.Date', '<=', @$this->input['DateTo'])
            ->where('SalesTransaction.BranchID', $this->param->BranchID)
            ->groupBy(DB::raw('"Cycle", "CycleName", "PaymentMethodName"'))
            ->orderBy(DB::raw('"Cycle", "PaymentMethodName"'))
            ->get();
        $tempGraph = array();
        $finalGraph = array();
        for($i = 0; $i < count($graph); $i++){
            $g = $graph[$i];
            $tempGraph[$g->CycleName][$g->PaymentMethodName]['TotalSales'] = $g->TotalSales;
            $tempGraph[$g->CycleName][$g->PaymentMethodName]['TotalTransactions'] = $g->TotalTransactions;
        }
        $json["PaymentGraph"] = array();
        for($j = 0; $j < count($paymentMethod); $j++){
            $m = $paymentMethod[$j];
            $json["PaymentGraph"][$j]['PaymentMethodName'] = $m->PaymentMethodName;
            
            for($i = 0; $i < count($paymentDate); $i++){
                $d = $paymentDate[$i];
                $json["PaymentGraph"][$j]['Graph'][] = array(
                    'Cycle' => $d->Cycle,
                    'CycleName' => $d->CycleName,
                    'TotalSales' => $this->coalesce(@$tempGraph[$d->CycleName][$m->PaymentMethodName]['TotalSales'], '0'),
                    'TotalTransactions' => $this->coalesce(@$tempGraph[$d->CycleName][$m->PaymentMethodName]['TotalTransactions'], '0')
                );
                
            }
        }
        
        $response = $this->generateResponse(0, [], "Success", ['Data' => $json]);
        return response()->json($response);
        
	}
    
    public function mergeDashboard(){
        
        $data = array(
            'Figure' => $this->dashboard(),
            'TopItemByQty' => $this->topItemByQty(),
            'TopItemByAmount' => $this->topItemByAmount(),
            'TopPaymentByQty' => $this->topPaymentByQty(),
            'TopPaymentByAmount' => $this->topPaymentByAmount(),
            'Pie1' => $this->pie1(),
            'Pie2' => $this->pie2(),
            'Pie3' => $this->pie3(),
            'Pie4' => $this->pie4(),
            'Pie5' => $this->pie5(),
            'Pie6' => $this->pie6(),
            'Pie7' => $this->pie7()
        );
        $response = $this->generateResponse(0, [], "Success", ['Data' => $data]);
        return response()->json($response);
    }
    
    public function topPaymentByQty(){
        
        $select = [
            DB::raw('coalesce("PaymentMethodName", \'Cash\') "PaymentMethodName"'),
            DB::raw('count(*) as "Qty"')
        ];
        
        $result = DB::table('Payment AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('sdt.BranchID', $this->param->BranchID)->where('sdt.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'))->orderBy(DB::raw('2'), 'desc')->limit(10)->get();         
        return $result;
    }
    public function topPaymentByAmount(){
        
        $select = [
            DB::raw('coalesce("PaymentMethodName", \'Cash\') "PaymentMethodName"'),
            DB::raw('sum("Payment") as "Amount"')
        ];
        
        $result = DB::table('Payment AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('sdt.BranchID', $this->param->BranchID)->where('sdt.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1'))->orderBy(DB::raw('2'), 'desc')->limit(10)->get();         
        return $result;
    }
    
    public function profitLoss(){
        $data['ServiceSales'] = DB::table('ServiceSalesTransactionDetail AS sdt')
            ->select(DB::raw('coalesce(sum(coalesce(sdt."SubTotal",0)), 0) "ServicePrice"'))
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first()->ServicePrice;
        
        $data['SubServiceSales'] = DB::table('SubServiceSalesTransactionDetail AS sdt')
            ->select(DB::raw('coalesce(sum(coalesce(sdt."SubServicePrice",0)), 0) "SubServicePrice"'))
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first()->SubServicePrice;  
        
        $data['ItemSales'] = DB::table('ItemSalesTransactionDetail AS sdt')
            ->select(DB::raw('coalesce(sum(coalesce(sdt."SubTotal",0)), 0) "SubTotal"'))
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first()->SubTotal;   
        
        $data['ServiceDiscount'] = DB::table('ServiceSalesTransactionDetail AS sdt')
            ->select(DB::raw('coalesce(sum(coalesce(sdt."Discount",0)), 0) "Discount"'))
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first()->Discount;  
        $data['ItemDiscount'] = DB::table('ItemSalesTransactionDetail AS sdt')
            ->select(DB::raw('coalesce(sum(coalesce(sdt."Discount",0)), 0) "Discount"'))
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first()->Discount;   
        
        $data['ServiceCOGS'] = DB::table('InventoryTransactionDetail AS itd')->select(DB::raw('coalesce(sum(itd."SubTotal"), 0) AS "Total"'))
            ->join('ServiceSalesTransactionDetail AS sstd', 'sstd.ServiceSalesTransactionDetailID', '=', 'itd.ReferenceID')
            ->join('InventoryTransaction AS it', 'it.InventoryTransactionID', '=', 'itd.InventoryTransactionID')
            ->where('itd.BranchID', $this->param->BranchID)->where('itd.BrandID',  $this->param->MainID)
            ->where('sstd.Void' , '<>' , 1)->where('itd.ReferenceFrom', 'Service Sales Detail')
            ->where('it.TransactionDate', '>=', $this->input['DateFrom'])->where('it.TransactionDate', '<=', $this->input['DateTo'])->get()->first()->Total;
        
        $data['ItemCOGS'] = DB::table('InventoryTransactionDetail AS itd')->select(DB::raw('coalesce(sum(itd."SubTotal"), 0) AS "Total"'))
            ->join('ItemSalesTransactionDetail AS sstd', 'sstd.ItemSalesTransactionDetailID', '=', 'itd.ReferenceID')
            ->join('InventoryTransaction AS it', 'it.InventoryTransactionID', '=', 'itd.InventoryTransactionID')
            ->where('itd.BranchID', $this->param->BranchID)->where('itd.BrandID',  $this->param->MainID)
            ->where('sstd.Void' , '<>' , 1)->where('itd.ReferenceFrom', 'Item Sales Detail')
            ->where('it.TransactionDate', '>=', $this->input['DateFrom'])->where('it.TransactionDate', '<=', $this->input['DateTo'])->get()->first()->Total;
        
        $data['ServiceCommission'] = DB::table('ServiceSalesTransactionDetail AS sdt')
            ->select(DB::raw('coalesce(sum(coalesce(sdt."TotalCommission",0)), 0) "TotalCommission"'))
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first()->TotalCommission; 
        
        $data['SubServiceCommission'] = DB::table('SubServiceSalesTransactionDetail AS sdt')
            ->select(DB::raw('coalesce(sum(coalesce(sdt."TotalCommission",0)), 0) "TotalCommission"'))
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first()->TotalCommission; 
        
        $data['ItemCommission'] = DB::table('ItemSalesTransactionDetail AS sdt')
            ->select(DB::raw('coalesce(sum(coalesce(sdt."TotalCommission",0)), 0) "TotalCommission"'))
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first()->TotalCommission; 
        
        $data['TotalTax'] = DB::table('SalesTransaction AS st')
            ->select(DB::raw('coalesce(sum(coalesce(st."VAT",0)) ,0 ) "VAT"'))
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first()->VAT; 
        
        $data['TotalDiscount'] = DB::table('SalesTransaction AS st')
            ->select(DB::raw('coalesce(sum(coalesce(st."Discount",0)), 0) "Discount"'))
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first()->Discount; 
        
        $data['Rounding'] = DB::table('ItemSalesTransactionDetail AS sdt')
            ->select(DB::raw('coalesce(sum(coalesce(st."Rounding",0)), 0) "Rounding"'))
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('sdt.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get()->first()->Rounding; 
        $data['Expense'] = DB::table('Expense AS e')->select(DB::raw('sum(e."Amount") AS "Amount"'))
            ->join('ExpenseType AS et', 'et.ExpenseTypeID', '=', 'e.ExpenseTypeID')
            ->where('e.BranchID', $this->param->BranchID)->where('e.BrandID',  $this->param->MainID)
            ->where('e.Archived', null)->where('et.Archived', null)->get()->first()->Amount;
        
        $data['Expenses'] = DB::table('Expense AS e')->select(['ExpenseTypeName', DB::raw('sum(e."Amount") AS "Amount"')])
            ->join('ExpenseType AS et', 'et.ExpenseTypeID', '=', 'e.ExpenseTypeID')
            ->where('e.BranchID', $this->param->BranchID)->where('e.BrandID',  $this->param->MainID)
            ->where('e.Archived', null)->where('et.Archived', null)
            ->where('e.Date', '>=', $this->input['DateFrom'])->where('e.Date', '<=', $this->input['DateTo'])
            ->groupBy('ExpenseTypeName')
            ->get();
        
        $data['SubTotal'] = (int)$data['ServiceSales'] + (int)$data['SubServiceSales'] + (int)$data['ItemSales'] - (int)($data['ServiceDiscount'] + $data['ItemDiscount'] + $data['ServiceCommission'] +$data['SubServiceCommission'] + $data['ItemCommission'] + $data['Expense']);
        $data['Gross'] = $data['SubTotal'] + $data['TotalTax'] - $data['TotalDiscount'] + $data['Rounding'];
        $data['Net'] = $data['Gross'] - $data['TotalTax'];
        
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$data]);
        return response()->json($response);
    }
    
    
    
    public function dailySales(){
        $select = [
            DB::raw('sum("TotalSalesTransaction") "Gross"'),
            DB::raw('to_char("Date", \'yyyy-MM-dd\') "Date"'),
            DB::raw('Count(*) "TotalTransaction"'),
            DB::raw('sum("VAT") "VAT"'),
            DB::raw('sum("TotalCommission") "TotalCommission"'),
            DB::raw('sum("Discount") "Discount"'),
            DB::raw('sum("Sales" - coalesce("Discount", 0)) "Net", sum("Sales"- coalesce("Discount", 0)) - sum("TotalCommission") AS "Revenue"')
        ];
        
        $result = DB::table('SalesTransaction AS st')->select($select)
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void', '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('2'))->orderBy(DB::raw('2'))->get();        
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function commission(){
        $selectItem = [
            "Fullname", 
            "UserCode", 
            "sdtc.UserID", 
            DB::raw(' 0 "ServiceCommission"'),
            DB::raw('sum(sdtc."Commission") "ItemCommission"'),
            DB::raw('sum(sdtc."Commission") "TotalCommission"')
        ];
        
        $selectService = [
            "Fullname",  
            "UserCode", 
            "sdt.WorkerID AS UserID",
            DB::raw('sum(sdt."TotalCommission") "ServiceCommission"'),
            DB::raw(' 0 "ItemCommission"'),
            DB::raw('sum(sdt."TotalCommission") "TotalCommission"')
        ];
        
        $selectSubService = [
            "Fullname",  
            "UserCode", 
            "sdt.WorkerID AS UserID",
            DB::raw('sum(sdt."TotalCommission") "ServiceCommission"'),
            DB::raw(' 0 "ItemCommission"'),
            DB::raw('sum(sdt."TotalCommission") "TotalCommission"')
        ];
        
        $select = [
            "Fullname", 
            "UserCode", 
            "UserID", 
            DB::raw('sum("ItemCommission") "ItemCommission"'),
            DB::raw('sum("ServiceCommission") "ServiceCommission"'),
            DB::raw('sum("TotalCommission") "TotalCommission"')
        ];
        $item = DB::table('ItemSalesTransactionCommissionDetail AS sdtc')->select($selectItem)
            ->leftJoin('ItemSalesTransactionDetail AS sdt','sdtc.ItemSalesTransactionDetailID', '=', 'sdt.ItemSalesTransactionDetailID')
            ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('User AS w','w.UserID', '=', 'sdtc.UserID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void', '<>' , 1)->where('sdt.Void', '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('2, 1, 3'));    
        
        $subService = DB::table('SubServiceSalesTransactionDetail AS sdt')->select($selectSubService)
            ->leftJoin('User AS w','w.UserID', '=', 'sdt.WorkerID')
            ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('sdt.Void', null)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('2, 1, 3'));
        
        $service = DB::table('ServiceSalesTransactionDetail AS sdt')->select($selectService)
            ->leftJoin('User AS w','w.UserID', '=', 'sdt.WorkerID')
            ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void', '<>' , 1)->where('sdt.Void', '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('2, 1, 3'))->union($item)->union($subService); 
        $result = DB::table( DB::raw("({$service->toSql()}) as sub") )
        ->addBinding($service->getBindings())
        ->select($select)->groupBy(DB::raw('2,1,3'))->get();

        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    
    public function commissionBreakdownService(){
        $select = [
            'ServiceCode AS ProductCode',
            'ServiceName AS ProductName',
            DB::raw('\'Service\' AS "ProductType"'),
            DB::raw('coalesce(std."ServicePrice",0) "ProductPrice"'),
            DB::raw('sum(std."TotalCommission") "TotalCommission"'),
            DB::raw('count(*) AS "Qty"'),
            DB::raw('sum(std."ServicePrice") "SubTotal"')
        ];
        
        $selectSub = [
            'SubServiceCode AS ProductCode',
            'SubServiceName AS ProductName',
            DB::raw('\'Sub Service\' AS "ProductType"'),
            DB::raw('coalesce(std."SubServicePrice",0) "ProductPrice"'),
            DB::raw('sum(std."TotalCommission") "TotalCommission"'),
            DB::raw('count(*) AS "Qty"'),
            DB::raw('sum(std."SubServicePrice") "SubTotal"')
        ];
        
        $sub = DB::table('SubServiceSalesTransactionDetail AS std')->select($selectSub)
            ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('std.Void' , null)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
        
        if(@$this->input['UserID'] != null)
            $sub = $sub->where('std.WorkerID', @$this->input['UserID']);
        $sub = $sub->groupBy(DB::raw('1,2,3,4'));
        
        $result = DB::table('ServiceSalesTransactionDetail AS std')->select($select)->union($sub)
            ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('std.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
        
        if(@$this->input['UserID'] != null)
            $result = $result->where('std.WorkerID', @$this->input['UserID']);
        
        
        $result = $result->groupBy(DB::raw('1,2,3,4'))->orderBy(DB::raw('1,2'))->get();
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function commissionBreakdownItem(){
        $select = [
            'ItemCategoryCode',
            'ItemCategoryName',
            'ItemName',
            'ItemCode',
            DB::raw('coalesce(std."Price",0) "ItemPrice"'),
            DB::raw('sum(sdtc."Commission") "TotalCommission"'),
            DB::raw('sum("Qty") AS "Qty"'),
            DB::raw('sum(std."SubTotal") "SubTotal"')
        ];
        
        $result = DB::table('ItemSalesTransactionCommissionDetail AS sdtc')->select($select)
            ->join('ItemSalesTransactionDetail AS std','sdtc.ItemSalesTransactionDetailID', '=', 'std.ItemSalesTransactionDetailID')
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('std.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
        if(@$this->input['UserID'] != null)
            $result = $result->where('sdtc.UserID', @$this->input['UserID']);
        
        $result = $result->groupBy(DB::raw('1,2,3,4,5'))->orderBy(DB::raw('1,2'))->get();
        
        $data = array();
        $i = 0;
        foreach($result as $d){
            if(count($data) <=0){
                $data[0]['CategoryCode'] = $d->ItemCategoryCode;
                $data[0]['CategoryName'] = $d->ItemCategoryName;
                $data[0]['Detail'][0] = array(
                    'ItemName' => $d->ItemName,
                    'ItemCode' => $d->ItemCode,
                    'ItemPrice' => $d->ItemPrice,
                    'Qty' => $d->Qty,
                    'SubTotal' => $d->SubTotal,
                    'TotalCommission' => $d->TotalCommission
                );
            } else if ($d->CategoryCode == $data[$i]['CategoryCode']){
                $data[$i]['CategoryCode'] = $d->ItemCategoryCode;
                $data[$i]['CategoryName'] = $d->ItemCategoryName;
                $data[$i]['Detail'][] = array(
                    'ItemName' => $d->ItemName,
                    'ItemCode' => $d->ItemCode,
                    'ItemPrice' => $d->ItemPrice,
                    'Qty' => $d->Qty,
                    'SubTotal' => $d->SubTotal,
                    'TotalCommission' => $d->TotalCommission
                );
            } else {
                $i += 1;
                $data[$i]['CategoryCode'] = $d->ItemCategoryCode;
                $data[$i]['CategoryName'] = $d->ItemCategoryName;
                $data[$i]['Detail'][] = array(
                    'ItemName' => $d->ItemName,
                    'ItemCode' => $d->ItemCode,
                    'ItemPrice' => $d->ItemPrice,
                    'Qty' => $d->Qty,
                    'SubTotal' => $d->SubTotal,
                    'TotalCommission' => $d->TotalCommission
                );
            }
        }
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$data]);
        return response()->json($response);
    }
    
    public function commissionBreakdownStaff(){
        $selectItem = [
            'UserCode AS StaffCode',
            DB::raw('coalesce("WorkerName", "Fullname") AS "StaffName"'),
            DB::raw('\'Item\' AS "ProductType"'),
            'ItemName AS ProductName',
            'ItemCode AS ProductCode',
            DB::raw('coalesce(std."Price",0) "ProductPrice"'),
            DB::raw('sum(sdtc."Commission") "TotalCommission"'),
            DB::raw('sum("Qty") "Qty"'),
            DB::raw('sum(std."SubTotal") "SubTotal"')
        ];
        
        $selectService = [
            'UserCode AS StaffCode',
            DB::raw('coalesce("WorkerName", "Fullname") AS "StaffName"'),
            DB::raw('\'Service\' AS "ProductType"'),
            DB::raw('"ServiceName" AS "ProductName"'),
            DB::raw('"ServiceCode" AS "ProductCode"'),
            DB::raw('coalesce(nullif("ServicePrice",0), "ServicePrice") as "ProductPrice"'),
            DB::raw('sum(std."TotalCommission") "TotalCommission"'),
            DB::raw('count(*) "Qty"'),
            DB::raw('sum(std."SubTotal") "SubTotal"')
        ];
        
        $selectSubService = [
            'UserCode AS StaffCode',
            DB::raw('coalesce("WorkerName", "Fullname") AS "StaffName"'),
            DB::raw('\'Sub Service\' AS "ProductType"'),
            DB::raw('"SubServiceName" AS "ProductName"'),
            DB::raw('"SubServiceCode" AS "ProductCode"'),
            DB::raw('coalesce(nullif("SubServicePrice",0), "SubServicePrice") as "ProductPrice"'),
            DB::raw('sum(std."TotalCommission") "TotalCommission"'),
            DB::raw('count(*) "Qty"'),
            DB::raw('sum(std."SubServicePrice") "SubTotal"')
        ];
        
        $item = DB::table('ItemSalesTransactionCommissionDetail AS sdtc')->select($selectItem)
            ->join('ItemSalesTransactionDetail AS std','sdtc.ItemSalesTransactionDetailID', '=', 'std.ItemSalesTransactionDetailID')
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->join('User AS w','w.UserID', '=', 'sdtc.UserID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('std.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
        if(@$this->input['UserID'] != null)
            $item = $item->where('sdtc.UserID', @$this->input['UserID']);
        $item = $item->groupBy(DB::raw('1,2,3,4,5,6'));
        
        $subService = DB::table('SubServiceSalesTransactionDetail AS std')->select($selectSubService)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->join('User AS w','w.UserID', '=', 'std.WorkerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('std.Void' , null)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
        if(@$this->input['UserID'] != null)
            $subService = $subService->where('std.WorkerID', @$this->input['UserID']);
        $subService = $subService->groupBy(DB::raw('1,2,3,4,5,6'));
        
        $service = DB::table('ServiceSalesTransactionDetail AS std')->union($subService)->union($item)->select($selectService)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->join('User AS w','w.UserID', '=', 'std.WorkerID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('st.Void' , '<>' , 1)->where('std.Void' , '<>' , 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
         
        if(@$this->input['UserID'] != null)
            $service = $service->where('std.WorkerID', @$this->input['UserID']);   
        
        $service = $service->groupBy(DB::raw('1,2,3,4,5,6'))->orderBy(DB::raw('1,2,3'))->get();  
        $data = array();
        $i = 0;
        foreach($service as $d){
            if(count($data) <=0){
                $data[0]['StaffCode'] = $d->StaffCode;
                $data[0]['StaffName'] = $d->StaffName;
                $data[0]['Detail'][0] = array(
                    'ProductType' => $d->ProductType,
                    'ProductName' => $d->ProductName,
                    'ProductCode' => $d->ProductCode,
                    'ProductPrice' => $d->ProductPrice,
                    'Qty' => $d->Qty,
                    'SubTotal' => $d->SubTotal,
                    'TotalCommission' => $d->TotalCommission
                    
                    

                );
            } else if ($d->StaffCode == $data[$i]['StaffCode']){
                $data[$i]['StaffCode'] = $d->StaffCode;
                $data[$i]['StaffName'] = $d->StaffName;
                $data[$i]['Detail'][] = array(
                    'ProductType' => $d->ProductType,
                    'ProductName' => $d->ProductName,
                    'ProductCode' => $d->ProductCode,
                    'ProductPrice' => $d->ProductPrice,
                    'Qty' => $d->Qty,
                    'SubTotal' => $d->SubTotal,
                    'TotalCommission' => $d->TotalCommission
                );
            } else {
                $i += 1;
                $data[$i]['StaffCode'] = $d->StaffCode;
                $data[$i]['StaffName'] = $d->StaffName;
                $data[$i]['Detail'][] = array(
                    'ProductType' => $d->ProductType,
                    'ProductName' => $d->ProductName,
                    'ProductCode' => $d->ProductCode,
                    'ProductPrice' => $d->ProductPrice,
                    'Qty' => $d->Qty,
                    'SubTotal' => $d->SubTotal,
                    'TotalCommission' => $d->TotalCommission
                );
            }
        }
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$data]);
        return response()->json($response);
    }
    
    
    public function commissionBreakdownHistory(){
        $selectItem = [
            'Date',
            DB::raw('"Fullname" AS "StaffName"'),
            'UserCode AS StaffCode',
            DB::raw('coalesce(st."CustomerName", c."CustomerName") AS "CustomerName"'),
            DB::raw('\'Item\' AS "ProductType"'),
            'ItemName AS ProductName',
            'ItemCode AS ProductCode',
            'Price AS ProductPrice',
            'Qty',
            'SubTotal',
            DB::raw('sdtc."Commission" "TotalCommission"')
        ];
        $selectService = [
            'Date',
            DB::raw('"Fullname" AS "StaffName"'),
            'UserCode AS StaffCode',
            DB::raw('coalesce(st."CustomerName", c."CustomerName") AS "CustomerName"'),
            DB::raw('\'Service\' AS "ProductType"'),
            DB::raw('"ServiceName" AS "ProductName"'),
            DB::raw('"ServiceCode" AS "ProductCode"'),
            DB::raw('1 AS "Qty"'),
            'SubTotal',
            DB::raw('coalesce(nullif("ServicePrice",0), "ServicePrice") as "ProductPrice"'),
            DB::raw('coalesce(std."TotalCommission", 0) AS "TotalCommission"')
        ];
        
        $selectSubService = [
            'Date',
            DB::raw('"Fullname" AS "StaffName"'),
            'UserCode AS StaffCode',
            DB::raw('coalesce(st."CustomerName", c."CustomerName") AS "CustomerName"'),
            DB::raw('\'Sub Service\' AS "ProductType"'),
            DB::raw('"SubServiceName" AS "ProductName"'),
            DB::raw('"SubServiceCode" AS "ProductCode"'),
            DB::raw('1 AS "Qty"'),
            'SubServicePrice AS SubTotal',
            DB::raw('coalesce(nullif("SubServicePrice",0), "SubServicePrice") as "ProductPrice"'),
            DB::raw('coalesce(std."TotalCommission", 0) AS "TotalCommission"')
        ];
        
        if(@$this->input['Item'] == true){
            $result = DB::table('ItemSalesTransactionCommissionDetail AS sdtc')->select($selectItem)
                    ->leftJoin('ItemSalesTransactionDetail AS std','sdtc.ItemSalesTransactionDetailID', '=', 'std.ItemSalesTransactionDetailID')
                    ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                    ->leftJoin('User AS w','w.UserID', '=', 'sdtc.UserID')
                    ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
                    ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                    ->where('st.Void' , '<>' , 1)->where('std.Void' , '<>' , 1)
                    ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
            if(@$this->input['UserID'] != null)
                $result = $result->where('sdtc.UserID', @$this->input['UserID']);
            $result = $result->orderBy(DB::raw('1,2'))->get();
        } else if(@$this->input['Service'] == true){
            
            $result = DB::table('SubServiceSalesTransactionDetail AS std')->select($selectSubService)
                ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
                ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
                ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                ->where('std.Void' , null)
                ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
            if(@$this->input['UserID'] != null)
                $result = $result->where('std.WorkerID', @$this->input['UserID']);
            $result = DB::table('ServiceSalesTransactionDetail AS std')->select($selectService)->union($result)
                ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
                ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
                ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                ->where('st.Void' , '<>' , 1)->where('std.Void' , '<>' , 1)
                ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);

            if(@$this->input['UserID'] != null)
                $result = $result->where('std.WorkerID', @$this->input['UserID']);
            $result = $result->orderBy(DB::raw('1,2'))->get();
            
        } else {
        $item = DB::table('ItemSalesTransactionCommissionDetail AS sdtc')->select($selectItem)
                ->leftJoin('ItemSalesTransactionDetail AS std','sdtc.ItemSalesTransactionDetailID', '=', 'std.ItemSalesTransactionDetailID')
                ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                ->leftJoin('User AS w','w.UserID', '=', 'sdtc.UserID')
                ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
                ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                ->where('st.Void' , '<>' , 1)->where('std.Void' , '<>' , 1)
                ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
            if(@$this->input['UserID'] != null)
                $item = $item->where('sdtc.UserID', @$this->input['UserID']);

            $sub = DB::table('SubServiceSalesTransactionDetail AS std')->select($selectSubService)->union($item)
                ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
                ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
                ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                ->where('std.Void' , null)
                ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
            if(@$this->input['UserID'] != null)
                $sub = $sub->where('std.WorkerID', @$this->input['UserID']);
            
            $result = DB::table('ServiceSalesTransactionDetail AS std')->select($selectService)->union($sub)
                ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
                ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
                ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                ->where('st.Void' , '<>' , 1)->where('std.Void' , '<>' , 1)
                ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);
            if(@$this->input['UserID'] != null)
                $result = $result->where('std.WorkerID', @$this->input['UserID']);
            $result = $result->orderBy(DB::raw('1,2'))->get();
        }
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function VoidReport(){
        $selectItem = [
            'std.ItemID AS ProductID',
            'std.ItemCode AS ProductCode',
            'std.ItemName AS ProductName',
            DB::raw('\'Item\' AS "ProductType"'),
            DB::raw('coalesce(std."Price",0) "ProductPrice"'),
            DB::raw('sum(coalesce(std."SubTotal",0)) "SubTotal"'),
            DB::raw('sum("Qty") "Qty"')
        ];
        
        $selectService = [
            'std.ServiceID AS ProductID',
            'std.ServiceCode AS ProductCode',
            'std.ServiceName AS ProductName',
            DB::raw('\'Service\' AS "ProductType"'),
            DB::raw('"ServicePrice" AS "ProductPrice"'),
            DB::raw('sum(coalesce(std."SubTotal",0)) "SubTotal"'),
            DB::raw('count(*) as "Qty"')
        ];
        
        $selectSubService = [
            'std.SubServiceID AS ProductID',
            'std.SubServiceCode AS ProductCode',
            'std.SubServiceName AS ProductName',
            DB::raw('\'Service\' AS "ProductType"'),
            DB::raw('"SubServicePrice" AS "ProductPrice"'),
            DB::raw('sum(coalesce(std."SubServicePrice",0)) "SubTotal"'),
            DB::raw('count(*) as "Qty"')
        ];
        
        $item = DB::table('ItemSalesTransactionDetail AS std')->select($selectItem)
            ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->join('Item AS i','i.ItemID', '=', 'std.ItemID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('std.Void', 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1,2,3,4,5'));
        
        $service = DB::table('ServiceSalesTransactionDetail AS std')->select($selectService)
            ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('std.Void', 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1,2,3,4,5')); 
        
        $result = DB::table('SubServiceSalesTransactionDetail AS std')->select($selectSubService)->union($item)->union($service)
            ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
            ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
            ->where('std.Void', 1)
            ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])
            ->groupBy(DB::raw('1,2,3,4,5'))->orderBy(DB::raw('2,3,1'))->get();  
        
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    
    public function VoidReportBreakdown(){
        $selectItem = [
            'Date',
            'std.VoidDate',
            'SalesTransactionNumber',
            'std.VoidDescription',
            'std.VoidByAccountID',
            'Fullname',
            'CustomerName',
            'std.VoidType',
            'ItemID AS ProductID',
            'ItemCode AS ProductCode',
            'ItemName AS ProductName',
            DB::raw('\'Item\' AS "ProductType"'),
            DB::raw('coalesce(std."Price",0) "ProductPrice"'),
            DB::raw('coalesce(std."SubTotal", 0) "SubTotal"'),
            DB::raw('coalesce("Qty", 1) as "Qty"')
        ];
        
        $selectService = [
            'Date',
            'std.VoidDate',
            'SalesTransactionNumber',
            'std.VoidDescription',
            'std.VoidByAccountID',
            'Fullname',
            'CustomerName',
            'std.VoidType',
            'ServiceID AS ProductID',
            'ServiceCode AS ProductCode',
            'ServiceName AS ProductName',
            DB::raw('\'Service\' AS "ProductType"'),
            DB::raw('coalesce(std."ServicePrice",0) as "ProductPrice"'),
            DB::raw('coalesce(std."SubTotal", 0) "SubTotal"'),
            DB::raw('\'1\' as "Qty"')
        ];
        
        $selectSubService = [
            'Date',
            'std.VoidDate',
            'SalesTransactionNumber',
            'std.VoidDescription',
            'std.VoidByAccountID',
            'Fullname',
            'CustomerName',
            'std.VoidType',
            'SubServiceID AS ProductID',
            'SubServiceCode AS ProductCode',
            'SubServiceName AS ProductName',
            DB::raw('\'Service\' AS "ProductType"'),
            DB::raw('coalesce(std."SubServicePrice",0) as "ProductPrice"'),
            DB::raw('coalesce(std."SubServicePrice", 0) "SubTotal"'),
            DB::raw('\'1\' as "Qty"')
        ];
        if(@$this->input['ItemID'] != null){
            $id = @$this->input['ItemID'];
            $result = DB::table('ItemSalesTransactionDetail AS std')->select($selectItem)
                ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
                ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                ->where('std.Void', 1)->where('std.ItemID', $id)
                ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get();
        } else if(@$this->input['ServiceID'] != null){
            $id = @$this->input['ServiceID'];
            $result = DB::table('ServiceSalesTransactionDetail AS std')->select($selectService)
                ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
                ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                ->where('std.Void', 1)->where('std.ServiceID', $id)
                ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get(); 
        } else if(@$this->input['SubServiceID'] != null){
            $id = @$this->input['SubServiceID'];
            $result = DB::table('SubServiceSalesTransactionDetail AS std')->select($selectSubService)
                ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
                ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                ->where('std.Void', 1)->where('std.SubServiceID', $id)
                ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get();  
        } else {
            $item = DB::table('ItemSalesTransactionDetail AS std')->select($selectItem)
                ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
                ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                ->where('std.Void', 1)
                ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo']);

            $service = DB::table('ServiceSalesTransactionDetail AS std')->select($selectService)
                ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
                ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                ->where('std.Void', 1)
                ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get();      
            
            $result = DB::table('SubServiceSalesTransactionDetail AS std')->select($selectSubService)->union($item)->union($service)
                ->leftJoin('SalesTransaction AS st','st.SalesTransactionID', '=', 'std.SalesTransactionID')
                ->leftJoin('User AS w','w.UserID', '=', 'std.WorkerID')
                ->where('st.BranchID', $this->param->BranchID)->where('st.BrandID',  $this->param->MainID)
                ->where('std.Void', 1)
                ->where('st.Date', '>=', $this->input['DateFrom'])->where('st.Date', '<=', $this->input['DateTo'])->get();  
        }
        $unknown_users = array();
            if(count($result) > 0){
                $unknown_users = [];
                $temp_report = [];
                foreach ($result as $key => $val) {
                    if($val->VoidByAccountID != null && !in_array($val->VoidByAccountID, $unknown_users)) $unknown_users[] = $val->VoidByAccountID;
                    $temp_report[] = $key;
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
}
