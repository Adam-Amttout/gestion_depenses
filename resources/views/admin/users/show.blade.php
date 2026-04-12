@extends('layouts.app')

@section('title', 'Profil de ' . $user->name)
@section('header', 'Profil utilisateur')

@section('content')
<div class="row">
    <!-- Informations personnelles -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-circle"></i> {{ $user->name }}</h5>
            </div>
            <div class="card-body">
                <p><strong>Email :</strong> {{ $user->email }}</p>
                <p><strong>Téléphone :</strong> {{ $user->phone ?? 'Non renseigné' }}</p>
                <p><strong>Adresse :</strong> {{ $user->address ?? 'Non renseignée' }}</p>
                <p><strong>Rôle :</strong> 
                    <span class="badge {{ $user->isAdmin() ? 'bg-danger' : 'bg-secondary' }}">
                        {{ $user->isAdmin() ? 'Administrateur' : 'Utilisateur' }}
                    </span>
                </p>
                <p><strong>Statut :</strong> 
                    <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $user->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </p>
                <p><strong>Membre depuis :</strong> {{ $user->created_at->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Cartes financières -->
    <div class="col-md-8 mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6>Total revenus</h6>
                        <h3>{{ number_format($totalIncome, 2) }} DH</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6>Total dépenses</h6>
                        <h3>{{ number_format($totalExpense, 2) }} DH</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card {{ $balance >= 0 ? 'bg-info' : 'bg-warning' }} text-white">
                    <div class="card-body">
                        <h6>Solde actuel</h6>
                        <h3>{{ number_format($balance, 2) }} DH</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Graphiques (Chart.js) -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                Dépenses par catégorie
            </div>
            <div class="card-body">
                <canvas id="expensesChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                Revenus par catégorie
            </div>
            <div class="card-body">
                <canvas id="incomesChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Dernières transactions -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                Dernières transactions
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Catégorie</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->date->format('d/m/Y') }}</td>
                                <td>{{ $transaction->description ?? '-' }}</td>
                                <td>{{ $transaction->category->name }}</td>
                                <td class="{{ $transaction->type == 'expense' ? 'text-danger' : 'text-success' }}">
                                    {{ $transaction->type == 'expense' ? '-' : '+' }} {{ number_format($transaction->amount, 2) }} DH
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">Aucune transaction</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Graphique des dépenses par catégorie
    const expensesCtx = document.getElementById('expensesChart').getContext('2d');
    new Chart(expensesCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode($expensesByCategory->pluck('category.name')) !!},
            datasets: [{
                data: {!! json_encode($expensesByCategory->pluck('total')) !!},
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
            }]
        }
    });

    // Graphique des revenus par catégorie
    const incomesCtx = document.getElementById('incomesChart').getContext('2d');
    new Chart(incomesCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode($incomesByCategory->pluck('category.name')) !!},
            datasets: [{
                data: {!! json_encode($incomesByCategory->pluck('total')) !!},
                backgroundColor: ['#2ECC71', '#3498DB', '#9B59B6', '#E67E22', '#1ABC9C']
            }]
        }
    });
</script>
@endpush
@endsection