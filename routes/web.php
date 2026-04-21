<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\UserManagementController;

// Routes d'authentification (non protégées)
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Routes protégées (authentification requise)
Route::middleware(['auth'])->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Profil
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Transactions
    Route::resource('transactions', TransactionController::class);
    Route::post('transactions/bulk-delete', [TransactionController::class, 'bulkDelete'])->name('transactions.bulk-delete');
    
    // Catégories
    Route::resource('categories', CategoryController::class);
    
    // Budgets
    Route::resource('budgets', BudgetController::class);
    
    // Rapports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    // Dans le groupe middleware auth
Route::post('reports/print', [ReportController::class, 'printReport'])->name('reports.print');   
    
    // Routes Admin (protégées par middleware 'admin')
    Route::prefix('admin')->name('admin.')->middleware(['admin'])->group(function () {
        // CRUD complet pour les utilisateurs
        Route::resource('users', UserManagementController::class);
        // Route pour activer/désactiver un utilisateur
        Route::get('users/{user}/toggle', [UserManagementController::class, 'toggleStatus'])->name('users.toggle');
    });
});