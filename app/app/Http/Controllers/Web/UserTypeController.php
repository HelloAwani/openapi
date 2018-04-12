<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Meta;
use DB;

class UserTypeController extends BaseController
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
        $orderBy = $this->coalesce(@$input['OrderBy'], 'UserTypeCode');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = $this->coalesce(@$input['PageSize'], 10);
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $sortableColumn = ["UserTypeCode","UserTypeName"];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $total = $this->meta->totalRecords('UserType', $this->param);
        $data = $this->meta->getDataTable('UserType', ['UserTypeID', 'UserTypeCode', 'UserTypeName'], ['UserTypeCode', 'UserTypeName'], $perPage, $start, $orderBy, $sort, $keyword,$this->param);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['UserTypeID'];
        $rules = [
                    'UserTypeCode' => 'required',
                    'UserTypeName' => 'required'
                ];
        if(isset($input['UserTypeCode']) && !empty($this->meta->checkUnique('UserType', 'UserTypeCode', $input['UserTypeCode'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['UserTypeCode'] = 'required|unique:UserType';
        }
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            if($errors->first('UserTypeCode')){
                if(strpos($errors->first('UserTypeCode'),'taken') !== false){
                    $additional = ["UserType" => ''];
                }
            }
            
            
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }

        // validate input
        $this->db_trans_start();
        if($id !== null ){
            $find = $this->meta->find('UserType', $id);
            if(count($find) <= 0){
                $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
            $data = ["UserTypeCode" => @$input['UserTypeCode'], "UserTypeName" => @$input['UserTypeName']];
        } else {
            $data = ["UserTypeCode" => @$input['UserTypeCode'], "UserTypeName" => @$input['UserTypeName'], "BrandID" => $this->param->MainID, "BranchID" => $this->param->BranchID];
        }
        
        $insertedData = $this->meta->upsert('UserType', $data, $this->param, $id);
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['Data'=>$insertedData]);
        }
        if($id === null ){
            $id = $this->getLastVal();
        }
        DB::table('UserTypePermission')->where('UserTypeID', $id)->delete();
        for($i = 0; $i < count(@$input['Permission']); $i++){
            $permission = @$input['Permission'][$i];
            $data = array(
                'PermissionID'=> $permission,
                'UserTypeID' => $id
            );
            $insertedData = DB::table('UserTypePermission')->insert($data);
        }
        $this->db_trans_end();
        return response()->json($response);
    }
    

    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['UserTypeID'];
        $find = $this->meta->find('UserType', $id, 'first');
        if(count($find) <= 0){
            $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Data not found");
            return response()->json($response);
        }
        $select = array(
            'p.PermissionID',
            'PermissionName', 
            'Group',
            'Order',
            DB::raw('CASE WHEN utp."PermissionID" IS NOT NULL THEN \'1\' ELSE \'0\' END AS "Checked"')
        );
        $result = DB::table('Permission AS p')->select($select)
            ->leftJoin('UserTypePermission AS utp', function($join) use($id){
                $join->on('utp.PermissionID', '=', 'p.PermissionID');
                $join->on('UserTypeID', '=', DB::raw($id));
         })->get();
        $i = 0;
        $ordered = array();
        foreach($result as $res){
            if(@$ordered[$i-1]['Group'] != $res->Group){
                $ordered[$i]['Group'] = $res->Group;
                $i += 1;
            }
            $ordered[$i-1]['Permission'][] = array(
                'PermissionID' => $res->PermissionID,
                'PermissionName' => $res->PermissionName,
                'Checked' => $res->Checked,
                'Order' => $res->Order
            );
            
        }
        
        $find->Permission = $ordered;
        $response = $this->generateResponse(0, [], "Success", ['Data'=>@$find]);
        return response()->json($response);
    }
    public function delete()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['UserTypeID'];
        $delete = $this->meta->delete('UserType', $id);
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
