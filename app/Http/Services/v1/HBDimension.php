<?php

namespace Service\Http\Services\v1;

class HBDimension
{
   /**
    * Unprocessed dimension from db
    *
    * @var array
    */
   protected $rawDimension = [];

   /**
    * processed dimension / result
    *
    * @var array
    */
   protected $dimension = [];

   public function __construct()
   {
      // parent::__construct();
   }

   /**
    * merge dimension data that doesnt have id, example: TotalBilling, TotalPayment
    *
    * @param array $oldData
    * @param array $newData
    * @param array $filter array of index that will be removed from $newData
    * @return array
    */
   protected function mergeDimension($oldData, $newData, $filter = [])
   {
      // process filter
      foreach ($filter ?? [] as $filterValue) {
         unset($newData[$filterValue]);
      }

      foreach ($newData as $index => $value) {
         $oldData[$index] = strval($oldData[$index] + $value);
      }

      return $oldData;
   }

   /**
    * merge dimension data that have id, example: ItemSalesDetail (for each MenuID)
    *
    * @param array $oldData
    * @param array $newData
    * @param string $identifier id identifier in data
    * @param array $filter array of index that will be ignored from $newData
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
