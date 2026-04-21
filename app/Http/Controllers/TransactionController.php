<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $userId = session('user_id');
        $query = Transaction::where('user_id', $userId)->with('category');
        
        if($request->filled('type') && $request->type != 'all') {
            $query->where('type', $request->type);
        }
        if($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('amount', 'like', '%' . $request->search . '%');
        }
        
        $transactions = $query->latest('date')->paginate(15);
        $categories = Category::where(function($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('is_default', true);
        })->get();
        
        $totalIncome = Transaction::where('user_id', $userId)->where('type', 'income')->sum('amount');
        $totalExpense = Transaction::where('user_id', $userId)->where('type', 'expense')->sum('amount');
        
        return view('transactions.index', compact('transactions', 'categories', 'totalIncome', 'totalExpense'));
    }
    
    public function create()
    {
        $userId = session('user_id');
        $categories = Category::where(function($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('is_default', true);
        })->get();
        return view('transactions.create', compact('categories'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'date' => 'required|date',
            'type' => 'required|in:income,expense'
        ]);

        $userId = session('user_id');

        if ($request->type === 'expense') {
            $totalIncome = Transaction::where('user_id', $userId)->where('type', 'income')->sum('amount');
            $totalExpense = Transaction::where('user_id', $userId)->where('type', 'expense')->sum('amount');
            $currentBalance = $totalIncome - $totalExpense;

            if ($request->amount > $currentBalance) {
                return redirect()->back()
                    ->with('error', "Solde insuffisant ! Votre solde actuel est de {$currentBalance} DH.")
                    ->withInput();
            }
        }

        $validated['user_id'] = $userId;
        Transaction::create($validated);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction ajoutée avec succès!');
    }
    
    public function show(Transaction $transaction)
    {
        if ($transaction->user_id != session('user_id')) abort(403);
        return view('transactions.show', compact('transaction'));
    }
    
    public function edit(Transaction $transaction)
    {
        if ($transaction->user_id != session('user_id')) abort(403);
        $userId = session('user_id');
        $categories = Category::where(function($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('is_default', true);
        })->get();
        return view('transactions.edit', compact('transaction', 'categories'));
    }
    
    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id != session('user_id')) abort(403);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'date' => 'required|date',
            'type' => 'required|in:income,expense'
        ]);

        $userId = session('user_id');

        if ($request->type === 'expense') {
            $totalIncome = Transaction::where('user_id', $userId)->where('type', 'income')->sum('amount');
            $totalExpense = Transaction::where('user_id', $userId)->where('type', 'expense')->sum('amount');
            $currentBalance = $totalIncome - $totalExpense;
            $balanceBeforeUpdate = $currentBalance + $transaction->amount;

            if ($request->amount > $balanceBeforeUpdate) {
                return redirect()->back()
                    ->with('error', "Solde insuffisant pour cette modification ! Solde disponible avant modification : {$balanceBeforeUpdate} DH.")
                    ->withInput();
            }
        }

        $transaction->update($validated);
        return redirect()->route('transactions.index')
            ->with('success', 'Transaction modifiée avec succès!');
    }
    
    /**
     * Supprimer une transaction (vérification solde pour les revenus)
     */
    public function destroy(Transaction $transaction)
    {
        if ($transaction->user_id != session('user_id')) {
            abort(403, 'Accès non autorisé.');
        }

        // Si c'est un revenu, vérifier que sa suppression ne rend pas le solde négatif
        if ($transaction->type === 'income') {
            $userId = session('user_id');
            $totalIncome = Transaction::where('user_id', $userId)->where('type', 'income')->sum('amount');
            $totalExpense = Transaction::where('user_id', $userId)->where('type', 'expense')->sum('amount');
            $currentBalance = $totalIncome - $totalExpense;
            $balanceAfter = $currentBalance - $transaction->amount;

            if ($balanceAfter < 0) {
                return redirect()->route('transactions.index')
                    ->with('error', "Impossible de supprimer ce revenu de {$transaction->amount} DH car votre solde deviendrait négatif ({$balanceAfter} DH).");
            }
        }

        $transaction->delete();
        return redirect()->route('transactions.index')
            ->with('success', 'Transaction supprimée avec succès!');
    }
    
    /**
     * Suppression groupée (bulk delete) avec vérification des revenus
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:transactions,id'
        ]);

        $userId = session('user_id');
        $transactions = Transaction::whereIn('id', $request->ids)
            ->where('user_id', $userId)
            ->get();

        // Vérifier l'impact sur le solde pour les revenus
        $totalIncomeToDelete = $transactions->where('type', 'income')->sum('amount');
        $currentTotalIncome = Transaction::where('user_id', $userId)->where('type', 'income')->sum('amount');
        $totalExpense = Transaction::where('user_id', $userId)->where('type', 'expense')->sum('amount');
        $currentBalance = $currentTotalIncome - $totalExpense;
        $balanceAfter = $currentBalance - $totalIncomeToDelete;

        if ($balanceAfter < 0) {
            return redirect()->route('transactions.index')
                ->with('error', "Suppression groupée impossible : votre solde deviendrait négatif ({$balanceAfter} DH) si vous supprimez ces revenus.");
        }

        Transaction::whereIn('id', $request->ids)
            ->where('user_id', $userId)
            ->delete();

        return redirect()->route('transactions.index')
            ->with('success', 'Transactions supprimées avec succès!');
    }
}