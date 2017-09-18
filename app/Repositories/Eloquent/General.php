<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\General as GeneralInterface;
use DB;

class General implements GeneralInterface {

    public function std_to_json(&$array, $array_column){
		foreach ($array_column as $d) {
			foreach ($array as &$dd) {
				$dd->{$d} = json_decode($dd->{$d});
			}
		}
	}
    
	public function get($param){
		try{
            $gt = DB::select('SELECT DISTINCT "Category" from 
				(SELECT "Order", "Category" from "GeneralSettingType" order by "Order") a');
			foreach ($gt as $d) {
				unset($d->Order);
				$d->Settings = DB::select('
				SELECT 
				"gt"."GeneralSettingTypeID" , 
				"gt"."GeneralSettingTypeName" as "GeneralSettingTypeName", 
				"gt"."AcceptedDataType" as "DataType",
				COALESCE(COALESCE("GeneralSetting"."GeneralSettingValue", gt."DefaultValue"),\'\') as 
				"GeneralSettingValue", "Options"
				FROM 
				"GeneralSetting"
				RIGHT JOIN 
				"GeneralSettingType" gt on "GeneralSetting"."GeneralSettingTypeID" = gt."GeneralSettingTypeID"
				AND  
				"GeneralSetting"."BranchID" = \''.$param->BranchID.'\'
				WHERE gt."Category" = \''.$d->Category.'\'
				ORDER BY "gt"."Order"');
                $this->std_to_json($d->Settings, array("Options"));
			}
            $result = $gt;
			return $result;
		}catch(\Exception $e){
			return $e;
		}
	}
    

    
	public function upsert($data, $param, $id = null){
		try{
            if(!empty($id)){
                // update data
                try{
                    DB::table('GeneralSetting')->where('BranchID',$param->BranchID)->where('BrandID',$param->MainID)->where('GeneralSettingID', $id)->update($data);
                    return '';
                }catch(\Exception $e){
                    return ['error'=>true, 'message'=>$e->getMessage()];
                }
            }else{
                // insert data
                try{
                    return DB::table('GeneralSetting')->insert($data);
                }catch(\Exception $e){
                    return ['error'=>true, 'message'=>$e->getMessage()];
                }
            }
		}catch(\Exception $e){
			return 0;
		}
	}
    
    
    public function find($param, $id){
		try{
			return DB::table('GeneralSetting')->where('BranchID',$param->BranchID)->where('BrandID',$param->MainID)->where('GeneralSettingTypeID', $id)->get();
		}catch(\Exception $e){
			return ['error'=>true, 'message'=>$e->getMessage()];
		}
    }
    

}