<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Models\Student;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Finance\Models\Paiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdministrationService
{
    /**
     * Récupérer les utilisateurs administratifs
     */
    public function getAdminUsers(array $filters = []): \Illuminate\Support\Collection
    {
        $adminRoles = ['chef_cap', 'chef_division', 'chef_division_continue', 'comptable', 'secretaire'];
        
        $query = User::whereHas('roles', function ($q) use ($adminRoles) {
            $q->whereIn('name', $adminRoles);
        })->with(['roles' => function ($query) {
            $query->select('roles.id', 'roles.name', 'roles.display_name');
        }]);

        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->select('id', 'first_name', 'last_name', 'email', 'phone', 'photo')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Récupérer les statistiques du tableau de bord
     */
    public function getDashboardStats(): array
    {
        return [
            'users' => [
                'total' => User::count(),
                'active' => User::whereNotNull('email_verified_at')->count(),
            ],
            'students' => [
                'total' => Student::count(),
                'active' => Student::where('is_active', true)->count(),
            ],
            'pending_students' => [
                'total' => PendingStudent::count(),
                'pending' => PendingStudent::where('status', 'pending')->count(),
                'approved' => PendingStudent::where('status', 'approved')->count(),
                'rejected' => PendingStudent::where('status', 'rejected')->count(),
            ],
            'payments' => [
                'total' => Paiement::count(),
                'pending' => Paiement::where('statut', 'attente')->count(),
                'accepted' => Paiement::where('statut', 'accepte')->count(),
                'rejected' => Paiement::where('statut', 'rejete')->count(),
                'total_amount' => Paiement::where('statut', 'accepte')->sum('montant'),
            ],
        ];
    }

    /**
     * Récupérer les activités récentes
     */
    public function getRecentActivities(int $limit = 20): array
    {
        $activities = [];

        // Derniers utilisateurs créés
        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at'])
            ->map(fn($user) => [
                'type' => 'user_created',
                'description' => "Nouvel utilisateur: {$user->first_name} {$user->last_name}",
                'timestamp' => $user->created_at,
                'data' => $user,
            ]);

        // Derniers étudiants en attente
        $recentPending = PendingStudent::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'email', 'status', 'created_at'])
            ->map(fn($student) => [
                'type' => 'pending_student_created',
                'description' => "Nouvelle demande: {$student->first_name} {$student->last_name}",
                'timestamp' => $student->created_at,
                'data' => $student,
            ]);

        // Derniers paiements
        $recentPayments = Paiement::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'matricule', 'montant', 'statut', 'created_at'])
            ->map(fn($payment) => [
                'type' => 'payment_created',
                'description' => "Nouveau paiement: {$payment->montant} FCFA ({$payment->matricule})",
                'timestamp' => $payment->created_at,
                'data' => $payment,
            ]);

        // Combiner et trier par timestamp
        $activities = collect()
            ->merge($recentUsers)
            ->merge($recentPending)
            ->merge($recentPayments)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values()
            ->toArray();

        return $activities;
    }

    /**
     * Récupérer la santé du système
     */
    public function getSystemHealth(): array
    {
        $health = [
            'database' => 'ok',
            'storage' => 'ok',
            'errors' => [],
        ];

        try {
            // Test database
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $health['database'] = 'error';
            $health['errors'][] = 'Database connection failed';
        }

        try {
            // Test storage
            if (!is_writable(storage_path('app'))) {
                $health['storage'] = 'warning';
                $health['errors'][] = 'Storage directory not writable';
            }
        } catch (\Exception $e) {
            $health['storage'] = 'error';
            $health['errors'][] = 'Storage check failed';
        }

        return $health;
    }

    /**
     * Récupérer les statistiques par période
     */
    public function getStatsByPeriod(string $period = 'month'): array
    {
        $dateFormat = match($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m',
        };

        return [
            'users' => User::select(DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"), DB::raw('COUNT(*) as count'))
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit(12)
                ->get(),
            'students' => Student::select(DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"), DB::raw('COUNT(*) as count'))
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit(12)
                ->get(),
            'payments' => Paiement::select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(montant) as total_amount')
            )
                ->where('statut', 'accepte')
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit(12)
                ->get(),
        ];
    }
}
