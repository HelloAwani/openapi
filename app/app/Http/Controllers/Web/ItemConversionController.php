<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use Service\Interfaces\ItemConversion;
use DB;

class ItemConversionController extends BaseController
{
    public function __construct(ItemConversion $itemConversion, Meta $meta, Request $request){
        $this->meta = $meta;
        $this->itemConversion = $itemConversion;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }

    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['ItemID'];
        $rules = [
            'ItemID' => 'required',
            'Detail' => 'required|array',
            'Detail.*.ToInventoryUnitTypeID' => 'required|exists:InventoryUnitType,InventoryUnitTypeID|distinct',
            'Detail.*.ConversionRate' => 'required|numeric|greaterZero'
        ];
        
        $messages = [
            'greater_zero' => 'The :attribute must greater than 0'
        ];
        // validate input
        Validator::extend('greaterZero', function($attribute, $value, $parameters, $validator) {
            if($value > 0 ) return true;
            else return false;
        });
        if(@$input['ItemID'] != null && empty($this->meta->checkUnique('Item', 'ItemID', @$input['ItemID'], $this->param->BranchID, $this->param->MainID))){
            $additional[] = ["ID" => "Item", "Message" => "Item ".@$input['ItemID']." is not Invalid"];
            $response = $this->generateResponse(1, $additional, "Please check input");
            return response()->json($response);
        }
        
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
        $this->meta->deleteDetail('ItemConversion', $id, 'ItemID');
        
        for($i = 0; $i < count(@$input['Detail']); $i++){
            $detail = @$input['Detail'][$i];
            $data = [
                'ItemID' => $id,
                'ToInventoryUnitTypeID' => @$detail['ToInventoryUnitTypeID'],
                'ConversionRate' => @$detail['ConversionRate']
            ];
            $insertedData = $this->meta->upsert('ItemConversion', $data, $this->param);
            if(isset($insertedData['error'])){ 
                if($this->environment != 'live') $errorMsg = $insertedData['message'];
                else $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Database Error");
            }else{
                $response = $this->generateResponse(0, [], "Success", []);
            }
        }
        $this->db_trans_end();
        return response()->json($response);
    }
    
    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['ItemID'];
        $find = DB::table('ItemConversion')->join('InventoryUnitType AS iut', 'iut.InventoryUnitTypeID', '=', 'ItemConversion.ToInventoryUnitTypeID')->where('ItemID', $id)->get();
        $response = $this->generateResponse(0, [], "Success", ['Data'=>@$find]);
        return response()->json($response);
    }
}
