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
      $this->dimensionStructure = [
         // "BranchID" => 1,
         // "BranchName" => 1,
         // "Alamat" => 1,
         // "Data" => [
         "VAT" => 2,
         "Bill" => 2,
         "Changes" => 2,
         "Discount" => 2,
         "Rounding" => 2,
         "TotalBill" => 2,
         "TotalItem" => 2,
         "ServiceTax" => 2,
         "GuestNumber" => 2,
         "TotalBilling" => 2,
         "TotalPayment" => 2,
         "PaymentDetail" => [
            "PaymentMethodID" => 3,
            "Payment" => 2,
            "PaymentCount" => 2,
            "StartToFinish" => 2
         ],
         "TotalModifier" => 2,
         "TotalItemSales" => 2,
         "ItemSalesDetail" => [
            "MenuID" => 3,
            "Qty" => 2,
            "AvgPrice" => 'countAvgPrice',
            "Discount" => 2,
            "SubTotal" => 2,
            "CategoryID" => 1,
            "StartToFinish" => 2,
            "ModifierDetail" => [
               "ModifierID" => 1,
               "ModifierTotal" => 2
            ]
         ],
         "TotalDiscountItem" => 2,
         "TOtalModifierSales" => 2
         // ]
      ];

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
               $currDimData = (array) $currDimension->data;
               
               // $test = $this->mergeDimension2((array) $currValue->Data, $currDimData);
               // $this->mergeDimension3((array) $currValue->Data, $currDimData);
               
               $newData = $this->mergeDimension($oldData, $currDimData, ['PaymentDetail', 'ItemSalesDetail']);
               
               $newData['PaymentDetail'] = $this->mergeDimensionWithId($oldData['PaymentDetail'], $currDimData['PaymentDetail'], 'PaymentMethodID');
               $newData['ItemSalesDetail'] = $this->mergeDimensionWithId($oldData['ItemSalesDetail'], $currDimData['ItemSalesDetail'], 'MenuID', ['CategoryID', 'ModifierDetail', 'AvgPrice']);

               // $newData['ItemSalesDetail']['ModifierDetail'] = $this->mergeDimensionWithId($oldData['ItemSalesDetail']['ModifierDetail'], $currDimData['ItemSalesDetail']['ModifierDetail'], 'ModifierName');
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
            if (empty($branchData)) {
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

         // search for existing data
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

            // if identifer = MenuID do merge for the subdata
            if($identifier == 'MenuID'){
               foreach ($new->ModifierDetail as $modKey => $modValue) {
                  $newModifier = true;
                  foreach ($result[$oldIndex]->ModifierDetail as $key => $value) {
                     if($value->ModifierName == $modValue->ModifierName){
                        $newModifier = false;

                        break;
                     }
                  }

                  // insert new modifier
                  if($newModifier){
                     $result[$oldIndex]->ModifierDetail[] = $modValue;
                  }
                  else{
                     // update modifier
                     $temp1 = $result[$oldIndex]->ModifierDetail[$key];
                     $temp1->TotalSales = strval($temp1->TotalSales + $modValue->TotalSales);
                     $temp1->QtyModifier = strval($temp1->QtyModifier + $modValue->QtyModifier);
                     $result[$oldIndex]->ModifierDetail[$key] = $temp1;
                  }
               }
            }
               # code...
            // $result[$oldIndex] = $temp;
         }
      }
      return $result;
   }
}
