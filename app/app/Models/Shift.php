<?php

namespace Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use SoftDeletes;

    protected $table = 'Shift';
    protected $fillable = ['ShiftCode','ShiftName','From','To','BrandID','BranchID'];
    public $timestamps = false;
    const DELETED_AT = 'Archived';
    protected $primaryKey = 'ShiftID';
}
