<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'avatar', 'is_active', 'phone', 'address',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Calcul du solde (revenus - dépenses)
     */
    public function getBalanceAttribute()
    {
        $totalIncome = $this->transactions()->where('type', 'income')->sum('amount');
        $totalExpense = $this->transactions()->where('type', 'expense')->sum('amount');
        return $totalIncome - $totalExpense;
    }

    // Relations
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function savingsGoals()
    {
        return $this->hasMany(SavingsGoal::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}