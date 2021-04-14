<?php

namespace Service\Http\Services\v1;

use Carbon\Carbon;
use DB;
use Service\Http\Services\v1\FNBService;

class FNBSalesDimension extends FNBDimension
{
   protected $void = false;

   /**
    * @param boolean $void if true, get dimensin with void status
    */
   public function __construct($void = false)
   {
      parent::__construct();
      $this->void = $void;
   }

   /**
    * Get brand dimension within date range
    *
    * @param object $tokenData 
    * @param array $date ['start', 'end']
    * @return array
    */
   public function getDimension($tokenData, $date)
   {
      $fnbSvc = new FNBService();
      $brandId = $tokenData->MainID;

      // get brand branch
      $branch = $fnbSvc->getBranch($brandId);
      $branch = $fnbSvc->mapData($branch);
      // $branchId = $fnbSvc->getBranchId($branch);

      if (empty($branch)) {
         return false;
      }

      // get all brand dimension within date range
      $dimName = $this->void == true ? ['fnb_void_sales'] : ['fnb_sales'];
      $brandDim = $this->getBrandDimension($brandId, $dimName, $date);
      if (empty($brandDim)) {
         return false;
      }

      $brandDim = $this->prepareDimData($brandDim);

      // parse dimension data
      $result = $this->parseSalesDimensionData($brandDim, $branch);

      return $result;
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
    * parse sales dimension data
    *
    * @param array $data
    * @return array
    */
   public function parseSalesDimensionData($data, $branchData = [])
   {
      $temp = $data[0];
      $result = (object) [];
      $result->MainID = $temp->main_id;
      // $result->branch_id = $temp->branch_id;
      $result->ProductID = $temp->product_id;
      $result->DimensionName = $temp->dimension_name;
      $result->DimensionVersion = (int) $temp->dimension_version;
      $result->Data = [];

      foreach ($data as $currData) {
         // check branch already exist or not
         $tempResultData = $result->Data;
         $branchExist = false;
         foreach ($tempResultData as $currDataIndex => $currDataValue) {
            if ($currDataValue->BranchID == $currData->branch_id) {
               $branchExist = true;
               break;
            }
         }

         if (!$branchExist) {
            // add new branch to dimension data
            $temp = $currData->data;
            $tempResult = (object)[];
            $tempResult->BranchID = (int) ($currData->branch_id ?? null);
            $tempResult->BranchName = $branchData[$tempResult->BranchID]->BranchName ?? '';
            $tempResult->BranchAddress =  $branchData[$tempResult->BranchID]->Address ?? '';
            $tempResult->VAT = (float) ($temp->VAT ?? 0);
            $tempResult->Bill = (float) ($temp->Bill ?? 0);
            $tempResult->Changes = (float) ($temp->Changes ?? 0);
            $tempResult->Discount = (float) ($temp->Discount ?? 0);
            $tempResult->GuestNumber = (float) ($temp->GuestNumber ?? 0);
            $tempResult->Rounding = (float) ($temp->Rounding ?? 0);
            $tempResult->ServiceTax = (float) ($temp->ServiceTax ?? 0);
            $tempResult->TotalBill = (float) ($temp->TotalBill ?? 0);
            $tempResult->TotalItem = (float) ($temp->TotalItem ?? 0);
            $tempResult->TotalBilling = (float) ($temp->TotalBilling ?? 0);
            $tempResult->TotalPayment = (float) ($temp->TotalPayment ?? 0);
            $tempResult->TotalModifier = (float) ($temp->TotalModifier ?? 0);
            $tempResult->TotalItemSales = (float) ($temp->TotalItemSales ?? 0);
            $tempResult->TotalDiscountItem = (float) ($temp->TotalDiscountItem ?? 0);
            $tempResult->TotalModifierSales = (float) ($temp->TotalModifierSales ?? 0);
            $tempResult->ItemSalesDetail = $temp->ItemSalesDetail ?? [];
            $tempResult->PaymentDetail = $temp->PaymentDetail ?? [];

            $result->Data[] = $tempResult;
         } else {
            // update branch data
            $temp = $currData->data;
            $tempResult = $tempResultData[$currDataIndex];
            $tempResult->VAT += (float) ($temp->VAT ?? 0);
            $tempResult->Bill += (float) ($temp->Bill ?? 0);
            $tempResult->Changes += (float) ($temp->Changes ?? 0);
            $tempResult->Discount += (float) ($temp->Discount ?? 0);
            $tempResult->GuestNumber += (float) ($temp->GuestNumber ?? 0);
            $tempResult->Rounding += (float) ($temp->Rounding ?? 0);
            $tempResult->ServiceTax += (float) ($temp->ServiceTax ?? 0);
            $tempResult->TotalBill += (float) ($temp->TotalBill ?? 0);
            $tempResult->TotalItem += (float) ($temp->TotalItem ?? 0);
            $tempResult->TotalBilling += (float) ($temp->TotalBilling ?? 0);
            $tempResult->TotalPayment += (float) ($temp->TotalPayment ?? 0);
            $tempResult->TotalModifier += (float) ($temp->TotalModifier ?? 0);
            $tempResult->TotalItemSales += (float) ($temp->TotalItemSales ?? 0);
            $tempResult->TotalDiscountItem += (float) ($temp->TotalDiscountItem ?? 0);
            $tempResult->TotalModifierSales += (float) ($temp->TotalModifierSales ?? 0);

            // update ItemSalesDetail & PaymentDetail
            $tempResult->ItemSalesDetail = $this->parseItemSalesDetail($tempResult->ItemSalesDetail, $temp->ItemSalesDetail);
            $tempResult->PaymentDetail = $this->parsePaymentDetail($tempResult->PaymentDetail, $temp->PaymentDetail);

            $result->Data[$currDataIndex] = $tempResult;
         }
      }
      return $result;
   }

   /**
    * parse sub data ItemSalesDetail
    *
    * @param array $curData
    * @param array $temp
    * @return array
    */
   private function parseItemSalesDetail($curData, $newData)
   {
      foreach ($newData as $newKey => $newValue) {
         $menuFound = false;
         foreach ($curData as $curKey => $curValue) {
            if ($curValue->MenuID == $newValue->MenuID) {
               $menuFound = true;
               break;
            }
         }

         if (!$menuFound) {
            // add new menu
            $temp = (object)[];
            $temp->MenuID = (int) ($newValue->MenuID ?? null);
            $temp->CategoryID = (int) ($newValue->CategoryID ?? null);
            $temp->Qty = (float) ($newValue->Qty ?? 0);
            $temp->AvgPrice = (float) ($newValue->AvgPrice ?? 0);
            $temp->Discount = (float) ($newValue->Discount ?? 0);
            $temp->SubTotal = (float) ($newValue->SubTotal ?? 0);
            $temp->StartToFinish = (float) ($newValue->StartToFinish ?? 0);
            $temp->ModifierDetail = $newValue->ModifierDetail ?? [];

            $curData[] = $temp;
         } else {
            // update existing data
            $temp = $curValue;
            $temp->Qty += (float) ($newValue->Qty ?? 0);
            $temp->Discount += (float) ($newValue->Discount ?? 0);
            $temp->SubTotal += (float) ($newValue->SubTotal ?? 0);
            if ($temp->Qty == 0) {
               $temp->AvgPrice = 0;
            } else {
               $temp->AvgPrice = (float) ($temp->SubTotal / $temp->Qty);
            }
            $temp->StartToFinish += (float) ($newValue->StartToFinish ?? 0);
            // update modifier
            $temp->ModifierDetail = $this->parseModifierDetail($temp->ModifierDetail, $newValue->ModifierDetail);

            $curData[$curKey] = $temp;
         }
      }

      return $curData;
   }

   /**
    * parse sub data ModifierDetail from ItemSalesDetail
    *
    * @param array $curData
    * @param array $newData
    * @return array
    */
   private function parseModifierDetail($curData, $newData)
   {
      foreach ($newData as $newKey => $newValue) {
         $modifierFound = false;
         foreach ($curData as $curKey => $curValue) {
            if ($curValue->ModifierName == $newValue->ModifierName) {
               $modifierFound = true;
               break;
            }
         }
         if (!$modifierFound) {
            // add new menu
            $temp = (object)[];
            $temp->ModifierName = $newValue->ModifierName ?? null;
            $temp->QtyModifier = (float) ($newValue->QtyModifier ?? 0);
            $temp->TotalSales = (float) ($newValue->TotalSales ?? 0);

            $curData[] = $temp;
         } else {
            // update existing data
            $temp = $curValue;

            $temp->QtyModifier += (float) ($newValue->QtyModifier ?? 0);
            $temp->TotalSales += (float) ($newValue->TotalSales ?? 0);

            $curData[$curKey] = $temp;
         }
      }

      return $curData;
   }

   /**
    * parse sub data PaymentDetail
    *
    * @param array $curData
    * @param array $newData
    * @return array
    */
   private function parsePaymentDetail($curData, $newData)
   {
      foreach ($newData as $newKey => $newValue) {
         $paymentFound = false;
         foreach ($curData as $curKey => $curValue) {
            if ($curValue->PaymentMethodID == $newValue->PaymentMethodID) {
               $paymentFound = true;
               break;
            }
         }
         if (!$paymentFound) {
            // add new menu
            $temp = (object)[];
            $temp->PaymentMethodID = (int) ($newValue->PaymentMethodID ?? null);
            $temp->Payment = (float) ($newValue->Payment ?? 0);
            $temp->PaymentCount = (float) ($newValue->PaymentCount ?? 0);
            $temp->StartToFinish = (float) ($newValue->StartToFinish ?? 0);

            $curData[] = $temp;
         } else {
            // update existing data
            $temp = $curValue;

            $temp->Payment += (float) ($newValue->Payment ?? 0);
            $temp->PaymentCount += (float) ($newValue->PaymentCount ?? 0);
            $temp->StartToFinish += (float) ($newValue->StartToFinish ?? 0);

            $curData[$curKey] = $temp;
         }
      }

      return $curData;
   }
}
