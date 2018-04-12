<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use Service\Interfaces\Space;

class SpaceController extends BaseController
{
    public function __construct(Meta $meta, Request $request, Space $space){
        $this->meta = $meta;
        $this->space = $space;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }

    public function datatables()
    {
        $input = json_decode($this->request->getContent(),true);
        $draw = $this->coalesce(@$input['Draw'],1);
        $orderBy = $this->coalesce(@$input['OrderBy'], 'SpaceName');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = $this->coalesce(@$input['PageSize'], 10);
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $sortableColumn = ["SpaceName","SpaceSectionName", "Order"];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $total = $this->meta->totalRecords('Space', $this->param);
        $data = $this->space->getDataTable(['Space.SpaceName', 'Space.Order', 'Space.Description', 'SpaceSection.SpaceSectionName', 'Space.SpaceID'], ['SpaceName', 'SpaceSectionName'], $perPage, $start, $orderBy, $sort, $keyword,$this->param);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['SpaceID'];
        $rules = [
                    'SpaceName' => 'required',
                    'Order' => 'required|numeric',
                    'SpaceSectionID' => 'required'
                ];
        if(isset($input['SpaceName']) && !empty($this->meta->checkUnique('Space', 'SpaceName', $input['SpaceName'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['SpaceName'] = 'required|unique:Space';
        }
        if(isset($input['SpaceSectionID']) && empty($this->meta->checkUnique('SpaceSection', 'SpaceSectionID', $input['SpaceSectionID'], $this->param->BranchID, $this->param->MainID)) ){
            $additional[] = ["ID" => "SpaceSection", "Message" => "SpaceSection ".$input['SpaceSectionID']." is not Invalid"];
            $response = $this->generateResponse(1, $additional, "Please check input");
            return response()->json($response);
        }
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            if($errors->first('SpaceName')){
                if(strpos($errors->first('SpaceName'),'taken') !== false){
                    $additional = ["Space" => ''];
                }
            }
            
            
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }

        // validate input
        
        if($id !== null ){
            $find = $this->meta->find('Space', $id);
            if(count($find) <= 0){
                $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
            $data = ["SpaceName" => @$input['SpaceName'], "Description" => @$input['Description'], "SpaceSectionID" => @$input['SpaceSectionID'], "Order" => @$input['Order']];
        } else {
            $data = ["SpaceName" => @$input['SpaceName'], "Description" => @$input['Description'], "SpaceSectionID" => @$input['SpaceSectionID'], "Order" => @$input['Order'], "BrandID" => $this->param->MainID, "BranchID" => $this->param->BranchID];
        }
        
        $insertedData = $this->meta->upsert('Space', $data, $this->param, $id);
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['Space'=>$insertedData]);
        }
        return response()->json($response);
    }

    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['SpaceID'];
        $find = $this->meta->find('Space', $id);
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
        $id = $input['SpaceID'];
        $delete = $this->meta->delete('Space', $id);
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
