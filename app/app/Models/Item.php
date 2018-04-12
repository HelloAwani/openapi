<?php

namespace Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
	use SoftDeletes;
	
    protected $table = 'Item';
    protected $fillable = ['ItemCode','ItemName','LocalID','CategoryID','Price','BranchID','BrandID','Image','CurrentStock', 'CommissionValue', 'CommissionPercent'];
    public $timestamps = false;
    const DELETED_AT = 'Archived';
    protected $primaryKey = 'ItemID';

    public function branch()
    {
        return $this->belongsTo('Service\Models\Branch','BranchID');
    }
}
