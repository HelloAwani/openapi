<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use DB;

class LoginController extends BaseController
{
    
    public function __construct(Meta $meta, Request $request){
        $this->environment = \App::environment();
        $this->request = $request;
    }

    public function login()
    {
        $input = json_decode($this->request->getContent(),true);
        $select = [
            'Password',
            'UserID'
        ];
        $result = DB::table('User')->select($select)
            ->where(DB::raw('LOWER("Username")'), strtolower(@$input['Username']))->where('Archived', null)->get()->first();
        if (password_verify(@$input['Password'], @$result->Password)) {
            $response = $this->generateResponse(0, [], "Success", ['UserID'=>$result->UserID]);
        } else {
            $response = $this->generateResponse(1, [], "Login Failed", []);
        }
        return response()->json($response);
    }
    
    
    public function userItemSalesCommission(){
        $input = json_decode($this->request->getContent(),true);
        $select = [
            DB::raw('coalesce(sum(sdt."Qty"), 0) "Qty"'),
            DB::raw('coalesce(sum(sdt."Price"), 0) "Price"'),
            DB::raw('coalesce(sum(sdt."SubTotal"), 0) "SubTotal"'),
            DB::raw('coalesce(sum(sdtc."Commission"), 0) "TotalCommission"')
        ];
        $result = DB::table('ItemSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->join('ItemSalesTransactionCommissionDetail AS sdtc','sdtc.ItemSalesTransactionDetailID', '=', 'sdt.ItemSalesTransactionDetailID')
            ->where('sdtc.UserID',  @$input['UserID'])
            ->where('st.Void', null)->where('sdt.Void', null)
            ->where('st.Date', '>=', $input['DateFrom'])->where('st.Date', '<=', $input['DateTo'])->get()->first();        
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function userServiceSalesCommission(){
        $input = json_decode($this->request->getContent(),true);
        $select = [
            DB::raw('coalesce(sum(sdt."TotalCommission"), 0) "TotalCommission"')
        ];
        $result = DB::table('ServiceSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('sdt.WorkerID',  @$input['UserID'])
            ->where('st.Void', null)->where('sdt.Void', null)
            ->where('st.Date', '>=', $input['DateFrom'])->where('st.Date', '<=', $input['DateTo'])->get()->first();        
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function userServiceSalesCommissionDetail(){
        $input = json_decode($this->request->getContent(),true);
        $select = [
            DB::raw('coalesce(st."CustomerName", c."CustomerName") "CustomerName"'),
            "ServiceName", 
            "st.Date", 
            "SubServiceName",
            "sdt.TotalCommission"
        ];
        $result = DB::table('ServiceSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('sdt.WorkerID',  @$input['UserID'])
            ->where('st.Void', null)->where('sdt.Void', null)
            ->where('st.Date', '>=', $input['DateFrom'])->where('st.Date', '<=', $input['DateTo'])->get();        
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    public function userItemSalesCommissionDetail(){
        
        $input = json_decode($this->request->getContent(),true);
        $select = [
            DB::raw('coalesce(st."CustomerName", c."CustomerName") "CustomerName"'),
            "ItemCategoryName", 
            "st.Date", 
            "ItemName", 
            "Qty",
            "sdtc.Commission AS TotalCommission"
        ];
        $result = DB::table('ItemSalesTransactionDetail AS sdt')->select($select)
            ->join('SalesTransaction AS st','st.SalesTransactionID', '=', 'sdt.SalesTransactionID')
            ->join('ItemSalesTransactionCommissionDetail AS sdtc','sdtc.ItemSalesTransactionDetailID', '=', 'sdt.ItemSalesTransactionDetailID')
            ->leftJoin('Customer AS c','c.CustomerID', '=', 'st.CustomerID')
            ->where('sdtc.UserID',  @$input['UserID'])
            ->where('st.Void', null)->where('sdt.Void', null)
            ->where('st.Date', '>=', $input['DateFrom'])->where('st.Date', '<=', $input['DateTo'])->get();        
        $response = $this->generateResponse(0, [], "Success", ['Data'=>$result]);
        return response()->json($response);
    }
    
    
    
}
