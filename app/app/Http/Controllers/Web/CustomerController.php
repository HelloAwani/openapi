<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use Service\Interfaces\SubService;

class CustomerController extends BaseController
{
    
    public function __construct( Meta $meta, Request $request){
        $this->meta = $meta;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
        $this->table = 'Customer';
        $this->display = [
                "CustomerCode",
                "CustomerName",
                "CustomerID",
                "Email",
                "IDNumber",
                "Gender",
                "Address",
                "Photo",
                "DOB",
                "Note",
                "PhoneNumber"
            ];
        $this->searchable = [
            "CustomerCode",
            "CustomerName",
            "Email",
            "IDNumber",
            "Gender"
        ]; 
        $this->sortableColumn = [
            "CustomerCode",
            "CustomerName", 
            "Email",
            "Gender"
        ];
        
    }

    public function datatables()
    {
        $input = json_decode($this->request->getContent(),true);
        $draw = $this->coalesce(@$input['Draw'],1);
        $orderBy = $this->coalesce(@$input['OrderBy'], 'CustomerCode');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = $this->coalesce(@$input['PageSize'], 10);
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        if(!in_array($orderBy,$this->sortableColumn)) $orderBy = null;
        $total = $this->meta->totalRecords($this->table, $this->param);
        $data = $this->meta->getDataTable($this->table, $this->display, $this->searchable, $perPage, $start, $orderBy, $sort, $keyword,$this->param, null, null);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$this->sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['CustomerID'];
        $rules = [
                    'CustomerCode' => 'required',
                    'CustomerName' => 'required',
                    'Email' => 'email|nullable',
                    'DOB' => 'date_format:Y-m-d|nullable'
                ];
        if(isset($input['CustomerCode']) && !empty($this->meta->checkUnique($this->table, 'CustomerCode', $input['CustomerCode'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['CustomerCode'] = 'required|unique:'.$this->table;
        }
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            if($errors->first('CustomerCode')){
                if(strpos($errors->first('CustomerCode'),'taken') !== false){
                    $additional = ["CustomerCode" => 'Already Taken'];
                }
            }
            
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }

        // validate input
        
        $data = [
            "CustomerName" => @$input['CustomerName'],
            "PhoneNumber" => @$input['PhoneNumber'],
            "Email" => @$input['Email'],
            "Note" => @$input['Note'],
            "DOB" => @$input['DOB'],
            "Gender" => @$input['Gender'],
            "CustomerCode" => @$input['CustomerCode'],
            "Address" => @$input['Address'],
            "IDNumberType" => @$input['IDNumberType'],
            "IDNumber" => @$input['IDNumber']
        ];
        if($id !== null ){
            $find = $this->meta->find($this->table, $id);
            if(count($find) <= 0){
                $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
        } else {
            $data["BrandID"] = $this->param->MainID;
            $data["BranchID"] = $this->param->BranchID;
        }
        $insertedData = $this->meta->upsert($this->table, $data, $this->param, $id);
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['Data'=>$insertedData]);
        }
        return response()->json($response);
    }
    
    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['CustomerID'];
        $find = $this->meta->find($this->table, $id);
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
        $id = $input['CustomerID'];
        $delete = $this->meta->delete($this->table, $id);
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
