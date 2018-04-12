<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use Service\Interfaces\ServiceUsage;
use DB;

class ServiceUserMappingController extends BaseController
{
    public function __construct(ServiceUsage $service, Meta $meta, Request $request){
        $this->meta = $meta;
        $this->service = $service;
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
            'UserCode',
            'Fullname',
            'UserTypeName',
            'UserTypeCode'
        ];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $table = 'ServiceUserMapping';
        $total = DB::table($table)->distinct('UserID')->where('BranchID', $this->param->BranchID)->where('BrandID', $this->param->MainID)->count('UserID');
        $display = [
            'ServiceUserMapping.UserID',
            'UserCode',
            'Fullname',
            'UserTypeName',
            'UserTypeCode'
        ];
        $searchable = [
            'UserCode',
            'Fullname',
            'UserTypeName',
            'UserTypeCode'
        ];
        $join = [
            ['join', 'User AS u','u.UserID', '=', $table.'.UserID'],
            ['join', 'UserType AS ut','ut.UserTypeID', '=', 'u.UserTypeID']
        ];
        $data = $this->meta->getDataTableTransaction($table, $display, $searchable, $perPage, $start, $orderBy, $sort, $keyword,$this->param, $join);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    public function detail(){
        $input = json_decode($this->request->getContent(),true);
        $id = $input['UserID'];
        $select = array(
              
        );
        $find = DB::table('ServiceUserMapping')->join('SubService', 'SubService.SubServiceID', '=', 'ServiceUserMapping.SubServiceID')->where('UserID', $id)->get();
        $response = $this->generateResponse(0, [], "Success", ['Data'=>@$find]);
        return response()->json($response);
        
    }
    

    public function detail_sub(){
        $input = json_decode($this->request->getContent(),true);
        $id = $input['SubServiceID'];
        $select = array(
            'ServiceUserMappingID',
            'ServiceUserMapping.UserID',
            'SubServiceID',
            'PriceModifier',
            'CommissionModifierPercent',
            'CommissionModifierValue',
            'UserCode',
            'Fullname'
        );
        $find = DB::table('ServiceUserMapping')->select($select)->join('User', 'User.UserID', '=', 'ServiceUserMapping.UserID')->where('SubServiceID', $id)->get();
        $response = $this->generateResponse(0, [], "Success", ['Data'=>@$find]);
        return response()->json($response);
        
    }
    
    
    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $rules = [
                    'UserID' => 'required'
                ];
        if(@$input['UserID'] != null && empty($this->meta->checkUnique('User', 'UserID', @$input['UserID'], $this->param->BranchID, $this->param->MainID))){
            $additional[] = ["ID" => "User", "Message" => "User ".@$input['UserID']." is not Invalid"];
            $response = $this->generateResponse(1, $additional, "Please check input");
            return response()->json($response);
        }
        
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }
        $this->db_trans_start();
        for($i = 0; $i < count($input['Data']); $i++){
            $data = $input['Data'][$i];
            $rules = [
                    'SubServiceID' => 'required',
                    'PriceModifier' => 'numeric|required',
                    'CommissionModifierValue' => 'numeric|required_without_all:CommissionModifierPercent',
                    'CommissionModifierPercent' => 'numeric|required_without_all:CommissionModifierValue'
                ];
            if(@$data['SubServiceID'] != null && empty($this->meta->checkUnique('SubService', 'SubServiceID', @$data['SubServiceID'], $this->param->BranchID, $this->param->MainID))){
                $additional[] = ["ID" => "SubService", "Message" => "SubService ".@$data['SubServiceID']." is not Invalid"];
                $response = $this->generateResponse(1, $additional, "Please check input");
                return response()->json($response);
            }

            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                // validation error
                $errors = $validator->errors();
                $errorList = $this->checkErrors($rules, $errors);
                $additional = null;
                $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
                return response()->json($response);
            }
        }
        DB::table('ServiceUserMapping')->where('UserID', $input['UserID'])->delete();

        // validate input
        
        for($i = 0; $i < count($input['Data']); $i++){
            $data = $input['Data'][$i];
            $d = [
                        'UserID' => @$input['UserID'],
                        'SubServiceID' => $data['SubServiceID'],
                        'PriceModifier' => $this->coalesce(@$data['PriceModifier'], 0),
                        'CommissionModifierValue' => @$data['CommissionModifierValue'],
                        'CommissionModifierPercent' => @$data['CommissionModifierPercent'],
                        'BrandID' => $this->param->MainID,
                        'BranchID' => $this->param->BranchID
                ];
            $insertedData = $this->meta->upsert('ServiceUserMapping', $d, $this->param);
            
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
    
    public function save_sub()
    {
        $input = json_decode($this->request->getContent(),true);
        $rules = [
                    'SubServiceID' => 'required'
                ];
        if(@$input['SubServiceID'] != null && empty($this->meta->checkUnique('SubService', 'SubServiceID', @$input['SubServiceID'], $this->param->BranchID, $this->param->MainID))){
            $additional[] = ["ID" => "SubService", "Message" => "SubService ".@$input['SubServiceID']." is not Invalid"];
            $response = $this->generateResponse(1, $additional, "Please check input");
            return response()->json($response);
        }
        
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }
        $this->db_trans_start();
        for($i = 0; $i < count($input['Data']); $i++){
            $data = $input['Data'][$i];
            $rules = [
                    'UserID' => 'required',
                    'PriceModifier' => 'numeric|required',
                    'CommissionModifierValue' => 'numeric|required_without_all:CommissionModifierPercent',
                    'CommissionModifierPercent' => 'numeric|required_without_all:CommissionModifierValue'
                ];
            if(@$data['UserID'] != null && empty($this->meta->checkUnique('User', 'UserID', @$data['UserID'], $this->param->BranchID, $this->param->MainID))){
                $additional[] = ["ID" => "User", "Message" => "User ".@$data['UserID']." is not Invalid"];
                $response = $this->generateResponse(1, $additional, "Please check input");
                return response()->json($response);
            }

            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                // validation error
                $errors = $validator->errors();
                $errorList = $this->checkErrors($rules, $errors);
                $additional = null;
                $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
                return response()->json($response);
            }
        }
        DB::table('ServiceUserMapping')->where('SubServiceID', $input['SubServiceID'])->delete();

        // validate input
        
        for($i = 0; $i < count($input['Data']); $i++){
            $data = $input['Data'][$i];
            $d = [
                        'UserID' => @$data['UserID'],
                        'SubServiceID' => @$input['SubServiceID'],
                        'PriceModifier' => $this->coalesce(@$data['PriceModifier'], 0),
                        'CommissionModifierValue' => @$data['CommissionModifierValue'],
                        'CommissionModifierPercent' => @$data['CommissionModifierPercent'],
                        'BrandID' => $this->param->MainID,
                        'BranchID' => $this->param->BranchID
                ];
            $insertedData = $this->meta->upsert('ServiceUserMapping', $d, $this->param);
            
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
    
}
