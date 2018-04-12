<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use Service\Interfaces\SubService;
use DB;

class SubServiceController extends BaseController
{
    public function __construct(SubService $subservice, Meta $meta, Request $request){
        $this->meta = $meta;
        $this->subservice = $subservice;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }

    public function datatables()
    {
        $input = json_decode($this->request->getContent(),true);
        $table = 'SubService';
        $display = [
            'SubService.SubServiceID',
            'SubServiceCode',
            'SubServiceName',
            'SubService.CommissionValue',
            'SubService.SubPrice',
            'SubService.CommissionPercent',
            'SubServiceDuration',
            'ServiceName',
            'DurationUnitName'];
        $data = DB::table($table)->select($display)
            ->join('DurationUnit', 'SubService.DurationUnitID', '=', 'DurationUnit.DurationUnitID')
            ->join('Service', 'SubService.ServiceID', '=', 'Service.ServiceID')
            ->where('SubService.BranchID', $this->param->BranchID)
            ->where('SubService.BrandID', $this->param->MainID)
            ->where('SubService.Archived', null)
            ->where('SubService.ServiceID', @$input['ServiceID'])->get();
        $response = $this->generateResponse(0, [], "Success", ['Data'=> $data]);
        return response()->json($response);
    }

    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['SubServiceID'];
        $rules = [
            'ServiceID' => 'required',
            'SubServiceCode' => 'required',
            'SubServiceName' => 'required',
            'CommissionValue' => 'numeric|required_without_all:CommissionPercent',
            'CommissionPercent' => 'numeric|required_without_all:CommissionValue',
            'SubPrice' => 'numeric|required|min:0',
            'Mandatory' => 'levelFilter|nullable',
            'DurationUnitID' => 'numeric|required|exists:DurationUnit,DurationUnitID',
            'SubServiceDuration' => 'required|numeric|min:0'
        ];
        if(isset($input['SubServiceCode']) && !empty($this->meta->checkUnique('SubService', 'SubServiceCode', $input['SubServiceCode'], $this->param->BranchID, $this->param->MainID, $id))){
            $rules['SubServiceCode'] = 'required|unique:SubService';
        }
        if(@$input['ServiceID'] != null && empty($this->meta->checkUnique('Service', 'ServiceID', @$input['ServiceID'], $this->param->BranchID, $this->param->MainID))){
            $additional[] = ["ID" => "Service", "Message" => "ServiceID ".@$input['ServiceID']." is not Invalid"];
            $response = $this->generateResponse(1, $additional, "Please check input");
            return response()->json($response);
        }
        
        
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
            if($errors->first('SubServiceCode')){
                if(strpos($errors->first('SubServiceCode'),'taken') !== false){
                    $additional = ["SubService" => 'Already Taken'];
                }
            }
            
            
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }

        // validate input
        
        $data = ["ServiceID" => @$input['ServiceID'],
                 "SubServiceCode" => @$input['SubServiceCode'],
                 "SubServiceName" => @$input['SubServiceName'],
                 "SubPrice" => @$input['SubPrice'],
                 "Mandatory" => $this->coalesce(@$input['Mandatory'], 0),
                 "CommissionValue" => @$input['CommissionValue'],
                 "DurationUnitID" => @$input['DurationUnitID'],
                 "CommissionPercent" => @$input['CommissionPercent'],
                 "SubServiceDuration" => @$input['SubServiceDuration']];
        if($id !== null ){
            $find = $this->meta->find('SubService', $id);
            if(count($find) <= 0){
                $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
        } else {
            $data["BrandID"] = $this->param->MainID;
            $data["BranchID"] = $this->param->BranchID;
        }
        if(@$input['DeleteImage'] === true){
            $data = ["Image" => null];
        }
        $insertedData = $this->meta->upsert('SubService', $data, $this->param, $id);
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
                'Folder' => 'SubService',
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
        $id = $input['SubServiceID'];
        $find = $this->meta->find('SubService', $id);
        if(count($find) <= 0){
            $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Data not found");
            return response()->json($response);
        }

        $response = $this->generateResponse(0, [], "Success", ['Item'=>@$find[0]]);
        return response()->json($response);
    }
    public function delete()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['SubServiceID'];
        $delete = $this->meta->delete('SubService', $id);
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
