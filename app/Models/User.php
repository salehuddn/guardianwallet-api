<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'dob',
        'spending_limit',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'dob' => 'date',
    ];

    protected $with = [
        'roles'
    ];

    public function dependants()
    {
        return $this->belongsToMany(User::class, 'dependant_guardian', 'guardian_id', 'dependant_id');
    }

    public function guardians()
    {
        return $this->belongsToMany(User::class, 'dependant_guardian', 'dependant_id', 'guardian_id');
    }

    public function wallet()
    {
        return $this->hasOne(UserWallet::class);
    }

    public function transactions()
    {
        return $this->hasMany(UserTransaction::class);
    }

    public function setSpendingLimit($limit)
    {
        $this->spending_limit = $limit;
        $this->save();
    }

    public function getSpendingLimit()
    {
        return $this->spending_limit;
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            $user->wallet()->create([
                'balance' => 0.00,
                'status' => 'active',
            ]);
        });
    }

    public function getTotalIncome($date = null)
    {
        if (!$this->hasRole('dependant')) {
            return 0;
        }

        /**
         * Transaction Type (ID)
         * 4 - Receive Fund
         */
        $query = $this->transactions()->where('transaction_type_id', 4);

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        return $query->sum('amount');
    }
}
