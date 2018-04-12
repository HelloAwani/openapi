<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use DB;

class ExpenseController extends BaseController
{
    public function __construct(Meta $meta, Request $request){
        $this->meta = $meta;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
        $this->table = 'Expense';
    }

    public function datatables()
    {
        $input = json_decode($this->request->getContent(),true);
        $draw = $this->coalesce(@$input['Draw'],1);
        $orderBy = $this->coalesce(@$input['OrderBy'], 'Date');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = $this->coalesce(@$input['PageSize'], 1);
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $total = $this->meta->totalRecords($this->table, $this->param);
        $display = [
            'ExpenseID',
            'ExpenseTypeName', 
            'Date',
            'Amount', 
            'Note'
        ];
        $searchable = [
            'ExpenseTypeName',
            'Date', 
            'Amount', 
            'Note'
        ];
        $sortableColumn = [
            "ExpenseTypeName",
            "Date",
            "Amount"
        ];
        $join = [
            ['leftJoin', 'ExpenseType AS et','et.ExpenseTypeID', '=', $this->table.'.ExpenseTypeID']
        ];
        if(@$input['ExpenseTypeID'] != null)
            $extraWhere[] = ['Expense.ExpenseTypeID', @$input['ExpenseTypeID']];
        
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;
        $data = $this->meta->getDataTable($this->table, $display, $searchable, $perPage, $start, $orderBy, $sort, $keyword,$this->param, $join);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['ExpenseID'];
        $rules = [
            'ExpenseTypeID' => 'required|exists:ExpenseType,ExpenseTypeID,BranchID,'.$this->param->BranchID,
            'Date' => 'required|date_format:Y-m-d',
            'Amount' => 'required|numeric|min:0'
        ];
        
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }
        
        $data = [
            "ExpenseTypeID" => @$input['ExpenseTypeID'],
            "Date" => @$input['Date'],
            "Amount" => @$input['Amount'],
            "Note" => @$input['Note']
        ];
        
        if($id !== null ){
            $find = $this->meta->find('Expense', $id);
            if(count($find) <= 0){
                $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
        } else {
            $data["BrandID"] = $this->param->MainID;
            $data["BranchID"] = $this->param->BranchID;
        }
        $insertedData = $this->meta->upsert('Expense', $data, $this->param, $id);
        if(@$id === null){
            $id = $this->getLastVal();   
        }
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['Data'=>$insertedData, 'ID' => $id]);
        }
        return response()->json($response);
    }

    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['ExpenseID'];
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
        $id = $input['ExpenseID'];
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
