<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use Service\Interfaces\General;

class GeneralSettingController extends BaseController
{
    
    public function __construct(General $general, Meta $meta, Request $request){
        $this->meta = $meta;
        $this->general = $general;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }

    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        for($i = 0; $i < count($input['Data']); $i++){
            $data = $input['Data'][$i];
            $insertData = [
                "GeneralSettingValue" => $data['GeneralSettingValue'],
                "GeneralSettingTypeID" => $data['GeneralSettingTypeID']
            ];  
            $find = $this->general->find($this->param, $data['GeneralSettingTypeID']);
            $id = @$find[0]->GeneralSettingID;
            if($id == null){
                $insertData["BrandID"] = $this->param->MainID;
                $insertData["BranchID"] = $this->param->BranchID;
            }
            $insertedData = $this->general->upsert($insertData, $this->param, $id);
            if(isset($insertedData['error'])){ 
                if($this->environment != 'live') $errorMsg = $insertedData['message'];
                else $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Database Error");
            }
            
            
        }
        $response = $this->generateResponse(0, [], "Success", []);
        return response()->json($response);
    }
    
    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $find = $this->general->get($this->param);

        $response = $this->generateResponse(0, [], "Success", ['Data'=>$find]);
        return response()->json($response);
    }
}
