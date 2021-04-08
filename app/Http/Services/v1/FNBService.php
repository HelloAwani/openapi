<?php

namespace Service\Http\Services\v1;

use DB;

class FNBService
{
   public function __construct()
   {
   }

   /**
    * get brand branch data
    *
    * @param integer $brandId
    * @param integer $limit
    * @return mixed
    */
   public function getBranch($brandId, $limit = 0)
   {
      $query = DB::connection('res')
         ->table('Branch')
         ->select(['BranchID', 'BranchName', 'Address'])
         ->where('RestaurantID', '=', $brandId)
         ->whereNull('Archived');

      if ($limit > 0) {
         $query->limit($limit);
      }

      $dbResult = $query->get();

      if($dbResult->isNotEmpty()){
         return $dbResult;
      }
      else return [];
   }

   /**
    * get brand branch id
    *
    * @param integer $brandId
    * @param integer $limit
    * @return mixed
    */
   public function getBranchId($branchData)
   {
      if ($branchData->isNotEmpty()) {
         foreach ($branchData as $value) {
            $result[] = $value->BranchID;
         }
      }

      return $result ?? [];
   }

   /**
    * map branch data to [BranchID:{BranchData}]
    *
    * @param array $data branch data from query result
    * @return array
    */
   public function mapData($data){
      if(empty($data)) return [];

      $result = [];
      foreach ($data as $value) {
         $temp = (object)[];
         $temp->BranchName = $value->BranchName;
         $temp->Address = $value->Address;

         $result[$value->BranchID] = $temp;
      }
      
      return $result;
   }
}
