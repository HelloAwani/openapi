<?php

namespace Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemCategory extends Model
{
	use SoftDeletes;
	
    protected $table = 'Category';
    protected $fillable = ['CategoryName', 'CategoryCode', 'LocalID', 'ImageID', 'BranchID', 'BrandID'];
    public $timestamps = false;
    const DELETED_AT = 'Archived';
    protected $primaryKey = 'CategoryID';
    
    public function items()
    {
        return $this->hasMany('Service\Models\Item', 'CategoryID');
    }

    public function branch()
    {
        return $this->belongsTo('Service\Models\Branch','BranchID');
    }
}
