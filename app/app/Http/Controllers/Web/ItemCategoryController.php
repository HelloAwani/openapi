<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\ItemCategory;
use Service\Interfaces\Meta;
use Upload;

class ItemCategoryController extends BaseController
{
    public function __construct(Meta $meta, ItemCategory $itemCategory, Request $request){
        $this->itemCategory = $itemCategory;
        $this->meta = $meta;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     * POST api/web/itemcategory/datatables
     * @return \Illuminate\Http\Response
     */
    public function datatables(Request $request)
    {
        $input = json_decode($request->getContent(),true);
        $draw = $this->coalesce(@$input['Draw'],1);
        $orderBy = $this->coalesce(@$input['OrderBy'], 'CategoryCode');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = @$input['PageSize'];
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $sortableColumn = ['CategoryName', 'CategoryCode'];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $total = $this->itemCategory->totalRecords($this->param);
        $data = $this->itemCategory->get($perPage, $start, $orderBy, $sort, $keyword, $this->param);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     * POST api/web/itemcategory
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = @$input['CategoryID'];
        $rules = [
                    'CategoryName' => 'required',
                    'CategoryCode' => 'required'
                ];
        // add unique:Category if search categoryCode = input category code where branchID = input branch id
        if(isset($input['CategoryCode']) && !empty($this->itemCategory->checkBranchCode($input['CategoryCode'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['CategoryCode'] = 'required|unique:Category';
        }

        
        
        // validate input
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            if($errors->first('CategoryCode')){
                if(strpos($errors->first('CategoryCode'),'taken') !== false){
                    $additional = ["Category" => $this->itemCategory->where('CategoryCode',$input['CategoryCode'], 'first')];
                }
            }
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }
        if($id !== null ){
            $find = $this->itemCategory->find($input['CategoryID']);
            if(isset($find['error'])){
                if($this->environment != 'live') $errorMsg = $find['message'];
                else $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
            // update item category
            $data = ["CategoryName" => @$input['CategoryName'], "CategoryCode" => @$input['CategoryCode']];
        } else {
            // create new item category
            $data = ["CategoryName" => @$input['CategoryName'], "CategoryCode" => @$input['CategoryCode'], "BrandID" => $this->param->MainID, "BranchID" => $this->param->BranchID];
        }
        if(@$input['DeleteImage'] === true){
            $data = ["Image" => null];
        }
        $insertedData = $this->itemCategory->upsert($data, $id);
        if(@$id === null){
            $id = $this->getLastVal();   
        }
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['Category'=>$insertedData]);
        }
        if(@$input['Images'] !== null){
            $arr = array(
                'BrandID' => $this->param->MainID,
                'BranchID' => $this->param->BranchID,
                'ObjectID' => $id,
                'Folder' => 'Category',
                'Filename' => @$input['Images']['Filename'], 
                'Data' =>  @$input['Images']['Data']
            );
            $path = $this->upload_to_s3($arr);
            $data = ["Image" => $path];
            $insertedData = $this->itemCategory->upsert($data, $id);
            $response = $this->generateResponse(0, [], "Success", ['Category'=>$insertedData]);
        }
        return response()->json($response);
    }
    
    
    public function import()
    {
        $input = json_decode($this->request->getContent(),true);
        $rules = [
            'Data' => 'required|array',
            'Data.*.CategoryName' => 'required',
            'Data.*.CategoryCode' => 'required|min:0|distinct'
        ];
        $niceNames = array(
            'Data.*.CategoryName' => 'Category Name',
            'Data.*.CategoryCode' => 'Category Code'
        );

        // validate input
        $validator = Validator::make($input, $rules);
        $validator->setAttributeNames($niceNames); 
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }
        $this->db_trans_start();
        for($i = 0; $i < count(@$input['Data']); $i++){
            $detail = @$input['Data'][$i];
            $id = @$this->meta->checkUniqueGetID('Category', 'CategoryCode', $detail['CategoryCode'], $this->param->BranchID, $this->param->MainID);
            if($id !== null ){
                // update item category
                $data = ["CategoryName" => @$detail['CategoryName'], "CategoryCode" => @$detail['CategoryCode']];
            } else {
                // create new item category
                $data = ["CategoryName" => @$detail['CategoryName'], "CategoryCode" => @$detail['CategoryCode'], "BrandID" => $this->param->MainID, "BranchID" => $this->param->BranchID];
            }
            $insertedData = $this->itemCategory->upsert($data, $id);
            if(isset($insertedData['error'])){ 
                if($this->environment != 'live') $errorMsg = $insertedData['message'];
                else $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Database Error");
            }
        }
        $this->db_trans_end();
        $response = $this->generateResponse(0, [], "Success", []);
        return response()->json($response);
    }
    
    

    /**
     * Display the specified resource.
     * GET api/web/itemcategory/{id}
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['CategoryID'];
        $find = $this->itemCategory->find($id);
        if(isset($find['error'])){
            if($this->environment != 'live') $errorMsg = $find['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Data not found");
            return response()->json($response);
        }

        $response = $this->generateResponse(0, [], "Success", ['Category'=>$find]);
        return response()->json($response);
    }

    /**
     * Update the specified resource in storage.
     * PUT api/web/itemcategory/{id}
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * Remove the specified resource from storage.
     * DELETE api/web/itemcategory/{id}
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['CategoryID'];
        $delete = $this->itemCategory->delete($id);
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
