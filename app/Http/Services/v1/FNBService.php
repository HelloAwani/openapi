<?php

namespace Service\Http\Services\v1;

use DB;

class FNBService
{
   public function __construct()
   {
   }

   /**
    * get brand branch
    *
    * @param integer $brandId
    * @param integer $limit
    * @return mixed
    */
   public function getBranchId($brandId, $limit = 0)
   {
      $query = DB::connection('res')
         ->table('Branch')
         ->select('BranchID')
         ->where('RestaurantID', '=', $brandId);

      if ($limit > 0) {
         $query->limit($limit);
      }

      $dbResult = $query->get();

      $result = [];
      if ($dbResult->isNotEmpty()) {
         foreach ($dbResult as $value) {
            $result[] = $value->BranchID;
         }
      }

      return $result;
   }
}
