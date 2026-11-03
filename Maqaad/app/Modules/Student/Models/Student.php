<?php

namespace App\Modules\Student\Models;

use App\Modules\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $table = 'students';

    public $timestamps = false;

    protected $guarded = [];

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }
}