<?php

namespace Service\Models;

use Illuminate\Database\Eloquent\Model;

class Authenticator extends Model
{
    protected $table = 'Authenticator';
    public $timestamps = false;
    protected $fillable = ['LastAccessed'];
}
