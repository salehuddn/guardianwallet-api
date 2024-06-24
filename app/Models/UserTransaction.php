<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTransaction extends Model
{
    use HasFactory;

    protected $table = 'user_transactions';

    protected $fillable = [
        'user_id',
        'merchant_id',
        'transaction_type_id',
        'reference',
        'narration',
        'amount',
        'status',
        'pending_at',
        'completed_at',
        'failed_at'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->reference = uniqid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
