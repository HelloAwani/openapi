<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use DB;

class ServiceController extends BaseController
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
        $orderBy = $this->coalesce(@$input['OrderBy'], 'ServiceCode');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = @$input['PageSize'];
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $sortableColumn = ["ServiceCode","ServiceName"];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $total = $this->meta->totalRecords('Service', $this->param);
        $data = $this->getDataTable('Service', ['Service.ServiceID', 'Service.ServiceCode', 'Service.ServiceName', 'Service.CommissionValue', 'Service.CommissionPercent','Service.ServiceDuration', 'Service.Price', 'DurationUnitName', 'ServiceLevelCommission', 'CommissionFormulaCode', 'ServicerIncluded', 'CommissionType'], ['ServiceCode', 'ServiceName'], $perPage, $start, $orderBy, $sort, $keyword,$this->param);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    
	public function getDataTable($table, $display, $column, $perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param){
		$offset = $start;
		$result = DB::table($table)->select($display)->where($table.'.BranchID', $param->BranchID)->where($table.'.BrandID', $param->MainID)->where($table.'.Archived', null)->leftJoin('DurationUnit', 'DurationUnit.DurationUnitID', '=', $table.'.DurationUnitID')->leftJoin('CommissionFormula', 'CommissionFormula.CommissionFormulaID', '=', $table.'.CommissionFormulaID');
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword, $column){
                for($i = 0; $i < count($column);$i++){
                    $query->orWhere(DB::raw('lower(trim("'.$column[$i].'"::varchar))'),'like','%'.strtolower ($keyword).'%');
                }
        	});	
        }
        $totalFiltered = $result->count();
        $maxPage =  $perPage==null ? 1 : ceil($totalFiltered/$perPage);
        if(!empty($orderBy)){
        	if(strtolower($sort) != 'asc' && strtolower($sort) != 'desc') $sort = 'asc';
        	$result = $result->orderBy($orderBy,$sort);
        }
        $result = $result->skip($offset);
        $result = $perPage==null ? $result : $result->take($perPage);
		$response = ['recordsFiltered' => $totalFiltered, 'maxPage' => $maxPage, 'data' => $result->get()];
		return $response;
	}

    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['ServiceID'];
        $rules = [
                    'ServiceCode' => 'required',
                    'ServiceName' => 'required',
                    'Price' => 'numeric|required|min:0',
                    'CommissionValue' => 'numeric|required_without_all:CommissionPercent',
                    'CommissionPercent' => 'numeric|required_without_all:CommissionValue',
                    'ServiceLevelCommission' => 'levelFilter|nullable',
                    'DurationUnitID' => 'numeric|required|exists:DurationUnit,DurationUnitID',
//                    'CommissionFormulaID' => 'numeric|required|exists:CommissionFormula,CommissionFormulaID',
//                    'ServicerIncluded' => 'levelFilter',
//                    'CommissionType' => 'required|comFilter',
                    'ServiceDuration' => 'required|numeric'
                ];
        $messages = [
            'level_filter' => 'The :attribute choices must be 0 or 1',
            'com_filter' => 'The :attribute choices must be 1 or 2'
        ];
        if(isset($input['ServiceCode']) && !empty($this->meta->checkUnique('Service', 'ServiceCode', $input['ServiceCode'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['ServiceCode'] = 'required|unique:Service';
        }
        
        Validator::extend('levelFilter', function($attribute, $value, $parameters, $validator) {
            if(strtoupper($value) == 0 || strtoupper($value) == 1) return true;
            else return false;
        });
        Validator::extend('comFilter', function($attribute, $value, $parameters, $validator) {
            if(strtoupper($value) == 2 || strtoupper($value) == 1) return true;
            else return false;
        });
        $validator = Validator::make($input, $rules, $messages);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            if($errors->first('ServiceCode')){
                if(strpos($errors->first('ServiceCode'),'taken') !== false){
                    $additional = ["Service" => 'Already Taken'];
                }
            }
            
            
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }
//        $formula = DB::table('CommissionFormula')->where('CommissionFormulaID', @$input['CommissionFormulaID'])->where(function ($query) use($input){
//            $query->orWhere('Status', 3)->orWhere('Status', @$input['CommissionType']);
//        });	
//        $formula = $formula->count();
//        if($formula == 0){
//            $errorList = array(
//                array(
//                    'ID' => 'Error',
//                    'Message' => 'Invalid Input'
//                )
//            );
//            $response = $this->generateResponse(1, $errorList, "Please check input", []);
//            return response()->json($response);
//        }
        if($id !== null ){
            $find = $this->meta->find('Service', $id);
            if(count($find) <= 0){
                $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
            $data = [
                "ServiceLevelCommission" => $this->coalesce(@$input['ServiceLevelCommission'], 0),
                "ServiceName" => @$input['ServiceName'], 
                "ServiceCode" => @$input['ServiceCode'], 
                "Price" => @$input['Price'], 
                "CommissionValue" => @$input['CommissionValue'],
                "CommissionPercent" => @$input['CommissionPercent'],
                "DurationUnitID" => @$input['DurationUnitID'],
                "CommissionFormulaID" => @$input['CommissionFormulaID'],
                "ServicerIncluded" => @$input['ServicerIncluded'],
                "CommissionType" => @$input['CommissionType'],
                "ServiceDuration" => @$input['ServiceDuration']
            ];
        } else {
            $data = [
                "ServiceName" => @$input['ServiceName'],
                "ServiceCode" => @$input['ServiceCode'], 
                "Price" => @$input['Price'], 
                "ServiceLevelCommission" => $this->coalesce(@$input['ServiceLevelCommission'], 0),
                "CommissionValue" => @$input['CommissionValue'],
                "CommissionPercent" => @$input['CommissionPercent'], 
                "DurationUnitID" => @$input['DurationUnitID'],
                "ServiceDuration" => @$input['ServiceDuration'], 
                "CommissionFormulaID" => @$input['CommissionFormulaID'],
                "ServicerIncluded" => @$input['ServicerIncluded'],
                "CommissionType" => @$input['CommissionType'],
                "BrandID" => $this->param->MainID, 
                "BranchID" => $this->param->BranchID
            ];
        }
        if(@$input['DeleteImage'] === true){
            $data = ["Image" => null];
        }
        $insertedData = $this->meta->upsert('Service', $data, $this->param, $id);
        if(@$id === null){
            $id = $this->getLastVal();   
        }
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['Service'=>$insertedData]);
        }
        if(@$input['Images'] !== null){
            $arr = array(
                'BrandID' => $this->param->MainID,
                'BranchID' => $this->param->BranchID,
                'ObjectID' => $id,
                'Folder' => 'Service',
                'Filename' => @$input['Images']['Filename'], 
                'Data' =>  @$input['Images']['Data']
            );
            $path = $this->upload_to_s3($arr);
            $data = ["Image" => $path];
            $insertedData = $this->itemCategory->upsert($data, $id);
            $response = $this->generateResponse(0, [], "Success", ['Service'=>$insertedData]);
        }
        return response()->json($response);
    }

    
    public function import()
    {
        $input = json_decode($this->request->getContent(),true);
        $rules = [
            'Data' => 'required|array',
            'Data.*.ServiceCode' => 'required|distinct',
            'Data.*.ServiceName' => 'required',
            'Data.*.Price' => 'numeric|required|min:0',
            'Data.*.CommissionValue' => 'numeric|required_without_all:Data.*.CommissionPercent',
            'Data.*.CommissionPercent' => 'numeric|required_without_all:Data.*.CommissionValue',
            'Data.*.ServiceLevelCommission' => 'levelFilter|nullable',
            'Data.*.DurationUnitName' => 'required|exists:DurationUnit,DurationUnitName',
            'Data.*.ServiceDuration' => 'required|numeric'
        ];
        
        $niceNames = array(
            'Data.*.ServiceCode' => 'Service Code',
            'Data.*.ServiceName' => 'Service Name',
            'Data.*.Price' => 'Price',
            'Data.*.CommissionValue' => 'Commission Value',
            'Data.*.CommissionPercent' => 'Commission Percent',
            'Data.*.ServiceLevelCommission' => 'Service Level Commission',
            'Data.*.DurationUnitName' => 'Duration Unit',
            'Data.*.ServiceDuration' => 'Service Duration'
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
        $duration = DB::table('DurationUnit')->get();
        $temp = array();
        foreach($duration as $d){
            $temp[$d->DurationUnitName] = $d->DurationUnitID;
        }
        for($i = 0; $i < count(@$input['Data']); $i++){
            $detail = @$input['Data'][$i];
            $id = @$this->meta->checkUniqueGetID('Service', 'ServiceCode', $detail['ServiceCode'], $this->param->BranchID, $this->param->MainID);
            if($id !== null ){
                // update item category
                $data = [
                    "ServiceLevelCommission" => $this->coalesce(@$detail['ServiceLevelCommission'], 0),
                    "ServiceName" => @$detail['ServiceName'], 
                    "ServiceCode" => @$detail['ServiceCode'], 
                    "Price" => @$detail['Price'], 
                    "CommissionValue" => @$detail['CommissionValue'],
                    "CommissionPercent" => @$detail['CommissionPercent'],
                    "DurationUnitID" => $temp[$detail['DurationUnitName']],
                    "ServiceDuration" => @$detail['ServiceDuration']
                ];
            } else {
                // create new item category
                $data = [
                    "ServiceLevelCommission" => $this->coalesce(@$detail['ServiceLevelCommission'], 0),
                    "ServiceName" => @$detail['ServiceName'], 
                    "ServiceCode" => @$detail['ServiceCode'], 
                    "Price" => @$detail['Price'], 
                    "CommissionValue" => @$detail['CommissionValue'],
                    "CommissionPercent" => @$detail['CommissionPercent'],
                    "DurationUnitID" => @$detail['DurationUnitID'],
                    "ServiceDuration" => @$detail['ServiceDuration'],
                    "BrandID" => $this->param->MainID, 
                    "BranchID" => $this->param->BranchID
                ];
            }
            $insertedData = $this->meta->upsert('Service', $data, $this->param, $id);
            if(isset($insertedData['error'])){ 
                if($this->environment != 'live') $errorMsg = $insertedData['message'];
                else $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Database Error");
            }
        }
        $this->db_trans_end();
        return response()->json($response);
    }
    

    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['ServiceID'];
        $find = $this->meta->find('Service', $id);
        if(count($find) <= 0){
            $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Data not found");
            return response()->json($response);
        }

        $response = $this->generateResponse(0, [], "Success", ['Service'=>@$find[0]]);
        return response()->json($response);
    }
    public function delete()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['ServiceID'];
        $delete = $this->meta->delete('Service', $id);
        $now = $this->get_date_now();
        $data = array(
            'Archived' => $now
        );
        DB::table('SubService')->where('ServiceID', $id)->update($data);
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
