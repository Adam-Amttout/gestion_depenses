<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $userId = session('user_id');
        $years = Transaction::where('user_id', $userId)
            ->select(DB::raw('YEAR(date) as year'))
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
            
        return view('reports.index', compact('years'));
    }
    
    public function generate(Request $request)
    {
        $userId = session('user_id');
        
        $request->validate([
            'report_type' => 'required|in:monthly,yearly,category',
            'year' => 'required|integer',
            'month' => 'required_if:report_type,monthly|integer|min:1|max:12'
        ]);
        
        $reportType = $request->report_type;
        $year = $request->year;
        
        if($reportType == 'monthly') {
            $month = $request->month;
            $data = $this->getMonthlyReport($userId, $year, $month);
            return view('reports.monthly', compact('data', 'year', 'month'));
        }
        elseif($reportType == 'yearly') {
            $data = $this->getYearlyReport($userId, $year);
            return view('reports.yearly', compact('data', 'year'));
        }
        elseif($reportType == 'category') {
            $data = $this->getCategoryReport($userId, $year);
            return view('reports.category', compact('data', 'year'));
        }
    }

    /**
     * Version imprimable d'un rapport (sans layout, avec style d'impression)
     */
    public function printReport(Request $request)
    {
        $userId = session('user_id');
        $reportType = $request->report_type;
        $year = $request->year;
        $month = $request->month;

        if($reportType == 'monthly') {
            $data = $this->getMonthlyReport($userId, $year, $month);
            return view('reports.printable', compact('data', 'year', 'month', 'reportType'));
        }
        elseif($reportType == 'yearly') {
            $data = $this->getYearlyReport($userId, $year);
            return view('reports.printable', compact('data', 'year', 'reportType'));
        }
        elseif($reportType == 'category') {
            $data = $this->getCategoryReport($userId, $year);
            return view('reports.printable', compact('data', 'year', 'reportType'));
        }
    }
    
    private function getMonthlyReport($userId, $year, $month)
    {
        $totalIncome = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('amount');
            
        $totalExpense = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('amount');
            
        $expensesByCategory = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->with('category')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->get();
            
        $transactions = Transaction::where('user_id', $userId)
            ->with('category')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'desc')
            ->get();
            
        return compact('totalIncome', 'totalExpense', 'expensesByCategory', 'transactions');
    }
    
    private function getYearlyReport($userId, $year)
    {
        $monthlyData = [];
        $totalYearIncome = 0;
        $totalYearExpense = 0;
        
        for($month = 1; $month <= 12; $month++) {
            $income = Transaction::where('user_id', $userId)
                ->where('type', 'income')
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('amount');
                
            $expense = Transaction::where('user_id', $userId)
                ->where('type', 'expense')
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('amount');
                
            $monthlyData[$month] = [
                'income' => $income,
                'expense' => $expense,
                'balance' => $income - $expense
            ];
            
            $totalYearIncome += $income;
            $totalYearExpense += $expense;
        }
        
        $categoriesSummary = Transaction::where('user_id', $userId)
            ->whereYear('date', $year)
            ->with('category')
            ->select('category_id', 'type', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id', 'type')
            ->get();
            
        return compact('monthlyData', 'totalYearIncome', 'totalYearExpense', 'categoriesSummary');
    }
    
    private function getCategoryReport($userId, $year)
    {
        $categories = Category::where(function($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('is_default', true);
            })
            ->with(['transactions' => function($query) use ($year, $userId) {
                $query->where('user_id', $userId)->whereYear('date', $year);
            }])
            ->get();
        
        $totalYearIncome = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereYear('date', $year)
            ->sum('amount');
            
        $totalYearExpense = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('date', $year)
            ->sum('amount');
            
        return compact('categories', 'totalYearIncome', 'totalYearExpense', 'year');
    }
}