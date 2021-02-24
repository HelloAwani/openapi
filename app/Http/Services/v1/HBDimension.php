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
    * dimension data index
    * $dimension[x]->$dimensionDataIndex
    *
    * @var string
    */
   protected $dimensionDataIndex = "Data";

   /**
    * $dimensionData structure
    * 1 = static value, ex: CategoryID in ItemSalesDetail
    * 2 = add value ( + ), ex: TotalSales
    * 3 = identifier, unique value, only work on nested array, ex: PaymentMethodID
    *
    * @var array
    */
   protected $dimensionStructure = [];
   protected $structure = [];
   /**
    * final result
    *
    * @var array
    */
   protected $dimension = [];

   public function __construct()
   {
      // parent::__construct();
      $this->structure = $this->parseStructure();
   }

   /**
    * merge dimensionData according to dimension structure recursively
    *
    * @param array $currDimension
    * @param array $newDimension
    * @param string $parent parent index, separated by comma, ex: 'ItemSalesDetail,ModifierDetail'
    * @return void
    */
   // protected function mergeDimension2($currDimension, $newDimension, $parent = [])
   // {
   //    $result = $currDimension;
   //    foreach ($newDimension as $index => $value) {
   //       if (is_array($value) && isset($value[0])) {
   //          $parent[] = $index;
   //          $temp = end($parent);
   //          $this->mergeDimension2($currDimension[$temp], $newDimension[$temp], $parent);
   //       } else {
   //          $dataType = $this->dimensionStructure;

   //          // process data structure parent
   //          if ($parent) {
   //             foreach ($parent as $v) {
   //                $dataType = $dataType[$v];
   //             }
   //          }
   //          // check if identifier exist
   //          $identifierIndex = array_search(3, $dataType);

   //          // set $dataType
   //          if (!empty($parent)) {
   //             foreach ($value as $index2 => $value2) {
   //                # code...
   //                $dataType = $dataType[$index2];
   //             }

   //             // if identifier found, and parent is set, search for existing data
   //             if (!empty($identifierIndex) && !empty($parent)) {
   //                // search for existing data
   //                foreach ($newDimension as $k => $v) {
   //                }
   //             }
   //          } else {
   //             $dataType = $dataType[$index];
   //             $result[$index] = $this->processDimensionData($dataType, $currDimension[$index], $value);

   //             // merge data
   //             // switch ($dataType) {
   //             //    case '1':
   //             //       # do nothing
   //             //       break;

   //             //    case '2':
   //             //       // add new value to old value
   //             //       $result[$index] = strval($currDimension[$index] + $value);
   //             //       break;

   //             //    case '3':
   //             //       # code...
   //             //       break;

   //             //    default:
   //             //       # code...
   //             //       break;
   //             // }
   //          }
   //       }
   //    }
   //    return $result;
   // }

   protected function mergeDimension3($old, $new, $parent = [])
   {
      // search for identifier in current array level
      if (empty($parent)) {
         $identifierIndex = array_search(3, $this->dimensionStructure);
         $indexToProcess = $this->structure;
         $filter = $this->getFilter($this->dimensionStructure);
      } else {
         $currJsonStructure = $this->dimensionStructure;
         $indexToProcess = $this->structure;
         foreach ($parent as $k => $v) {
            $currJsonStructure = $currJsonStructure[$v] ?? [];
            $indexToProcess = $indexToProcess[$v] ?? [];
         }
         $identifierIndex = array_search(3, $currJsonStructure);
         $filter = $this->getFilter($currJsonStructure);
      }

      if (empty($identifierIndex)) {
         $data1 = $this->mergeDimension($old, $new, $filter);
      } else {
         if (empty($parent)) {
         }
         $data1 = $this->mergeDimensionWithId($old, $new, $identifierIndex, $filter);
      }

      // search if array in this level have more array
      foreach ($indexToProcess as $key => $value) {
         $a = $parent;
         $a[] = $key;
         $data1[$key] = $this->mergeDimension3($old, $new, $a);
      }

      return $data1;
   }

   /**
    * get filter for dimension parsing
    *
    * @param array $structure
    * @return void
    */
   private function getFilter($structure)
   {
      $a = [];
      foreach ($structure as $key => $value) {
         if ($value != 2) {
            $a[] = $key;
         }
      }
      return $a;
   }

   protected function parseStructure($structure = [])
   {
      if (empty($structure)) $structure = $this->dimensionStructure;
      $result = [];
      foreach ($structure as $key => $value) {
         # code...
         if (is_array($value)) {
            $result[$key] = [];
            $temp = $this->parseStructure($value);
            if (!empty($temp)) {
               $result[$key] = $temp;
            }
         }
      }
      return $result;
   }

   private function processDimensionData($type, $curr, $new = null)
   {
      switch ($type) {
         case '1':
            # do nothing
            $result = strval($curr);
            break;

         case '2':
            // add new value to old value
            $result = strval($curr + $new);
            break;

         case '3':
            # code...
            $result = strval($curr);
            break;

         default:
            // run function
            break;
      }

      return $result;
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

   /**
    * merge dimension data that have id, example: ItemSalesDetail (for each MenuID)
    *
    * @param array $oldData
    * @param array $newData array of data to be added
    * @param string $identifier id identifier in data, ex: MenuID, PaymentMethodID
    * @param array $filter array of index that will be ignored from $newData, ex: avgPrice
    * @return array
    */
   protected function mergeDimensionWithId2($oldData, $newData, $identifier, $filter = [])
   {
      $result = $oldData;
      foreach ($newData as $new) {
         $isNew = true;

         // search for existing data
         foreach ($oldData as $oldIndex => $old) {
            if ($old->$identifier == $new->$identifier) {
               // found existing data
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

            //  check for subarray
            // if identifer = MenuID do merge for the subdata
            if ($identifier == 'MenuID') {
               foreach ($new->ModifierDetail as $modValue) {
                  $newModifier = true;
                  foreach ($result[$oldIndex]->ModifierDetail as $key => $value) {
                     if ($value->ModifierName == $modValue->ModifierName) {
                        $newModifier = false;

                        break;
                     }
                  }

                  // insert new modifier
                  if ($newModifier) {
                     $result[$oldIndex]->ModifierDetail[] = $modValue;
                  } else {
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

   protected function processDimensionSubData($new){
      $result = [];
      foreach ($new->ModifierDetail as $modKey => $modValue) {
         $newModifier = true;
         foreach ($result[$oldIndex]->ModifierDetail as $key => $value) {
            if ($value->ModifierName == $modValue->ModifierName) {
               $newModifier = false;

               break;
            }
         }

         // insert new modifier
         if ($newModifier) {
            $result[$oldIndex]->ModifierDetail[] = $modValue;
         } else {
            // update modifier
            $temp1 = $result[$oldIndex]->ModifierDetail[$key];
            $temp1->TotalSales = strval($temp1->TotalSales + $modValue->TotalSales);
            $temp1->QtyModifier = strval($temp1->QtyModifier + $modValue->QtyModifier);
            $result[$oldIndex]->ModifierDetail[$key] = $temp1;
         }
      }
   }
}
