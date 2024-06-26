<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Savings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'name', 
        'goal_amount', 
        'amount', 
        'remaining'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($saving) {
            $saving->amount = $saving->goal_amount;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
