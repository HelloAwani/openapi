<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Item;
use Service\Interfaces\Meta;
use DB;

class ItemController extends BaseController
{
    public function __construct(Meta $meta, Item $item, Request $request){
        $this->item = $item;
        $this->meta = $meta;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
    }

    /**
     * Display a listing of the resource.
     * POST api/web/item/datatables
     * @return \Illuminate\Http\Response
     */
    public function datatables()
    {
        $input = json_decode($this->request->getContent(),true);
        $draw = $this->coalesce(@$input['Draw'],1);
        $orderBy = $this->coalesce(@$input['OrderBy'], 'CategoryCode');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = @$input['PageSize'];
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $sortableColumn = ["CategoryCode","CategoryName","ItemName","ItemCode"];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $total = $this->item->totalRecords($this->param);
        $data = $this->item->get($perPage, $start, $orderBy, $sort, $keyword,$this->param);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function import()
    {
        $input = json_decode($this->request->getContent(),true);
        $rules = [
            'Data' => 'required|array',
            'Data.*.ItemCode' => 'required|distinct',
            'Data.*.ItemName' => 'required',
            'Data.*.CategoryCode' => 'required|exists:Category,CategoryCode',
            'Data.*.CommissionValue' => 'numeric|required_without_all:Data.*.CommissionPercent',
            'Data.*.CommissionPercent' => 'numeric|required_without_all:Data.*.CommissionValue',
            'Data.*.Price' => 'required|numeric',
            'Data.*.InventoryUnitTypeAbbv' => 'required|exists:InventoryUnitType,InventoryUnitTypeAbbv'
        ];
        
        $niceNames = array(
            'Data.*.ItemCode' => 'Item Code',
            'Data.*.ItemName' => 'Item Name',
            'Data.*.CategoryCode' => 'Category Code',
            'Data.*.CommissionValue' => 'Commission Value',
            'Data.*.CommissionPercent' => 'Commission Percent',
            'Data.*.Price' => 'Price',
            'Data.*.InventoryUnitTypeAbbv' => 'Inventory Unit Type'
        );
        
        $messages = [
            'level_filter' => 'The :attribute choices must be 0 or 1'
        ];
        Validator::extend('levelFilter', function($attribute, $value, $parameters, $validator) {
            if(strtoupper($value) == 0 || strtoupper($value) == 1) return true;
            else return false;
        });
        $validator = Validator::make($input, $rules, $messages);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }
            
        $this->db_trans_start();
        $response = $this->generateResponse(0, [], "Success", []);
        $duration = DB::table('InventoryUnitType')->get();
        $temp = array();
        foreach($duration as $d){
            $temp[$d->InventoryUnitTypeAbbv] = $d->InventoryUnitTypeID;
        }
        for($i = 0; $i < count(@$input['Data']); $i++){
            $detail = @$input['Data'][$i];
            $id = @$this->meta->checkUniqueGetID('Item', 'ItemCode', $detail['ItemCode'], $this->param->BranchID, $this->param->MainID);
            if($id !== null ){
                // update item category
                $data = [
                    "ItemCode" => $this->coalesce(@$detail['ServiceLevelCommission'], 0),
                    "ItemName" => @$detail['ServiceName'], 
                    "CategoryCode" => @$detail['ServiceCode'], 
                    "Price" => @$detail['Price'], 
                    "CommissionValue" => @$detail['CommissionValue'],
                    "CommissionPercent" => @$detail['CommissionPercent'],
                    "InventoryUnitTypeID" => $temp[$detail['InventoryUnitTypeAbbv']]
                ];
            } else {
                // create new item category
                $data = [
                    "ItemCode" => $this->coalesce(@$detail['ServiceLevelCommission'], 0),
                    "ItemName" => @$detail['ServiceName'], 
                    "CategoryCode" => @$detail['ServiceCode'], 
                    "Price" => @$detail['Price'], 
                    "CommissionValue" => @$detail['CommissionValue'],
                    "CommissionPercent" => @$detail['CommissionPercent'],
                    "InventoryUnitTypeID" => $temp[$detail['InventoryUnitTypeAbbv']],
                    "BrandID" => $this->param->MainID, 
                    "BranchID" => $this->param->BranchID
                ];
            }
            $insertedData = $this->meta->upsert('Item', $data, $this->param, $id);
            if(isset($insertedData['error'])){ 
                if($this->environment != 'live') $errorMsg = $insertedData['message'];
                else $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Database Error");
            }
        }
        $this->db_trans_end();
        return response()->json($response);
    }
    
    /**
     * Store a newly created resource in storage.
     * POST api/web/item
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['ItemID'];
        $categoryID = @$input['CategoryID'];
        $rules = [
                    'ItemCode' => 'required',
                    'ItemName' => 'required',
                    'CategoryID' => 'required|numeric',
                    'CommissionValue' => 'numeric',
                    'CommissionPercent' => 'numeric',
                    'Price' => 'required|numeric',
                    'InventoryUnitTypeID' => 'required|exists:InventoryUnitType,InventoryUnitTypeID'
                ];
        if(@$input['UseManualCOGS'] == '1')
            $rules['ManualCOGS'] = 'numeric|required|min:0';
        if(@$input['ItemCode'] != null && !empty($this->item->checkBranchCode(@$input['ItemCode'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['ItemCode'] = 'required|unique:Item';
        }
        $validator = Validator::make($input, $rules);
        if(@$input['CategoryID'] != null && empty($this->meta->checkUnique('Category', 'CategoryID', @$input['CategoryID'], $this->param->BranchID, $this->param->MainID)) ){
            $additional[] = ["ID" => "Category", "Message" => "Category is not Invalid"];
            $response = $this->generateResponse(1, $additional, "Please check input");
            return response()->json($response);
        }
        
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            if($errors->first('ItemCode')){
                if(strpos($errors->first('ItemCode'),'taken') !== false){
                    $additional = ["Item" => $this->item->where('ItemCode',$input['ItemCode'], 'first')];
                }
            }
            
            
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }

        // validate input
        
        if($id !== null ){
            $find = $this->item->find($id);
            if(isset($find['error'])){
                if($this->environment != 'live') $errorMsg = $find['message'];
                else $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
            $data = [
                "ItemName" => @$input['ItemName'], 
                "ItemCode" => @$input['ItemCode'], 
                "CommissionValue" => @$input['CommissionValue'],
                "CommissionPercent" => @$input['CommissionPercent'],
                "CategoryID" => @$input['CategoryID'], 
                "InventoryUnitTypeID" => @$input['InventoryUnitTypeID'], 
                "UseManualCOGS" => @$input['UseManualCOGS'] ? @$input['UseManualCOGS'] : '0', 
                "ManualCOGS" => @$input['ManualCOGS'], 
                "Price" => @$input['Price']
            ];
        } else {
            $data = [
                "ItemName" => @$input['ItemName'], 
                "ItemCode" => @$input['ItemCode'],
                "CategoryID" => @$input['CategoryID'],
                "Price" => @$input['Price'],
                "CurrentStock" => 0, 
                "BrandID" => $this->param->MainID, 
                "BranchID" => $this->param->BranchID,
                "UseManualCOGS" => @$input['UseManualCOGS'] ? @$input['UseManualCOGS'] : '0', 
                "COGS" => 0, 
                "ManualCOGS" => @$input['ManualCOGS'], 
                "InventoryUnitTypeID" => @$input['InventoryUnitTypeID'], 
                "CommissionValue" => @$input['CommissionValue'],
                "CommissionPercent" => @$input['CommissionPercent']
            ];
        }
        if(@$input['DeleteImage'] === true){
            $data = ["Image" => null];
        }
        $insertedData = $this->item->upsert($data, $id);
        if(@$id === null){
            $id = $this->getLastVal();   
        }
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['Data'=>$insertedData]);
        }
        if(@$input['Images'] !== null){
            $arr = array(
                'BrandID' => $this->param->MainID,
                'BranchID' => $this->param->BranchID,
                'ObjectID' => $id,
                'Folder' => 'Item',
                'Filename' => @$input['Images']['Filename'], 
                'Data' =>  @$input['Images']['Data']
            );
            $path = $this->upload_to_s3($arr);
            $data = ["Image" => $path];
            $insertedData = $this->itemCategory->upsert($data, $id);
            $response = $this->generateResponse(0, [], "Success", ['Data'=>$insertedData]);
        }
        return response()->json($response);
    }

    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['ItemID'];
        $find = $this->item->find($id);
        if(isset($find['error'])){
            if($this->environment != 'live') $errorMsg = $find['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Data not found");
            return response()->json($response);
        }

        $response = $this->generateResponse(0, [], "Success", ['Item'=>$find]);
        return response()->json($response);
    }
    /**
     * Remove the specified resource from storage.
     * DELETE api/web/item/{id}
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['ItemID'];
        $delete = $this->item->delete($id);
        if(isset($delete['error'])){
            if($this->environment != 'live') $errorMsg = $delete['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Data not found");
            return response()->json($response);
        }
        $response = $this->generateResponse(0, [], "Success");
        return response()->json($response);
    }
}
