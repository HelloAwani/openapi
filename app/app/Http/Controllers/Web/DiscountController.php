<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use Service\Interfaces\Discount;

class DiscountController extends BaseController
{
    public function __construct(Discount $discount, Meta $meta, Request $request){
        $this->meta = $meta;
        $this->discount = $discount;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }

    public function datatables()
    {
        $input = json_decode($this->request->getContent(),true);
        $draw = $this->coalesce(@$input['Draw'],1);
        $orderBy = $this->coalesce(@$input['OrderBy'], 'DiscountName');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = $this->coalesce(@$input['PageSize'], 10);
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $sortableColumn = ["DiscountName","StartDate", "EndDate", "StartHour", "EndHour" ];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $total = $this->meta->totalRecords('Discount', $this->param);
        $table = 'Discount';
        $display = [
            'DiscountID',
            'DiscountName',
            'StartDate',
            'EndDate',
            'StartHour',
            'EndHour',
            'Description',
            'PaymentMethodName'];
        $searchable = ['DiscountName', 'PaymentMethodName', 'Description', 'StartDate', 'EndDate', 'StartHour', 'EndHour'];
        $data = $this->discount->getDataTable($display, $searchable, $perPage, $start, $orderBy, $sort, $keyword,$this->param);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['DiscountID'];
        if(@$input['DiscountType'] == 'P')
            $input['Global'] = 1;
        $rules = [
                    'DiscountName' => 'required',
                    'StartDate' => 'nullable|date_format:Y-m-d',
                    'EndDate' => 'nullable|date_format:Y-m-d',
                    'StartHour' => 'nullable|date_format:H:i',
                    'EndHour' => 'nullable|date_format:H:i',
                    'Description' => 'nullable|max:1024',
                    'DiscountValue' => 'nullable|numeric',
                    'DiscountPercent' => 'nullable|numeric',
                    'DiscountFlat' => 'nullable|numeric',
                    'DiscountType' => 'required|typeFilter',
                    'Global' => 'yesnoFilter',
                    'Active' => 'yesnoFilter',
                    'AfterTax' => 'yesnoFilter',
                    'AfterServiceCharge' => 'yesnoFilter'
                ];
        if($input['Global'] == 0)
            $rules['Detail'] = 'required|array';
        $messages = [
            'type_filter' => 'The :attribute choices must be P or I or S',
            'yesno_filter' => 'The :attribute choices must 0 or 1'
        ];
        if(@$input['PaymentMethodID'] != null && @$input['PaymentMethodID'] != '0' && empty($this->meta->checkUnique('PaymentMethod', 'PaymentMethodID', @$input['PaymentMethodID'], $this->param->BranchID, $this->param->MainID))){
            $additional[] = ["ID" => "PaymentMethod", "Message" => "PaymentMethodID ".@$input['PaymentMethodID']." is not Invalid"];
            $response = $this->generateResponse(1, $additional, "Please check input");
            return response()->json($response);
        }
        
        
        Validator::extend('typeFilter', function($attribute, $value, $parameters, $validator) {
            if(strtoupper($value) == 'P' || strtoupper($value) == 'I' || strtoupper($value) == 'S') return true;
            else return false;
        });
        Validator::extend('yesnoFilter', function($attribute, $value, $parameters, $validator) {
            if($value == '0' || $value == '1') return true;
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

        // validate input
        $data = [
                    'DiscountName' => @$input['DiscountName'],
                    'StartDate' => @$input['StartDate'],
                    'EndDate' => @$input['EndDate'],
                    'StartHour' => @$input['StartHour'],
                    'EndHour' => @$input['EndHour'],
                    'Description' => @$input['Description'],
                    'DiscountValue' => @$input['DiscountValue'],
                    'DiscountPercent' => @$input['DiscountPercent'],
                    'DiscountFlat' => @$input['DiscountFlat'],
                    'DiscountType' => @$input['DiscountType'],
                    'Global' => @$input['Global'],
                    'Active' => @$input['Active'],
                    'AfterTax' => @$input['AfterTax'],
                    'AfterServiceCharge' => @$input['AfterServiceCharge'],
                    'PaymentMethodID' => @$input['PaymentMethodID']
            ];
        $this->db_trans_start();
        if($id !== null ){
            $find = $this->meta->find('SubService', $id);
            if(count($find) <= 0){
                $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            } else {
                $delete = $this->meta->deleteDetail('DiscountDetail', $id, 'DiscountID');
                
            }
        } else {
            $data["BrandID"] = $this->param->MainID;
            $data["BranchID"] = $this->param->BranchID;
        }
        $insertedData = $this->meta->upsert('Discount', $data, $this->param, $id);
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['Discount'=>$insertedData]);
        }
        if(@$input['Global'] == 0){
            if($id == null)
                $id = $this->getLastVal();
            if(@$input['DiscountType'] == 'I') $product = 'Item';
            else $product = 'Service';
            $detail = @$input['Detail'];
            // check detail
            for($i = 0; $i < count($detail); $i++){
                $details = $detail[$i];
                $rules = [
                    'ProductID' => 'required'
                ];
                $validator = Validator::make($details, $rules);
                if ($validator->fails()) {
                    // validation error
                    $errors = $validator->errors();
                    $errorList = $this->checkErrors($rules, $errors);
                    $additional = null;
                    $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
                    return response()->json($response);
                }
                if(@$details['ProductID'] != null && empty($this->meta->checkUnique($product, $product.'ID', @$details['ProductID'], $this->param->BranchID, $this->param->MainID))){
                    $additional[] = ["ID" => "Product", "Message" => "Product ".@$input['ProductID']." is not Invalid"];
                    $response = $this->generateResponse(1, $additional, "Please check input");
                    return response()->json($response);
                }
            }
            // insert detail
            for($i = 0; $i < count($detail); $i++){
                $details = $detail[$i];
                $data = [
                    'DiscountID' => $id,
                    'ProductID' => @$details['ProductID'],
                    'BrandID' => $this->param->MainID,
                    'BranchID' => $this->param->BranchID
                ];
                $insertedData = $this->meta->upsert('DiscountDetail', $data, $this->param);
                if(isset($insertedData['error'])){ 
                    if($this->environment != 'live') $errorMsg = $insertedData['message'];
                    else $errorMsg = "Database Error"; 
                    $response = $this->generateResponse(1, $errorMsg, "Database Error");
                }
            }
        }
        $this->db_trans_end();
        return response()->json($response);
    }
    
    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['DiscountID'];
        $find = $this->meta->find('Discount', $id);
        if(count($find) <= 0){
            $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Data not found");
            return response()->json($response);
        } else {
            @$find[0]->Detail = $this->meta->findDetail('DiscountDetail', $id, 'DiscountID');
        }

        $response = $this->generateResponse(0, [], "Success", ['Data'=>@$find[0]]);
        return response()->json($response);
    }
    public function delete()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['DiscountID'];
        $delete = $this->meta->delete('Discount', $id);
        $delete = $this->meta->deleteDetail('DiscountDetail', $id, 'DiscountID');
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
