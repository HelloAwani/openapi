<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;

class PaymentMethodController extends BaseController
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
        $orderBy = $this->coalesce(@$input['OrderBy'], 'PaymentMethod');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = $this->coalesce(@$input['PageSize'], 10);
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $sortableColumn = ["PaymentMethodName","Type"];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $total = $this->meta->totalRecords('PaymentMethod', $this->param);
        $data = $this->meta->getDataTable('PaymentMethod', ['PaymentMethodID', 'PaymentMethodName', 'Type'], ['PaymentMethodName'], $perPage, $start, $orderBy, $sort, $keyword,$this->param);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['PaymentMethodID'];
        $rules = [
                    'PaymentMethodName' => 'required',
                    'Type' => 'required|typeFilter'
                ];
        if(isset($input['PaymentMethodName']) && !empty($this->meta->checkUnique('PaymentMethod', 'PaymentMethodName', $input['PaymentMethodName'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['PaymentMethodName'] = 'required|unique:PaymentMethod';
        }
        $messages = [
            'type_filter' => 'The :attribute choices must be Other or Credit or Debet'
        ];
        Validator::extend('typeFilter', function($attribute, $value, $parameters, $validator) {
            if(strtoupper($value) == 'O' || strtoupper($value) == 'C'|| strtoupper($value) == 'D') return true;
            else return false;
        });
        $validator = Validator::make($input, $rules, $messages);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            if($errors->first('PaymentMethodName')){
                if(strpos($errors->first('PaymentMethodName'),'taken') !== false){
                    $additional = ["PaymentMethodName" => ''];
                }
            }
            
            
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }

        // validate input
        $data = ["PaymentMethodName" => @$input['PaymentMethodName'],
                 "Type" => @$input['Type'],
                 "PredefinedPaymentMethodID" => @$input['PredefinedPaymentMethodID']];
        if($id !== null ){
            $find = $this->meta->find('PaymentMethod', $id);
            if(count($find) <= 0){
                $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
        } else {
            $data["BrandID"] = $this->param->MainID;
            $data["BranchID"] = $this->param->BranchID;
        }
        
        $insertedData = $this->meta->upsert('PaymentMethod', $data, $this->param, $id);
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['UserType'=>$insertedData]);
        }
        return response()->json($response);
    }

    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['PaymentMethodID'];
        $find = $this->meta->find('PaymentMethod', $id);
        if(count($find) <= 0){
            $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Data not found");
            return response()->json($response);
        }

        $response = $this->generateResponse(0, [], "Success", ['Data'=>@$find[0]]);
        return response()->json($response);
    }
    public function delete()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['PaymentMethodID'];
        $delete = $this->meta->delete('PaymentMethod', $id);
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
