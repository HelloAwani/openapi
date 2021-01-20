<?php

namespace Service\Http\Services\v1;

use Carbon\Carbon;
use DB;
use Service\Http\Services\v1\FNBService;

class DimensionService
{
   public function __construct()
   {
      // $this->use_db_trans = false;
      // parent::__construct();
   }

   /**
    * Get brand dimension within date range
    *
    * @param integer $brandId
    * @param array $date ['start', 'end']
    * @return void
    */
   public function getDimension($brandId, $date)
   {
      $fnbSvc = new FNBService();
      // get brand branch
      $branchId = $fnbSvc->getBranchId($brandId);

      if (empty($branchId)) {
         return false;
      }

      // get all branch dimension within date range
      $dimension = $this->getBranchDimension($branchId, 'fnb_sales', $date);
      if (empty($dimension)) {
         return false;
      }

      // parse dimension data
      $dimension = $this->parseDimensionData($dimension);
      
      return $dimension;
   }

   /**
    * get branch dimension within date range
    *
    * @param array $branchId
    * @param array $dateRange[start, end]
    * @param string $dimensionName
    * @return mixed
    */
   public function getBranchDimension($branchId, $dimensionName, $dateRange)
   {
      // get startOfDay of 'start' and endOfDay of 'end'
      $dateRange['start'] = Carbon::parse($dateRange['start'])->startOfDay()->toDateTimeString();
      $dateRange['end'] = Carbon::parse($dateRange['end'])->endOfDay()->toDateTimeString();

      // run query
      $result = DB::connection('rep')
         ->table('dimension')
         ->where('product_id', '=', $dimensionName)
         ->where('dimension_name', '=', $dimensionName)
         ->whereIn('branch_id', $branchId)
         ->whereBetween('timeframe', [$dateRange['start'], $dateRange['end']])
         ->get();

      if ($result->isNotEmpty()) {
         return $result;
      } else return false;
   }

   /**
    * parse dimension data
    *
    * @param array $dimension array of dimension
    * @return array
    */
   public function parseDimensionData($dimension)
   {
      $result = [];
      foreach ($dimension as $value) {
         // extract date only from timeframe
         $currTimeframe = Carbon::parse($value->timeframe)->toDateString();

         $isNew = true;
         // search if the dimension timeframe already exist
         foreach ($result as $currValue) {
            if ($currTimeframe == $currValue->timeframe) {
               // dimension at the timeframe exist, update the dimension
               $isNew = false;

               // update dimension
            }
            $a = 1;
         }

         // insert new one if not found
         if ($isNew) {
            $a = 1;
         }
      }
   }
}
