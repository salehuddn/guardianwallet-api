<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantWallet extends Model
{
    use HasFactory;

    protected $table = 'merchant_wallets';

    protected $fillable = [
        'merchant_id',
        'balance',
        'status'
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
