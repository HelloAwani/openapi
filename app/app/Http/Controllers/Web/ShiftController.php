<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Service\Interfaces\Shift;
use Service\Interfaces\Meta;
use Validator;

class ShiftController extends BaseController
{

    public function __construct(Meta $meta, Shift $shift, Request $request){
        $this->shift = $shift;
        $this->meta = $meta;
        $this->environment = \App::environment();
        $this->param = $this->checkToken($request);
        $this->request = $request;
    }

    // GET api/web/shift/datatables
    // get data for datatables
    public function datatables(Request $request)
    {
        $input = json_decode($request->getContent(),true);
        $draw = $this->coalesce(@$input['Draw'],1);
        $orderBy = $this->coalesce(@$input['OrderBy'], 'CategoryCode');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = $this->coalesce(@$input['PageSize'], 10);
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $sortableColumn = ["ShiftName","From","To"];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $total = $this->shift->totalRecords($this->param);
        $data = $this->shift->get($perPage, $start, $orderBy, $sort, $keyword, $this->param);
        $additional = ['draw'=>++$draw, 'ValidSortColumn'=>$sortableColumn, 'recordsFiltered' => $data['recordsFiltered'], 'recordsTotal' => $total, 'DataCount' => $total, 'MaxPage' => $data['maxPage'], 'data'=>$data['data']];
        $response = $this->generateResponse(0, [], "Success", $additional);
        return response()->json($response);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * POST api/web/shift
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        $input = json_decode($request->getContent(),true);
        $shiftCode = @$input['ShiftCode'];
        $shiftName = @$input['ShiftName'];
        $id = @$input['ShiftID'];
        $from = @$input['From'];
        $to = @$input['To'];

        $rules = [
                    'ShiftCode' => 'required',
                    'ShiftName' => 'required',
                    'From' => 'required|date_format:H:i',
                    'To' => 'required|date_format:H:i|after:From'
                ];
        if(@$input['ShiftCode'] !== null && !empty($this->meta->checkUnique('Shift', 'ShiftCode', @$input['ShiftCode'], $this->param->BranchID, $this->param->MainID, @$id)) ){
            $rules['ShiftCode'] = 'required|unique:Shift';
        }
        // validate input
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $response = $this->generateResponse(1, $errorList, "Please check input");
            return response()->json($response);
        }
        if($id !== null ){
            $find = $this->shift->find($id);
            if(isset($find['error'])){
                if($this->environment != 'live') $errorMsg = $find['message'];
                else $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
            $data = ["ShiftCode" => $shiftCode, "ShiftName" => $shiftName, "From" => $from, "To" => $to];
        } else {
            $data = ["ShiftCode" => $shiftCode, "ShiftName" => $shiftName, "From" => $from, "To" => $to, "BrandID" => $this->param->MainID, "BranchID" => $this->param->BranchID];
        }
        
        
        
        $insertedData = $this->shift->upsert($data, @$id);
        if(isset($insertedData['error'])){ 
            if($this->environment != 'live') $errorMsg = $insertedData['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Database Error");
        }else{
            $response = $this->generateResponse(0, [], "Success", ['Shift'=>$insertedData]);
        }
        return response()->json($response);
    }

    /**
     * Display the specified resource.
     * GET api/web/shift/{id}
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function detail()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['ShiftID'];
        $find = $this->shift->find($id);
        if(isset($find['error'])){
            if($this->environment != 'live') $errorMsg = $find['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Data not found");
            return response()->json($response);
        }

        $response = $this->generateResponse(0, [], "Success", ['Shift'=>$find]);
        return response()->json($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * PUT api/web/shift/{id}
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
//    public function update(Request $request, $id)
//    {
//        $find = $this->shift->find($id);
//        if(isset($find['error'])){
//            if($this->environment != 'live') $errorMsg = $find['message'];
//            else $errorMsg = "Database Error"; 
//            $response = $this->generateResponse(1, $errorMsg, "Data not found");
//            return response()->json($response);
//        } 
//
//        $input = json_decode($request->getContent(),true);
//        $shiftName = @$input['ShiftName'];
//        $from = @$input['From'];
//        $to = @$input['To'];
//
//        $rules = [
//                    'From' => 'required_with:To|hiformat',
//                    'To' => 'required_with:From|hiformat|after:From'
//                ];
//        $messages = [
//            'hiformat' => 'The :attribute format must be mm:ss (minutes:seconds).',
//        ];
//        Validator::extend('hiformat', function($attribute, $value, $parameters, $validator) {
//            return preg_match("/(1[012]|0[0-9]):([0-5][0-9])/", $value);
//        });
//        // validate input
//        $validator = Validator::make($input, $rules, $messages);
//        if ($validator->fails()) {
//            // validation error
//            $errors = $validator->errors();
//            $errorList = $this->checkErrors($rules, $errors);
//            $response = $this->generateResponse(1, $errorList, "Please check input");
//            return response()->json($response);
//        }
//        $data = [];
//        if(!empty($shiftName)) $data['ShiftName'] = $shiftName;
//        if(!empty($from)) $data['From'] = $from;
//        if(!empty($to)) $data['To'] = $to;
//
//        if(count($data) == 0){
//            $response = $this->generateResponse(1, [], "Nothing updated, Minimum one field must be filled");
//            return response()->json($response);
//        }
//
//        $updatedData = $this->shift->upsert($data, $id);
//        if(isset($updatedData['error'])){ 
//            if($this->environment != 'live') $errorMsg = $updatedData['message'];
//            else $errorMsg = "Database Error"; 
//            $response = $this->generateResponse(1, $errorMsg, "Database Error");
//        }else{
//            $response = $this->generateResponse(0, [], "Success", ['Item'=>$updatedData]);
//        }
//        return response()->json($response);
//    }

    /**
     * Remove the specified resource from storage.
     * DELETE api/web/shift/{id}
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['ShiftID'];
        $delete = $this->shift->delete($id);
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
