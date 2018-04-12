<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use DB;

class InventoryTransactionController extends BaseController
{
    public function __construct(Meta $meta, Request $request){
        $this->meta = $meta;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }

    public function datatables()
    {
        $input = json_decode($this->request->getContent(),true);
        $draw = $this->coalesce(@$input['Draw'],1);
        $orderBy = $this->coalesce(@$input['OrderBy'], 'UserCode');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = $this->coalesce(@$input['PageSize'], 10);
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $sortableColumn = [
            'Notes',
            'TransactionDate',
            'Fullname'
        ];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $table = 'InventoryTransaction';
        $total = DB::table($table)->where('BranchID', $this->param->BranchID)->where('BrandID', $this->param->MainID)->where('TransactionType', @$input['TransactionType'])->count();
        $display = [
            'InventoryTransaction.InventoryTransactionID',
            'ReferenceNo',
            'Notes',
            'TransactionDate',
            'Fullname',
            'AccountID'
        ];
        $searchable = [
            'ReferenceNo',
            'Notes',
            'TransactionDate',
            'Fullname'
        ];
        $join = [
            ['leftJoin', 'User AS u','u.UserID', '=', $table.'.UserID']
        ];
        $extraWhere = [
            ['TransactionType', @$input['TransactionType']]
        ];
        $data = $this->meta->getDataTableTransaction($table, $display, $searchable, $perPage, $start, $orderBy, $sort, $keyword,$this->param, $join, $extraWhere);
        $result = $data['data'];
        $unknown_users = [];
        $temp_report = [];
        if(count($result) > 0){
			foreach ($result as $key => $val) {
				if(@$val->AccountID != null && !in_array($val->AccountID, $unknown_users))
                {$unknown_users[] = $val->AccountID;$temp_report[] = $key;}
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
        foreach ($temp_report as $temp_val) {
            foreach($auth->Raw as $uid => $user){
                if($result[$temp_val]->AccountID == $uid){
                    $result[$temp_val]->Fullname = $user; 
                }
            }
        }    
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    public function good_receive()
    {
        $input = json_decode($this->request->getContent(),true);
        $rules = [
            'Detail' => 'required|array',
            'Detail.*.Qty' => 'required|min:1|numeric',
            'Detail.*.Price' => 'required|min:0|numeric',
            'Detail.*.ItemID' => 'required|min:0|distinct|exists:Item,ItemID,BranchID,'.$this->param->BranchID.'|exists:Item,ItemID,BrandID,'.$this->param->MainID
        ];
        $niceNames = array(
            'Detail.*.Qty' => 'Qty',
            'Detail.*.Price' => 'Price',
            'Detail.*.ItemID' => 'Item',
        );
        
        $validator = Validator::make($input, $rules);
        $validator->setAttributeNames($niceNames); 
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }
        $response = $this->insert_good_receive($input);
        return response()->json($response);
    }
    
    

    public function good_receive_import()
    {
        $input = json_decode($this->request->getContent(),true);
        $rules = [
            'Detail' => 'required|array',
            'Detail.*.Qty' => 'required|min:1|numeric',
            'Detail.*.Price' => 'required|min:0|numeric',
            'Detail.*.ItemCode' => 'required|min:0|distinct|exists:Item,ItemCode,BranchID,'.$this->param->BranchID.'|exists:Item,ItemCode,BrandID,'.$this->param->MainID
        ];
        
        $niceNames = array(
            'Detail.*.Qty' => 'Qty',
            'Detail.*.Price' => 'Price',
            'Detail.*.ItemCode' => 'Item Code',
        );
        
        $validator = Validator::make($input, $rules);
        $validator->setAttributeNames($niceNames); 
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }
        $response = insert_good_receive($input, 1);
        return response()->json($response);
    }
    
    public function insert_good_receive($input, $import = 0){
        
        $this->db_trans_start();
        // validate input
        if($import == 1){
            $item = DB::table('Item')->select(['ItemID', 'ItemCode'])->where('BranchID', $this->param->BranchID)->where('BrandID', $this->param->MainID)->get();
            $temp = array();
            foreach($item as $d){
                $temp[$d->ItemCode] = $d->ItemID;
            }
        }
        $arr = array(
            "BranchID"=>$this->param->BranchID,
            "BrandID"=>$this->param->MainID,
            "AccountID"=> $this->param->AccountID,
            "TransactionType"=>"Good Receive",
            "ReCalculatePrice"=>"1",
            "ReCalculateStock"=>"1",
            "RestrictDate"=>"1",
            "Notes"=> @$input['Notes'],
            "TransactionDate"=>$this->get_date_now()
        );
        
        $readid = DB::table('InventoryTransaction')->insert($arr);
        $readid = $this->getLastVal();
        foreach ($input['Detail'] as $dss) {
            
            if($import == 1){
                $itemID = $temp[$dss['ItemCode']];
            } else {
                $itemID = $dss['ItemID'];
            }
            $vr = DB::table('Item')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ItemID', $itemID);
            if($vr->count()==0){
                //invalid
                continue;
            }
            $vr = $vr->first();
            
            $cat = DB::table('Category')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('CategoryID', $vr->CategoryID)->first();
            $subtotal = $dss['Qty'] * $dss['Price'];
            $sub = $vr->CurrentStock * $vr->COGS;
            $sub = $sub < 0 ? 0 : $sub;
            $totalqty = $dss['Qty'] + $vr->CurrentStock;
            $darr = array(
                "BranchID"=>$this->param->BranchID,
                "BrandID"=>$this->param->MainID,
                "ItemID"=>$itemID,
                "ItemCode"=>$vr->ItemCode,
                "ItemName"=>$vr->ItemName,
                "CategoryID"=>$cat->CategoryID,
                "CategoryCode"=>$cat->CategoryCode,
                "CategoryName"=>$cat->CategoryName,
                "Qty"=>$dss['Qty'],
                "Price"=>$dss['Price'],
                "SubTotal"=>$subtotal,
                "InventoryUnitTypeID"=>$vr->InventoryUnitTypeID,
                "OldStock"=>$vr->CurrentStock,
                "OldCOGS"=>$vr->COGS,
                "NewStock"=>$vr->CurrentStock + (int) $dss['Qty'],
                "NewCOGS"=>($subtotal + $sub)/$totalqty,
                "InventoryTransactionID"=>@$readid
            );

            $result = DB::table('InventoryTransactionDetail')->insert($darr);
            $dataitem = array(
                'CurrentStock' => DB::raw('coalesce("CurrentStock",0) + '.$dss['Qty']),
                'COGS' => ($subtotal + $sub)/$totalqty
            );
            $result = DB::table('Item')->where('ItemID', $itemID)->update($dataitem);
        }
        
        
        $this->db_trans_end();
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['Data'=>'Success']);
        }   
        return $response;
    }
    
    public function stock_take()
    {
        $input = json_decode($this->request->getContent(),true);
        $rules = [
            'Detail' => 'required|array',
            'Detail.*.Qty' => 'required|min:1|numeric',
            'Detail.*.Price' => 'required|min:0|numeric',
            'Detail.*.ItemID' => 'required|min:0|exists:Item,ItemID|distinct'
        ];
        $niceNames = array(
            'Detail.*.Qty' => 'Qty',
            'Detail.*.Price' => 'Price',
            'Detail.*.ItemID' => 'Item',
        );
        
        $validator = Validator::make($input, $rules);
        $validator->setAttributeNames($niceNames); 
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }
        
        foreach($input['Detail'] as $detail){
            if(empty($this->meta->checkUnique('Item', 'ItemID', $detail['ItemID'], $this->param->BranchID, $this->param->MainID))){
                $additional[] = ["ID" => "Item", "Message" => "Item ".$detail['ItemID']." is not Invalid"];
                $response = $this->generateResponse(1, $additional, "Please check input");
                return response()->json($response);
            }
        }
        $this->db_trans_start();
        // validate input
        
        $arr = array(
            "BranchID"=>$this->param->BranchID,
            "BrandID"=>$this->param->MainID,
            "AccountID"=> $this->param->AccountID,
            "TransactionType"=>"Stock Take",
            "ReCalculatePrice"=>"1",
            "ReCalculateStock"=>"1",
            "RestrictDate"=>"1",
            "Notes"=> @$input['Notes'],
            "TransactionDate"=>$this->get_date_now()
        );
        
        $readid = DB::table('InventoryTransaction')->insert($arr);
        $readid = $this->getLastVal();
        foreach ($input['Detail'] as $dss) {
            $vr = DB::table('Item')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('ItemID', $dss['ItemID']);
            if($vr->count()==0){
                //invalid
                continue;
            }
            $vr = $vr->first();
            
            $cat = DB::table('Category')->where('BranchID',$this->param->BranchID)->where('BrandID',$this->param->MainID)->where('CategoryID', $vr->CategoryID)->first();
            $subtotal = $dss['Qty'] * $dss['Price'];
            $sub = $vr->CurrentStock * $vr->COGS;
            $sub = $sub < 0 ? 0 : $sub;
            $totalqty = $dss['Qty'] + $vr->CurrentStock;
            $darr = array(
                "BranchID"=>$this->param->BranchID,
                "BrandID"=>$this->param->MainID,
                "ItemID"=>$dss['ItemID'],
                "ItemCode"=>$vr->ItemCode,
                "ItemName"=>$vr->ItemName,
                "CategoryID"=>$cat->CategoryID,
                "CategoryCode"=>$cat->CategoryCode,
                "CategoryName"=>$cat->CategoryName,
                "Qty"=>$dss['Qty'] - $vr->CurrentStock,
                "Price"=>$dss['Price'] - $vr->COGS,
                "SubTotal"=>$subtotal,
                "InventoryUnitTypeID"=>$vr->InventoryUnitTypeID,
                "OldStock"=>$vr->CurrentStock,
                "OldCOGS"=>$vr->COGS,
                "NewStock"=>$dss['Qty'],
                "NewCOGS"=>$dss['Price'],
                "InventoryTransactionID"=>@$readid
            );

            $result = DB::table('InventoryTransactionDetail')->insert($darr);
            $dataitem = array(
                'CurrentStock' => $dss['Qty'],
                'COGS' => $dss['Price']
            );
            $result = DB::table('Item')->where('ItemID', $dss['ItemID'])->update($dataitem);
        }
        
        
        $this->db_trans_end();
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['Data'=>'Success']);
        }
        return response()->json($response);
    }
    
    public function detail(){
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['InventoryTransactionID'];
        $select = [
            'Fullname',
            'ReferenceNo',
            'ReferenceID',
            'TransactionType',
            'TransactionDate',
            'ProcessedDate',
            'ClosedDate',
            'AccountID'
        ];
        $result = DB::table('InventoryTransaction')->where('InventoryTransactionID', $id)->select($select)
            ->leftJoin('User AS u','u.UserID', '=', 'InventoryTransaction.UserID')->get();
        
        $unknown_users = [];
        $temp_report = [];
        if(count($result) > 0){
			foreach ($result as $key => $val) {
				if(@$val->AccountID != null && !in_array($val->AccountID, $unknown_users))
                {$unknown_users[] = $val->AccountID;$temp_report[] = $key;}
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
        foreach ($temp_report as $temp_val) {
            foreach($auth->Raw as $uid => $user){
                if($result[$temp_val]->AccountID == $uid){
                    $result[$temp_val]->Fullname = $user; 
                }
            }
        }    
        
        
        $select = [
            'CategoryCode',
            'CategoryName',
            'ItemName',
            'ItemCode',
            'InventoryUnitTypeName',
            'Qty',
            'Price',
            'OldStock',
            'NewStock',
            'OldCOGS',
            'NewCOGS'
        ];
        $result[0]->Detail = DB::table('InventoryTransactionDetail')->select($select)
            ->where('InventoryTransactionID', $id)
            ->leftJoin('InventoryUnitType AS iut','iut.InventoryUnitTypeID', '=', 'InventoryTransactionDetail.InventoryUnitTypeID')
            ->get();
        
        $response = $this->generateResponse(0, [], "Success", ['Data'=> $result[0]]);
        return response()->json($response);
    }
    
}
