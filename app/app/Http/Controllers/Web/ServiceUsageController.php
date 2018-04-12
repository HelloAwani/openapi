<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use Service\Interfaces\ServiceUsage;
use DB;

class ServiceUsageController extends BaseController
{
    public function __construct(ServiceUsage $service, Meta $meta, Request $request){
        $this->meta = $meta;
        $this->service = $service;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }


    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id_name = (@$input['SubServiceID'] !== null) ? 'SubServiceID' : 'ServiceID';
        $id = @$input[$id_name];
        $rules = [
                    $id_name => 'required',
                    'Detail' => 'array',
                    'Detail.*.ItemID' => 'required|exists:Item,ItemID|distinct',
                    'Detail.*.InventoryUnitTypeID' => 'required|exists:InventoryUnitType,InventoryUnitTypeID',
                    'Detail.*.Qty' => 'required|numeric|min:0'
                ];
        $niceNames = array(
            'Detail.*.Qty' => 'Qty',
            'Detail.*.InventoryUnitTypeID' => 'Inventory Unit Type',
            'Detail.*.ItemID' => 'Item',
        );
        if(@$input['SubServiceID'] != null && empty($this->meta->checkUnique('SubService', 'SubServiceID', @$input['SubServiceID'], $this->param->BranchID, $this->param->MainID))){
            $additional[] = ["ID" => "SubService", "Message" => "SubService ".@$input['SubServiceID']." is not Invalid"];
            $response = $this->generateResponse(1, $additional, "Please check input");
            return response()->json($response);
        }
        for($i = 0; $i < count(@$input['Detail']); $i++){
            $detail = @$input['Detail'][$i];
            if($detail['ItemID'] != null && empty($this->meta->checkUnique('Item', 'ItemID', $detail['ItemID'], $this->param->BranchID, $this->param->MainID))){
                $additional[] = ["ID" => "Item", "Message" => "Item ".$detail['ItemID']." is not Invalid"];
                $response = $this->generateResponse(1, $additional, "Please check input");
                return response()->json($response);
            }
        }
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
        
        $this->db_trans_start();
        $response = $this->generateResponse(0, [], "Success", []);
        $this->meta->deleteDetail('ServiceInventoryUsage',@$input[$id_name], $id_name);
        for($i = 0; $i < count(@$input['Detail']); $i++){
            $detail = @$input['Detail'][$i];
            $data = [
                        'ItemID' => @$detail['ItemID'],
                        'InventoryUnitTypeID' => @$detail['InventoryUnitTypeID'],
                        $id_name => @$input[$id_name],
                        'Qty' => @$detail['Qty']
                ];
            $insertedData = $this->meta->upsert('ServiceInventoryUsage', $data, null, null);
            if(isset($insertedData['error'])){ 
                if($this->environment != 'live') $errorMsg = $insertedData['message'];
                else $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Database Error");
            }else{
                $response = $this->generateResponse(0, [], "Success", ['ServiceInventoryUsage'=>$insertedData]);
            }
        }
        $this->db_trans_end();
        return response()->json($response);
    }
    
    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id_name = (@$input['SubServiceID'] !== null) ? 'SubServiceID' : 'ServiceID';
        $id = @$input[$id_name];
        $select =  array(
            'siu.ItemID',
            'siu.Qty',
            'i.ItemName',
            'i.ItemCode',
            'c.CategoryCode',
            'c.CategoryName',
            'iut.*'
        );
        $find = DB::table('ServiceInventoryUsage AS siu')->select($select)
            ->join('Item AS i', 'i.ItemID', '=', 'siu.ItemID')
            ->join('Category as c', 'c.CategoryID', '=', 'i.CategoryID')
            ->join('InventoryUnitType as iut', 'iut.InventoryUnitTypeID', '=', 'siu.InventoryUnitTypeID')
            ->where($id_name, $id)->get();
        $response = $this->generateResponse(0, [], "Success", ['Data'=>@$find]);
        return response()->json($response);
    }
}
