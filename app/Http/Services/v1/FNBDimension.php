<?php

namespace Service\Http\Services\v1;

use Carbon\Carbon;
use DB;

/**
 * Base FnB Dimension
 */
class FNBDimension
{
   public function __construct()
   {
   }

   /**
    * get BRANCH dimension within date range
    *
    * @param array $branchId array of branch ids
    * @param array $dimensionName array of dimension name
    * @param array $dateRange [start, end]
    * @return mixed
    */
   public function getBranchDimension($branchId, $dimensionName, $dateRange)
   {
      // get startOfDay of 'start' and endOfDay of 'end'
      $dateRange['start'] = Carbon::parse($dateRange['start'])->startOfDay()->toDateTimeString();
      $dateRange['end'] = Carbon::parse($dateRange['end'])->endOfDay()->toDateTimeString();

      // run query
      $query = DB::connection('rep')
         ->table('dimension')
         ->whereIn('branch_id', $branchId)
         ->whereIn('dimension_name', '=', $dimensionName)
         ->whereBetween('timeframe', [$dateRange['start'], $dateRange['end']]);

      $result = $query->get();

      if ($result->isNotEmpty()) {
         $this->rawDimension = $result;
         return $result;
      } else return false;
   }

   /**
    * get BRAND dimension within date range
    *
    * @param array $brandId main_id
    * @param array $dimensionName array of dimension name
    * @param array $dateRange[start, end]
    * @return mixed
    */
   public function getBrandDimension($brandId, $dimensionName, $dateRange)
   {
      // get startOfDay of 'start' and endOfDay of 'end'
      $dateRange['start'] = Carbon::parse($dateRange['start'])->startOfDay()->toDateTimeString();
      $dateRange['end'] = Carbon::parse($dateRange['end'])->endOfDay()->toDateTimeString();

      // run query
      $result = DB::connection('rep')
         ->table('dimension')
         ->whereIn('dimension_name', $dimensionName)
         ->where('main_id', '=', $brandId)
         ->whereBetween('timeframe', [$dateRange['start'], $dateRange['end']])
         ->get();

      if ($result->isNotEmpty()) {
         return $result;
      } else return false;
   }

   
}
