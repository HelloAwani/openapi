<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;

use DB;

class MetaController extends BaseController
{
    public function __construct(Meta $meta, Request $request){
        $this->meta = $meta;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     * POST api/web/itemcategory/datatables
     * @return \Illuminate\Http\Response
     */
    public function getCategory(){
        $table = 'Category';
        $select = ['CategoryID','CategoryCode','CategoryName'];
        $response = $this->meta->get($table, $select, $this->param);
        return response()->json($response);
    }
    
    public function getShift(){
        $table = 'Shift';
        $select = ['ShiftID','ShiftCode','ShiftName'];
        $response = $this->meta->get($table, $select, $this->param);
        return response()->json($response);
    }
    
    public function getExpenseType(){
        $table = 'ExpenseType';
        $select = ['ExpenseTypeID','ExpenseTypeName'];
        $response = $this->meta->get($table, $select, $this->param);
        return response()->json($response);
    }
    
    public function getUserType(){
        $table = 'UserType';
        $select = ['UserTypeID','UserTypeCode','UserTypeName'];
        $response = $this->meta->get($table, $select, $this->param);
        return response()->json($response);
    }
    
    public function getUnitDuration(){
        $table = 'DurationUnit';
        $select = ['DurationUnitID','DurationUnitName'];
        $response = $this->meta->get($table, $select);
        return response()->json($response);
    }
    
    public function getPaymentMethod(){
        $table = 'PaymentMethod';
        $select = ['PaymentMethodID','PaymentMethodName','PredefinedPaymentMethodID'];
        $response = $this->meta->get($table, $select, $this->param);
        return response()->json($response);
    }
    
    public function getSpaceSection(){
        $table = 'SpaceSection';
        $select = ['SpaceSectionID','SpaceSectionName'];
        $response = $this->meta->get($table, $select, $this->param);
        return response()->json($response);
    }
    
    public function getUnitType(){
        $table = 'InventoryUnitType';
        $select = ['InventoryUnitTypeID','InventoryUnitTypeName', 'InventoryUnitTypeAbbv'];
        $response = $this->meta->get($table, $select);
        return response()->json($response);
    }
    
    public function getSubService(){
        $table = 'SubService';
        $select = ['SubServiceID','SubServiceCode','SubServiceName'];
        $response = $this->meta->get($table, $select, $this->param);
        return response()->json($response);
    }
    
    public function getItem(){
        $table = 'Item';
        $select = ['ItemID','ItemCode','ItemName'];
        $join = [
            ['join', 'Category AS c', 'c.CategoryID', '=', 'Item.CategoryID']
        ];
        $where = [
            ['whereIsNull', 'c.Archived', null, ''],
            ['whereIsNull', 'Item.Archived', null, '']
        ];
        $response = $this->meta->get($table, $select, $this->param, $join, $where);
        return response()->json($response);
    }
    
    public function getUser(){
        $table = 'User';
        $select = ['UserID','UserCode','Fullname'];
        $where = [
            ['where', 'ActiveStatus', '=', 'A']
        ];
        $response = $this->meta->get($table, $select, $this->param, null, $where);
        return response()->json($response);
    }
    
    public function getDetailBranch(){
        $table = 'Branch';
        $select = ['BranchID','BranchName','Address','Contact','Email','Image','Description'];
        $response['Data'] = $this->meta->get($table, $select, $this->param, null)[0];
        return response()->json($response);
    }
    
    public function getItemStock(){
        $table = 'Item AS i';
        $select = ['ItemID','ItemCode','ItemName', 'CurrentStock', 'Price', 'COGS', 'c.CategoryID','c.CategoryName', 'c.CategoryCode', 'InventoryUnitTypeName'];
        $result = DB::table($table)->select($select)
            ->join('Category AS c', 'c.CategoryID', '=', 'i.CategoryID')
            ->join('InventoryUnitType AS iut', 'iut.InventoryUnitTypeID', '=', 'i.InventoryUnitTypeID')
            ->where('c.Archived', null)->where('i.Archived', null)
            ->where('i.BrandID', $this->param->MainID)->where('i.BranchID', $this->param->BranchID)
            ->orderBy('CategoryID')
            ->whereNotNull('i.InventoryUnitTypeID')->get();
        $data = array();
        $i = 0;
        foreach($result as $d){
            if(count($data) <=0){
                $data[0]['CategoryID'] = $d->CategoryID;
                $data[0]['CategoryName'] = $d->CategoryName;
                $data[0]['CategoryCode'] = $d->CategoryCode;
                $data[0]['Detail'][0] = array(
                    'CategoryID' => $d->CategoryID,
                    'ItemID' => $d->ItemID,
                    'ItemCode' => $d->ItemCode,
                    'ItemName' => $d->ItemName,
                    'CurrentStock' => $d->CurrentStock,
                    'Price' => $d->Price,
                    'COGS' => $d->COGS,
                    'InventoryUnitTypeName' => $d->InventoryUnitTypeName
                );
            } else if ($d->CategoryID == $data[$i]['CategoryID']){
                $data[$i]['Detail'][] = array(
                    'CategoryID' => $d->CategoryID,
                    'ItemID' => $d->ItemID,
                    'ItemCode' => $d->ItemCode,
                    'ItemName' => $d->ItemName,
                    'CurrentStock' => $d->CurrentStock,
                    'Price' => $d->Price,
                    'COGS' => $d->COGS,
                    'InventoryUnitTypeName' => $d->InventoryUnitTypeName
                );
            } else {
                $i += 1;
                $data[$i]['CategoryID'] = $d->CategoryID;
                $data[$i]['CategoryName'] = $d->CategoryName;
                $data[$i]['CategoryCode'] = $d->CategoryCode;
                $data[$i]['Detail'][] = array(
                    'CategoryID' => $d->CategoryID,
                    'ItemID' => $d->ItemID,
                    'ItemCode' => $d->ItemCode,
                    'ItemName' => $d->ItemName,
                    'CurrentStock' => $d->CurrentStock,
                    'Price' => $d->Price,
                    'COGS' => $d->COGS,
                    'InventoryUnitTypeName' => $d->InventoryUnitTypeName
                );
            }
        }
        return response()->json($data);
    }
    
    public function getPermissionList(){
        $table = 'Permission';
        $response = DB::table($table)->orderBy('Order')->get();
        $i = 0;
        $ordered = array();
        foreach($response as $res){
            if(@$ordered[$i-1]['Group'] != $res->Group){
                $ordered[$i]['Group'] = $res->Group;
                $i += 1;
            }
            $ordered[$i-1]['Permission'][] = array(
                'PermissionID' => $res->PermissionID,
                'PermissionName' => $res->PermissionName,
                'Order' => $res->Order
            );
            
        }
        return response()->json($ordered);
    }
    
    public function getConversionUnit(){
        $input = json_decode($this->request->getContent(),true);
        $table = 'ItemConversion';
        $item = DB::table('Item')->select('InventoryUnitType.*')->where('ItemID', @$input['ItemID'])->join('InventoryUnitType', 'InventoryUnitType.InventoryUnitTypeID', '=', 'Item.InventoryUnitTypeID');
        $response = DB::table($table)->select('InventoryUnitType.*')->union($item)->where('ItemID', @$input['ItemID'])->join('InventoryUnitType', 'InventoryUnitType.InventoryUnitTypeID', '=', 'ItemConversion.ToInventoryUnitTypeID')->get();
        return response()->json($response);
    }
    
    public function getCommissionFormula(){
        $table = 'CommissionFormula';
        $select = ['CommissionFormulaID', 'CommissionFormulaName', 'CommissionFormulaCode', 'HiddenFields', 'Status'];
        $response = $this->meta->get($table, $select);
        return response()->json($response);
    }
}
