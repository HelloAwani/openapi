<?php 
namespace Service\Interfaces;
 
interface Space {
    // get
    public function getDataTable($display, $column, $perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param);
}