<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetAnalysis extends Model
{
    use HasFactory;

    protected $table = 'budget_analysis';

    protected $fillable = [
        'user_id',
        'month',
        'income',
        'spending',
        'limits',
        'analysis',
        'recommendations',
        'percentages',
    ];

    protected $casts = [
        'spending' => 'array',
        'limits' => 'array',
        'analysis' => 'array',
        'recommendations' => 'array',
        'percentages' => 'array',
    ];
}
