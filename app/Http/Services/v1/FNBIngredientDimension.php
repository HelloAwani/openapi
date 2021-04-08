<?php

namespace Service\Http\Services\v1;

use Service\Http\Services\v1\FNBService;

class FNBIngredientDimension extends FNBDimension
{
   public function __construct()
   {
      parent::__construct();
   }

   /**
    * get brand/branch dimension and parse it
    *
    * @param object $tokenData
    * @param array $date
    * @return object
    */
   public function getDimension($tokenData, $date){
      $brandId = $tokenData->MainID;

      $brandDim = $this->getBrandDimension($brandId, ['fnb_ingredient_transaction'], $date);
      if(empty($brandDim)) return false;
      $brandDim = $this->prepareDimData($brandDim);

      $result = $this->parseDimData($brandDim);
      
      return $result;
   }

   /**
    * parse ingredient transaction dimension data
    *
    * @param array $data
    * @return object
    */
   private function parseDimData($data){
      $temp = $data[0];
      $result = (object) [];
      $result->main_id = $temp->main_id;
      $result->branch_id = $temp->branch_id;
      $result->product_id = $temp->product_id;
      $result->dimension_name = $temp->dimension_name;
      $result->dimension_version = $temp->dimension_version;
      $result->data = [];

      $dimData = (object)[];
      $dimData->QtyTotal = 0;
      $dimData->PriceTotal = 0;
      $dimData->AvgPrice = 0;
      $dimData->IngredientTransaction = (object)[];

      foreach ($data as $key => $value) {
         $temp2 = $value->data;
         $dimData->QtyTotal += $temp2->QtyTotal ?? 0;
         $dimData->PriceTotal += $temp2->PriceTotal ?? 0;
         foreach ($temp2->IngredientTransaction as $trxType => $trxValue) {
            $trxTypeFound = false;
            // find if ingredient transaction type already exist
            foreach ($dimData->IngredientTransaction as $curTrxType => $curTrxValue) {
               if($trxType == $curTrxType){
                  $trxTypeFound = true;
                  break;
               }
            }

            if($trxTypeFound == false){
               // add trxType
               $dimData->IngredientTransaction->$trxType = $trxValue;
               break;
            }
            else{
               // parse trxType
               foreach ($trxValue as $trxData) {
                  $invAlreadyExist = false;
                  foreach ($dimData->IngredientTransaction->$trxType as $key2 => $value2) {
                     if($value2->InventoryID == $trxData->InventoryID){
                        $invAlreadyExist = true;
                        break;
                     }
                  }

                  if($invAlreadyExist){
                     // update ingredient transaction data
                     $newData = $dimData->IngredientTransaction->$trxType[$key2];

                     $newData->Qty += $trxData->Qty;
                     $newData->PriceTotal += $trxData->PriceTotal;
                     $newData->AvgPrice = abs( $newData->PriceTotal / $newData->Qty );

                     // parse price qty record
                     foreach ($trxData->PriceQtyRecord as $tempPrice => $tempQty) {
                        $priceFound = false;
                        foreach ($newData->PriceQtyRecord as $tempPriceCurrIndex => $tempQtyCurr) {
                           if($tempQty->Price == $tempQtyCurr->Price){
                              $priceFound = true;
                              break;
                           }
                        }

                        if($priceFound){
                           // update PriceQtyRecord
                           $newData->PriceQtyRecord[$tempPriceCurrIndex]->Qty += $tempQty->Qty;
                        }
                        else{
                           // add new PriceQtyRecord
                           $newData->PriceQtyRecord[] = $tempQty;
                        }
                     }

                     // update parsed dimension data
                     $dimData->IngredientTransaction->$trxType[$key2] = $newData;
                  }
                  else{
                     $dimData->IngredientTransaction->$trxType[] = $trxData;
                  }
               }
            }
         }
      }
      $dimData->AvgPrice = abs($dimData->PriceTotal / $dimData->QtyTotal);
      $result->data = $dimData;

      return $result;
   }
   

}
