<?php

namespace Service\Http\Controllers\Web;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Service\Interfaces\Staff;
use Service\Interfaces\Meta;

class StaffController extends BaseController
{

    public function __construct(Staff $staff,Meta $meta, Request $request){
        $this->staff = $staff;
        $this->param = $this->checkToken($request);
        $this->request = $request;
        $this->meta = $meta;
    }

    // GET api/web/users/datatables
    // get data for datatables
    public function datatables()
    {
        $input = json_decode($this->request->getContent(),true);
        $draw = $this->coalesce(@$input['Draw'],1);
        $orderBy = $this->coalesce(@$input['OrderBy'], 'CategoryCode');
        $sort = $this->coalesce(@$input['OrderDirection'], 'asc');
        $perPage = $this->coalesce(@$input['PageSize'], 10);
        $start = $this->coalesce(@$input['StartFrom'], 0);
        $keyword = @$input['StringQuery'];
        $active = @$input['ActiveStatus'];
        $sortableColumn = ["ShiftName","UserTypeName","UserCode","Fullname"];
        if(!in_array($orderBy,$sortableColumn)) $orderBy = null;

        $total = $this->staff->totalRecords($this->param);
        $data = $this->staff->get($perPage, $start, $orderBy, $sort, $keyword, $this->param);
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
     * POST api/web/users
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save()
    {
        $input = json_decode($this->request->getContent(),true);
        $branchID = $this->param->BranchID;
        $brandID = $this->param->MainID;
        $id = @$input['UserID'];
        $rules = [
                    'Fullname' => 'required',
                    'Email' => 'email',
//                    'Username' => 'required|min:6',
                    'UserCode' => 'required',
                    'ShiftID' => 'required|numeric|exists:Shift,ShiftID',
                    'UserTypeID' => 'required|numeric|exists:UserType,UserTypeID',
                    'Description' => 'max:1024',
                    'ActiveStatus' => 'required|activeFilter',
                    'JoinDate' => 'required|date_format:Y-m-d',
                    'PIN' => 'numeric|stringMax'
                ];
        $messages = [
            'active_filter' => 'The :attribute choices must be A or D',
            'string_max' => 'The :attribute size maximum 6 characters'
        ];
        if($id !== null){
            $find = $this->staff->find($id);
            if(isset($find['error'])){
                if($this->environment != 'live') $errorMsg = $find['message'];
                else $errorMsg = "Database Error"; 
                $response = $this->generateResponse(1, $errorMsg, "Data not found");
                return response()->json($response);
            }
            if(@$input['Password'] != null){   
                $rules['Password'] = 'min:8|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/';
                $messages['regex'] = 'The :attribute must contain uppercase, lowercase, and numeric';
            }
        } else {
            $rules['Password'] = 'min:8|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/';
            $messages['regex'] = 'The :attribute must contain uppercase, lowercase, and numeric';
        }
        
        
        if(@$input['UserCode'] != null && !empty($this->meta->checkUnique('User', 'UserCode', @$input['UserCode'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['UserCode'] = 'required|unique:User';
        }
        if(@$input['PIN'] != null && !empty($this->meta->checkUnique('User', 'PIN', @$input['PIN'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['PIN'] = 'required|numeric|stringMax|unique:User';
        }
        if(@$input['Email'] != null && !empty($this->meta->checkUnique('User', 'Email', @$input['Email'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['Email'] = 'required|unique:User';
        }
        
        if(@$input['Username'] != null&& !empty($this->meta->checkUnique('User', 'Username', @$input['Username'], $this->param->BranchID, $this->param->MainID, $id)) ){
            $rules['Username'] = 'required|min:6|unique:User';
        }

        // validate input
        Validator::extend('activeFilter', function($attribute, $value, $parameters, $validator) {
            if(strtoupper($value) == 'A' || strtoupper($value) == 'D') return true;
            else return false;
        });
        Validator::extend('stringMax', function($attribute, $value, $parameters, $validator) {
            if(strlen($value) == 6) return true;
            else return false;
        });
        $validator = Validator::make($input, $rules, $messages);
        if ($validator->fails()) {
            // validation error
            $errors = $validator->errors();
            $errorList = $this->checkErrors($rules, $errors);
            $additional = null;
            if($errors->first('UserCode')){
                if(strpos($errors->first('UserCode'),'taken') !== false){
                    $additional = ["User" => $this->staff->where('UserCode',$input['UserCode'], 'first')];
                }
            }else if($errors->first('PIN')){
                if(strpos($errors->first('PIN'),'taken') !== false){
                    $additional = ["User" => $this->staff->where('PIN',$input['UserCode'], 'first')];
                }
            }else if($errors->first('Email')){
                if(strpos($errors->first('Email'),'taken') !== false){
                    $additional = ["User" => $this->staff->where('Email',$input['UserCode'], 'first')];
                }
            }else if($errors->first('Username')){
                if(strpos($errors->first('Username'),'taken') !== false){
                    $additional = ["User" => $this->staff->where('PIN',$input['UserCode'], 'first')];
                }
            }
            $response = $this->generateResponse(1, $errorList, "Please check input", $additional);
            return response()->json($response);
        }
        $data = ["Fullname" => @$input['Fullname'], 
                 "Email" => @$input['Email'],
                 "Username" => @$input['Username'],
                 "UserCode" => @$input['UserCode'],
                 "ShiftID" => @$input['ShiftID'],
                 "UserTypeID" => @$input['UserTypeID'],
                 "Description" => @$input['Description'],
                 "ActiveStatus" => @$input['ActiveStatus'],
                 "JoinDate" => @$input['JoinDate'],
                 "PIN" => @$input['PIN'],
                 "StatusOnline" => "Offline"];
        if($id === null){
            $data["BrandID"] = $brandID;
            $data["BranchID"] = $branchID;
        }
        if(@$input['Password'] !== null){
            $data["Password"] = password_hash(@$input['Password'], PASSWORD_BCRYPT);
        }
        $insertedData = $this->staff->upsert($data,$id);
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
        $id = $input['UserID'];
        $find = $this->staff->find($id);
        if(isset($find['error'])){
            if($this->environment != 'live') $errorMsg = $find['message'];
            else $errorMsg = "Database Error"; 
            $response = $this->generateResponse(1, $errorMsg, "Data not found");
            return response()->json($response);
        }

        $response = $this->generateResponse(0, [], "Success", ['Data'=>$find]);
        return response()->json($response);
    }
    
    public function delete()
    {
        $input = json_decode($this->request->getContent(),true);
        $id = $input['UserID'];
        $delete = $this->staff->delete($id);
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
