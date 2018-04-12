<?php

namespace Service\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'User';
    public $timestamps = false;
    const DELETED_AT = 'Archived';
    protected $primaryKey = 'UserID';

    protected $fillable = ['UserCode','ShiftID','UserTypeID','Fullname','JoinDate','BrandID','BranchID','Description','ActiveStatus','PIN','StatusOnline','Email','Password'];
}
