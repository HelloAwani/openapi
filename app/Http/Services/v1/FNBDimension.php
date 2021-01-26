<?php

namespace Service\Http\Services\v1;

use Carbon\Carbon;
use DB;
use Log;
use Service\Http\Services\v1\FNBService;

class FNBDimension extends HBDimension
{
   public function __construct()
   {
      parent::__construct();
   }

   /**
    * Get brand dimension within date range
    *
    * @param object $tokenData 
    * @param array $date ['start', 'end']
    * @return array
    */
   public function getSalesDimension($tokenData, $date)
   {
      $fnbSvc = new FNBService();
      $brandId = $tokenData->MainID;

      // get brand branch
      $branch = $fnbSvc->getBranch($brandId);
      // $branchId = $fnbSvc->getBranchId($branch);

      if (empty($branch)) {
         return false;
      }

      // get all brand dimension within date range
      $this->getBrandDimension($brandId, ['fnb_sales'], $date);
      if (empty($this->rawDimension)) {
         return false;
      }

      // parse dimension data
      $this->parseSalesDimensionData($branch);

      return $this->dimension;
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
    * @param array $dateRange[start, end]
    * @param string $dimensionName
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
         ->where('dimension_name', '=', $dimensionName)
         ->where('main_id', '=', $brandId)
         ->whereBetween('timeframe', [$dateRange['start'], $dateRange['end']])
         ->get();

      if ($result->isNotEmpty()) {
         $this->rawDimension = $result;
         return $result;
      } else return false;
   }

   /**
    * parse dimension data
    *
    * @param array $data array of dimension
    * @param array $filter array of branch id to be shown from the dimension, if not given, process all dimension
    * @return array
    */
   public function parseSalesDimensionData($branch)
   {
      foreach ($this->rawDimension as $currDimension) {
         // check filter
         // if(!empty($filter)){
         //    // skip current data if branch isn't in filter
         //    if(!in_array($value->branch_id, $filter)) continue;
         // }

         // extract date only from timeframe
         $currTimeframe = Carbon::parse($currDimension->timeframe)->toDateString();
         $currDimension->data = json_decode($currDimension->data);

         $isNew = true;

         // update dimension
         // search if the branch already exist in dimension
         foreach ($this->dimension as $index => $currValue) {
            if ($currValue->BranchID == $currDimension->branch_id) {
               // update dimension
               $isNew = false;

               // prepare data to be merged
               $oldData = (array) $this->dimension[$index]->Data;
               $tempCurrData = (array) $currDimension->data;

               $newData = $this->mergeDimension($oldData, $tempCurrData, ['PaymentDetail', 'ItemSalesDetail']);
               $newData['PaymentDetail'] = $this->mergeDimensionWithId($oldData['PaymentDetail'], $tempCurrData['PaymentDetail'], 'PaymentMethodID');
               $newData['ItemSalesDetail'] = $this->mergeDimensionWithId($oldData['ItemSalesDetail'], $tempCurrData['ItemSalesDetail'], 'MenuID', ['CategoryID', 'ModifierDetail', 'AvgPrice']);
               $this->dimension[$index]->Data = $newData;

               continue;
            }
         }

         // insert new one if not found
         if ($isNew) {
            // search branch data
            $branchData = [];
            foreach ($branch as $tempBranch) {
               if ($tempBranch->BranchID == $currDimension->branch_id) {
                  $branchData = $tempBranch;
                  $result = [
                     "BranchID" => $branchData->BranchID,
                     "BranchName" => $branchData->BranchName,
                     "Alamat" => $branchData->Address,
                     "Data" => $currDimension->data
                  ];
                  $this->dimension[] = json_decode(json_encode(($result)));
                  break;
               }
            }
            if(empty($branchData)){
               $fnbSvc = new FNBService();
               Log::debug($currDimension->branch_id . '|' . json_encode($fnbSvc->getBranchId($branch)));
            }
         }
      }
   }

   /**
    * merge dimension data that have id, example: ItemSalesDetail (for each MenuID)
    *
    * @param array $oldData
    * @param array $newData array of data to be added
    * @param string $identifier id identifier in data, ex: MenuID, PaymentMethodID
    * @param array $filter array of index that will be ignored from $newData, ex: avgPrice
    * @return array
    */
   protected function mergeDimensionWithId($oldData, $newData, $identifier, $filter = [])
   {
      $result = $oldData;
      foreach ($newData as $new) {
         $isNew = true;
         foreach ($oldData as $oldIndex => $old) {
            if ($old->$identifier == $new->$identifier) {
               $isNew = false;
               $currData = $oldData[$oldIndex];
               break;
            }
         }

         if ($isNew) {
            // insert new data
            $result[] = $new;
         } else {
            // update data
            $result[$oldIndex] = $currData;
            foreach ($new as $dataIndex => $dataValue) {
               // process data if it's not identifier and not in filter
               if ($dataIndex != $identifier && !in_array($dataIndex, $filter)) {
                  $result[$oldIndex]->$dataIndex = strval($result[$oldIndex]->$dataIndex + $dataValue);
               }
            }
            // $result[$oldIndex] = $temp;
         }
      }
      return $result;
   }
}
