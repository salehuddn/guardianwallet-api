<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'registration_no',
        'email',
        'phone',
        'merchant_type_id'
    ];

    public function wallet()
    {
        return $this->hasOne(MerchantWallet::class);
    }

    public function type()
    {
        return $this->belongsTo(MerchantType::class, 'merchant_type_id');
    }
}
