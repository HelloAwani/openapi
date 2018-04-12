<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;

class SpaceSectionController extends BaseController
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
        $orderBy = $this->coalesce(@$input['OrderBy'], 'SpaceSectionName');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = $this->coalesce(@$input['PageSize'], 10);
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $sortableColumn = ["SpaceSectionName","Description", "Order"];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $total = $this->meta->totalRecords('SpaceSection', $this->param);
        $data = $this->meta->getDataTable('SpaceSection', ["SpaceSectionID", "SpaceSectionName","Description", "Order"], ['SpaceSectionName', 'Description', 'Order'], $perPage, $start, $orderBy, $sort, $keyword,$this->param);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['SpaceSectionID'];
        $rules = [
                    'SpaceSectionName' => 'required',
                    'Order' => 'required|numeric'
                ];
        if(isset($input['SpaceSectionName']) && !empty($this->meta->checkUnique('SpaceSection', 'SpaceSectionName', $input['SpaceSectionName'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['SpaceSectionName'] = 'required|unique:SpaceSection';
        }
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            if($errors->first('SpaceSectionName')){
                if(strpos($errors->first('SpaceSectionName'),'taken') !== false){
                    $additional = ["SpaceSection" => ''];
                }
            }
            
            
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }

        // validate input
        
        if($id !== null ){
            $find = $this->meta->find('SpaceSection', $id);
            if(count($find) <= 0){
                $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
            $data = ["SpaceSectionName" => @$input['SpaceSectionName'], "Description" => @$input['Description'], "Order" => @$input['Order']];
        } else {
            $data = ["SpaceSectionName" => @$input['SpaceSectionName'], "Description" => @$input['Description'], "Order" => @$input['Order'], "BrandID" => $this->param->MainID, "BranchID" => $this->param->BranchID];
        }
        
        $insertedData = $this->meta->upsert('SpaceSection', $data, $this->param, $id);
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['SpaceSection'=>$insertedData]);
        }
        return response()->json($response);
    }

    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['SpaceSectionID'];
        $find = $this->meta->find('SpaceSection', $id);
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
        $id = $input['SpaceSectionID'];
        $delete = $this->meta->delete('SpaceSection', $id);
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
