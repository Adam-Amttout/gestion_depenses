<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport financier</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1, h2, h3 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .summary {
            margin-bottom: 30px;
        }
        .positive {
            color: green;
        }
        .negative {
            color: red;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
            }
        }
        button {
            margin-bottom: 20px;
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print();">🖨️ Imprimer / PDF</button>
        <button onclick="window.close();">❌ Fermer</button>
        <hr>
    </div>

    <h1>Rapport {{ $reportType == 'monthly' ? 'Mensuel' : ($reportType == 'yearly' ? 'Annuel' : 'Par catégorie') }}</h1>
    
    @if($reportType == 'monthly')
        <p>Période : {{ $month }}/{{ $year }}</p>
        <div class="summary">
            <p>Total revenus : <strong>{{ number_format($data['totalIncome'], 2) }} DH</strong></p>
            <p>Total dépenses : <strong>{{ number_format($data['totalExpense'], 2) }} DH</strong></p>
            <p>Solde : <strong class="{{ ($data['totalIncome'] - $data['totalExpense']) >= 0 ? 'positive' : 'negative' }}">{{ number_format($data['totalIncome'] - $data['totalExpense'], 2) }} DH</strong></p>
        </div>

        <h3>Dépenses par catégorie</h3>
        <table>
            <thead><tr><th>Catégorie</th><th>Montant (DH)</th></tr></thead>
            <tbody>
                @foreach($data['expensesByCategory'] as $item)
                <tr>
                    <td>{{ $item->category->name ?? 'Sans catégorie' }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <h3>Détail des transactions</h3>
        <table>
            <thead><tr><th>Date</th><th>Description</th><th>Catégorie</th><th>Type</th><th>Montant</th></tr></thead>
            <tbody>
                @foreach($data['transactions'] as $t)
                <tr>
                    <td>{{ $t->date->format('d/m/Y') }}</td>
                    <td>{{ $t->description ?? '-' }}</td>
                    <td>{{ $t->category->name ?? '-' }}</td>
                    <td>{{ $t->type == 'income' ? 'Revenu' : 'Dépense' }}</td>
                    <td style="color: {{ $t->type == 'income' ? 'green' : 'red' }};">{{ $t->type == 'income' ? '+' : '-' }} {{ number_format($t->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    @elseif($reportType == 'yearly')
        <p>Année : {{ $year }}</p>
        <div class="summary">
            <p>Total revenus : <strong>{{ number_format($data['totalYearIncome'], 2) }} DH</strong></p>
            <p>Total dépenses : <strong>{{ number_format($data['totalYearExpense'], 2) }} DH</strong></p>
            <p>Solde : <strong class="{{ ($data['totalYearIncome'] - $data['totalYearExpense']) >= 0 ? 'positive' : 'negative' }}">{{ number_format($data['totalYearIncome'] - $data['totalYearExpense'], 2) }} DH</strong></p>
        </div>

        <h3>Récapitulatif mensuel</h3>
        <table>
            <thead><tr><th>Mois</th><th>Revenus (DH)</th><th>Dépenses (DH)</th><th>Solde (DH)</th></tr></thead>
            <tbody>
                @foreach($data['monthlyData'] as $month => $values)
                <tr>
                    <td>{{ $month }}</td>
                    <td>{{ number_format($values['income'], 2) }}</td>
                    <td>{{ number_format($values['expense'], 2) }}</td>
                    <td class="{{ $values['balance'] >= 0 ? 'positive' : 'negative' }}">{{ number_format($values['balance'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <h3>Résumé par catégorie (annuel)</h3>
        @php
            $summary = [];
            foreach($data['categoriesSummary'] as $cat) {
                $summary[$cat->category->name][$cat->type] = $cat->total;
            }
        @endphp
        <table>
            <thead><tr><th>Catégorie</th><th>Revenus (DH)</th><th>Dépenses (DH)</th><th>Solde (DH)</th></tr></thead>
            <tbody>
                @foreach($summary as $catName => $values)
                <tr>
                    <td>{{ $catName }}</td>
                    <td>{{ number_format($values['income'] ?? 0, 2) }}</td>
                    <td>{{ number_format($values['expense'] ?? 0, 2) }}</td>
                    <td class="{{ (($values['income'] ?? 0) - ($values['expense'] ?? 0)) >= 0 ? 'positive' : 'negative' }}">{{ number_format(($values['income'] ?? 0) - ($values['expense'] ?? 0), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    @elseif($reportType == 'category')
        <p>Année : {{ $year }}</p>
        <div class="summary">
            <p>Total revenus : <strong>{{ number_format($data['totalYearIncome'], 2) }} DH</strong></p>
            <p>Total dépenses : <strong>{{ number_format($data['totalYearExpense'], 2) }} DH</strong></p>
            <p>Solde : <strong class="{{ ($data['totalYearIncome'] - $data['totalYearExpense']) >= 0 ? 'positive' : 'negative' }}">{{ number_format($data['totalYearIncome'] - $data['totalYearExpense'], 2) }} DH</strong></p>
        </div>

        <h3>Détail par catégorie</h3>
        @foreach($data['categories'] as $category)
            @php
                $transactions = $category->transactions;
                $totalCatIncome = $transactions->where('type', 'income')->sum('amount');
                $totalCatExpense = $transactions->where('type', 'expense')->sum('amount');
            @endphp
            <div style="margin-top: 20px;">
                <h4>{{ $category->name }}</h4>
                <p>Revenus : {{ number_format($totalCatIncome, 2) }} DH</p>
                <p>Dépenses : {{ number_format($totalCatExpense, 2) }} DH</p>
                <p>Solde : {{ number_format($totalCatIncome - $totalCatExpense, 2) }} DH</p>
                @if($transactions->count())
                <table style="width: auto;">
                    <thead><tr><th>Date</th><th>Description</th><th>Montant</th></tr></thead>
                    <tbody>
                        @foreach($transactions as $t)
                        <tr>
                            <td>{{ $t->date->format('d/m/Y') }}</td>
                            <td>{{ $t->description ?? '-' }}</td>
                            <td style="color: {{ $t->type == 'income' ? 'green' : 'red' }};">{{ $t->type == 'income' ? '+' : '-' }} {{ number_format($t->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        @endforeach
    @endif
</body>
</html>