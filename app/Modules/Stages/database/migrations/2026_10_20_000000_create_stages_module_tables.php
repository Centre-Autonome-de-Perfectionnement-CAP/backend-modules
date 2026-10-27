<?php

use App\Modules\Stages\Support\StudyLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Module Stages : toutes ses tables, en une seule migration.
 *
 * Chaque étape ne s'exécute que si elle manque (table absente, colonne
 * absente) : une base neuve reçoit tout, une base qui avait déjà passé les
 * anciennes migrations du module (serveur de test) n'est pas modifiée.
 * Seules des tables `stage_*` sont créées ; hors du module, seul le rôle
 * « superviseur » est ajouté (s'il n'existe pas).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Demandes, échanges sur les conditions, notes de service, historique, réglages ───
        $this->create('stage_requests', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 20)->unique();
            $t->string('status', 30)->index();

            // ── Demandeur (formulaire public) ─────────────────────────────
            $t->string('civilite', 5);
            $t->string('nom', 80);
            $t->string('prenoms', 100);
            $t->date('date_naissance');
            $t->string('lieu_naissance', 100);
            $t->string('nationalite', 60);
            $t->string('etablissement_origine', 150);
            $t->string('filiere_niveau', 150);
            $t->date('date_debut_souhaitee')->nullable();
            $t->string('theme_souhaite', 200)->nullable();
            $t->string('duree_souhaitee', 60);
            $t->string('email', 254)->index();
            $t->string('whatsapp', 30)->index();
            $t->json('files');
            $t->timestamp('consent_at');

            // ── Conditions arrêtées (dernière version en vigueur) ─────────
            $t->string('service', 150)->nullable();
            $t->string('lieu', 150)->nullable();
            $t->date('date_debut')->nullable();
            $t->date('date_fin')->nullable();
            $t->json('horaires')->nullable();

            // ── Décision ──────────────────────────────────────────────────
            $t->timestamp('proposal_sent_at')->nullable();
            $t->timestamp('proposal_reminded_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('rejected_at')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->timestamp('expired_at')->nullable();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();

            $t->timestamps();
            $t->index(['status', 'proposal_sent_at']);
        });

        // Échanges sur les conditions du stage (propositions du responsable,
        // contre-propositions et réponses du demandeur). Jamais modifiés.
        $this->create('stage_request_proposals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('author', 12);          // staff | demandeur
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('kind', 12);            // proposal | counter | accept
            $t->string('service', 150)->nullable();
            $t->string('lieu', 150)->nullable();
            $t->date('date_debut')->nullable();
            $t->date('date_fin')->nullable();
            $t->json('horaires')->nullable();
            $t->text('message')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['stage_request_id', 'id']);
        });

        // Notes de service émises (initiale puis rectificatives). Le contenu
        // est figé (snapshot) : le PDF pourra toujours être régénéré à
        // l'identique.
        $this->create('stage_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('number', 40)->unique();
            $t->string('kind', 14);            // initiale | rectificative
            $t->unsignedSmallInteger('version');
            $t->json('snapshot');
            $t->text('motif')->nullable();
            $t->string('pdf_path')->nullable();
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('issued_at')->useCurrent();
            $t->index(['stage_request_id', 'version']);
        });

        // Historique non modifiable (traçabilité).
        $this->create('stage_request_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('action', 40);
            $t->string('from_status', 30)->nullable();
            $t->string('to_status', 30)->nullable();
            $t->string('actor', 12);           // staff | demandeur | systeme
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('comment')->nullable();
            $t->json('meta')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['stage_request_id', 'id']);
        });

        $this->create('stage_settings', function (Blueprint $t) {
            $t->string('key', 60)->primary();
            $t->json('value');
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('updated_at')->nullable();
        });

        // ── Supervision : superviseurs, binômes, groupes WhatsApp ─────────────────
        if (!DB::table('roles')->where('slug', 'superviseur')->exists()) {
            DB::table('roles')->insert([
                'uuid' => (string) Str::uuid(), 'name' => 'Superviseur de stage', 'slug' => 'superviseur',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->create('stage_supervisor_invitations', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 30)->index();
            $t->string('name', 120)->nullable();
            $t->string('token_hash', 64)->unique();
            $t->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('expires_at');
            $t->timestamp('last_sent_at')->nullable();
            $t->unsignedSmallInteger('send_count')->default(0);
            $t->timestamp('accepted_at')->nullable();
            $t->foreignId('accepted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
        });

        $this->create('stage_teams', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        $this->create('stage_whatsapp_groups', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('year')->index();
            $t->string('jid', 60)->unique();
            $t->string('name', 100);
            $t->string('origin', 10);          // created | linked
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('dissolved_at')->nullable();
            $t->foreignId('dissolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        $this->extend('stage_requests', 'team_id', function (Blueprint $t) {
            $t->foreignId('team_id')->nullable()->after('horaires')->constrained('stage_teams')->nullOnDelete();
            $t->foreignId('wa_group_id')->nullable()->after('team_id')->constrained('stage_whatsapp_groups')->nullOnDelete();
            $t->timestamp('wa_added_at')->nullable()->after('wa_group_id');
            $t->timestamp('wa_invite_sent_at')->nullable()->after('wa_added_at');
            $t->timestamp('wa_removed_at')->nullable()->after('wa_invite_sent_at');
        });

        $this->create('stage_supervisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['stage_request_id', 'user_id']);
        });

        // ── Espace stagiaire : compte, sessions, téléphone, présences, demandes ───
        $this->extend('stage_requests', 'date_debut_reelle', function (Blueprint $t) {
            $t->date('date_debut_reelle')->nullable()->after('date_fin');
            $t->string('activation_token_hash', 64)->nullable()->unique()->after('wa_removed_at');
            $t->timestamp('activation_sent_at')->nullable()->after('activation_token_hash');
            $t->unsignedTinyInteger('activation_reminders')->default(0)->after('activation_sent_at');
            $t->timestamp('activated_at')->nullable()->after('activation_reminders');
            $t->timestamp('withdrawn_at')->nullable()->after('activated_at');
        });

        $this->create('stage_intern_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->unique()->constrained('stage_requests')->cascadeOnDelete();
            $t->string('password');
            $t->unsignedSmallInteger('reglement_version')->nullable();
            $t->timestamp('reglement_accepted_at')->nullable();
            $t->timestamp('last_login_at')->nullable();
            $t->timestamps();
        });

        $this->create('stage_intern_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained('stage_intern_accounts')->cascadeOnDelete();
            $t->string('token_hash', 64)->unique();
            $t->string('user_agent', 255)->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->index();
            $t->timestamp('created_at')->useCurrent();
        });

        $this->create('stage_webauthn_credentials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained('stage_intern_accounts')->cascadeOnDelete();
            $t->string('credential_id', 512)->unique();
            $t->text('public_key');
            $t->unsignedBigInteger('sign_count')->default(0);
            $t->string('device_label', 120)->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        $this->create('stage_attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->date('day');
            $t->string('kind', 12);                 // arrivee | pause_debut | pause_fin | sortie
            $t->timestamp('at');
            $t->unsignedSmallInteger('late_minutes')->default(0);
            $t->boolean('early')->default(false);
            $t->unsignedInteger('distance_m')->nullable();
            $t->unsignedSmallInteger('accuracy_m')->nullable();
            $t->boolean('position_checked')->default(false);
            $t->string('source', 14)->default('pointage'); // pointage | activation
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['stage_request_id', 'day', 'kind']);
        });

        // Tentatives refusées (diagnostic) : motif uniquement, aucune position.
        $this->create('stage_attendance_failures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('reason', 40);
            $t->timestamp('created_at')->useCurrent();
            $t->index(['stage_request_id', 'created_at']);
        });

        $this->create('stage_intern_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('kind', 16);                 // permission | prolongation | raccourcissement
            $t->string('type', 24)->nullable();     // pour les permissions
            $t->date('date_from')->nullable();
            $t->date('date_to')->nullable();
            $t->string('time_from', 5)->nullable();
            $t->string('time_to', 5)->nullable();
            $t->date('new_date_fin')->nullable();
            $t->text('motif');
            $t->string('file_path')->nullable();
            $t->string('status', 12)->default('pending')->index();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->text('decision_comment')->nullable();
            $t->timestamps();
            $t->index(['stage_request_id', 'status']);
        });

        // ── Rapports ──────────────────────────────────────────────────────────────
        $this->create('stage_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('kind', 10);                 // periodique | final
            $t->string('period_key', 20);           // J2026-11-03 | S2026-W45 | M2026-11 | FINAL
            $t->string('frequency', 12)->nullable();
            $t->date('period_start')->nullable();
            $t->date('period_end')->nullable();
            $t->unsignedSmallInteger('current_version')->default(0);
            $t->string('status', 20)->default('submitted')->index();
            $t->boolean('late')->default(false);
            $t->timestamp('submitted_at')->nullable();
            $t->timestamps();
            $t->unique(['stage_request_id', 'period_key']);
        });

        $this->create('stage_report_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('stage_reports')->cascadeOnDelete();
            $t->unsignedSmallInteger('version');
            $t->string('file_path');
            $t->string('original_name', 200);
            $t->unsignedInteger('size');
            $t->text('note')->nullable();
            $t->boolean('late')->default(false);
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['report_id', 'version']);
        });

        $this->create('stage_report_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('stage_reports')->cascadeOnDelete();
            $t->unsignedSmallInteger('version')->nullable();
            $t->string('author', 10);               // staff | stagiaire
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('body');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['report_id', 'id']);
        });

        $this->create('stage_report_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('stage_reports')->cascadeOnDelete();
            $t->unsignedSmallInteger('version');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('role', 12);                 // superviseur | responsable
            $t->string('decision', 10);             // approved | rejected
            $t->text('comment')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['report_id', 'id']);
        });

        $this->create('stage_report_notices', function (Blueprint $t) {
            // Rappels déjà envoyés (ouverture / veille de clôture), pour ne jamais les répéter.
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('period_key', 20);
            $t->string('kind', 12);                 // open | closing
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['stage_request_id', 'period_key', 'kind']);
        });

        // ── Attestations ──────────────────────────────────────────────────────────
        $this->create('stage_attestations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('kind', 16);                    // normale | exceptionnelle | demande
            $t->string('status', 16)->index();         // a_valider | a_imprimer | imprimee | remise | annulee
            $t->string('numero', 60)->nullable()->index();
            $t->json('overrides')->nullable();         // corrections du responsable avant validation
            $t->json('content')->nullable();           // contenu figé à la validation
            $t->json('eligibility')->nullable();       // conditions au moment de la validation
            $t->string('motif_type', 20)->nullable();  // demande du stagiaire : non_recue | perte | duplicata | autre
            $t->text('motif')->nullable();             // exceptionnelle / demande
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('validated_at')->nullable();
            $t->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('printed_at')->nullable();
            $t->date('delivered_on')->nullable();
            $t->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('delivered_at')->nullable();
            $t->string('scan_path')->nullable();
            $t->string('scan_name', 200)->nullable();
            $t->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('cancelled_at')->nullable();
            $t->text('cancel_reason')->nullable();
            $t->timestamp('reminded_at')->nullable();
            $t->timestamps();
            $t->index(['stage_request_id', 'status']);
        });

        $this->extend('stage_requests', 'attestation_prepared_at', function (Blueprint $t) {
            // Préparation automatique de l'attestation (J-7) déjà faite.
            $t->timestamp('attestation_prepared_at')->nullable()->after('withdrawn_at');
        });

        // ── Attestations : date portée sur le document ────────────────────────────
        $this->extend('stage_attestations', 'document_date', function (Blueprint $t) {
            $t->date('document_date')->nullable()->after('printed_at');
        });

        // ── Rapports : corrections après soutenance ───────────────────────────────
        $added = $this->extend('stage_reports', 'official_version', function (Blueprint $t) {
            $t->unsignedSmallInteger('official_version')->nullable()->after('current_version');
            $t->string('correction_status', 12)->nullable()->after('status'); // pending | approved | rejected
        });
        if ($added) {
            // Rapports déjà validés : la version validée fait foi.
            DB::table('stage_reports')->whereIn('status', ['approved', 'supervisor_approved'])
                ->update(['official_version' => DB::raw('current_version')]);
        }
        $this->extend('stage_report_versions', 'post_defense', function (Blueprint $t) {
            $t->boolean('post_defense')->default(false)->after('late');
        });

        // ── Stagiaires arrivés avant le système (invitations) ─────────────────────
        $this->create('stage_intern_invitations', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 30)->index();
            $t->string('token_hash', 64)->unique();
            $t->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('expires_at');
            $t->timestamp('last_sent_at')->nullable();
            $t->unsignedSmallInteger('send_count')->default(1);
            $t->timestamp('used_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->foreignId('stage_request_id')->nullable()->constrained('stage_requests')->nullOnDelete();
            $t->timestamps();
        });

        $this->extend('stage_requests', 'origin', function (Blueprint $t) {
            // demande (formulaire du site) | invitation (stagiaire arrivé avant le système)
            $t->string('origin', 12)->default('demande')->after('status');
            // Présences et rapports comptés à partir de cette date (entrée dans le système)
            $t->date('attendance_from')->nullable()->after('date_debut_reelle');
        });

        $this->extend('stage_notes', 'recorded_at', function (Blueprint $t) {
            // Date réelle d'enregistrement (issued_at = date portée sur le document)
            $t->timestamp('recorded_at')->nullable()->after('issued_at');
        });

        // ── Notes de service : ancien code de vérification retiré ─────────────────
        if (Schema::hasColumn('stage_notes', 'verify_code')) {
            Schema::table('stage_notes', function (Blueprint $t) {
                $t->dropUnique(['verify_code']);
                $t->dropColumn('verify_code');
            });
        }

        // ── Propositions de binôme ────────────────────────────────────────────────
        $this->create('stage_team_invites', function (Blueprint $t) {
            $t->id();
            $t->foreignId('from_stage_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->foreignId('to_stage_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('status', 12)->default('pending'); // pending | accepted | declined | cancelled
            $t->timestamp('expires_at');
            $t->timestamp('responded_at')->nullable();
            $t->timestamps();
            $t->index(['to_stage_id', 'status']);
            $t->index(['from_stage_id', 'status']);
        });

        // ── Binômes : origine ─────────────────────────────────────────────────────
        $this->extend('stage_teams', 'from_request', function (Blueprint $t) {
            $t->boolean('from_request')->default(false)->after('name');
        });

        // ── Téléphone de stage ────────────────────────────────────────────────────
        $this->extend('stage_webauthn_credentials', 'device_hash', function (Blueprint $t) {
            $t->string('device_hash', 64)->nullable()->index()->after('device_label');
        });
        $this->extend('stage_intern_sessions', 'device_hash', function (Blueprint $t) {
            $t->string('device_hash', 64)->nullable()->after('ip');
        });

        // ── Superviseurs dans le groupe WhatsApp ──────────────────────────────────
        $this->create('stage_whatsapp_group_staff', function (Blueprint $t) {
            $t->id();
            $t->foreignId('group_id')->constrained('stage_whatsapp_groups')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('added_at')->nullable();
            $t->timestamp('invite_sent_at')->nullable();
            $t->timestamp('removed_at')->nullable();
            $t->timestamps();
            $t->unique(['group_id', 'user_id']);
        });

        // ── Filière et niveau d'études séparés ────────────────────────────────────
        $added = $this->extend('stage_requests', 'filiere', function (Blueprint $t) {
            $t->string('filiere', 150)->nullable()->after('filiere_niveau');
            $t->string('cycle', 12)->nullable()->after('filiere');
            $t->unsignedTinyInteger('annee_etude')->nullable()->after('cycle');
        });

        if ($added) {
            // Dossiers déjà enregistrés : convertis au mieux, rien n'est perdu.
            DB::table('stage_requests')->orderBy('id')->select('id', 'filiere_niveau')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $r) {
                        DB::table('stage_requests')->where('id', $r->id)->update(StudyLevel::guess((string) $r->filiere_niveau));
                    }
                });
        }
    }

    public function down(): void
    {
        foreach ([
            'stage_whatsapp_group_staff', 'stage_team_invites', 'stage_intern_invitations', 'stage_attestations',
            'stage_report_notices', 'stage_report_reviews', 'stage_report_comments', 'stage_report_versions', 'stage_reports',
            'stage_intern_requests', 'stage_attendance_failures', 'stage_attendances', 'stage_webauthn_credentials',
            'stage_intern_sessions', 'stage_intern_accounts', 'stage_supervisions', 'stage_settings', 'stage_request_history',
            'stage_notes', 'stage_request_proposals', 'stage_requests', 'stage_whatsapp_groups', 'stage_teams',
            'stage_supervisor_invitations',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        // Le rôle « superviseur » est conservé (des comptes peuvent y être rattachés).
    }

    /** Crée la table si elle n'existe pas. */
    private function create(string $table, Closure $definition): void
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }

    /** Ajoute des colonnes si la première d'entre elles manque ; indique si c'est fait. */
    private function extend(string $table, string $firstColumn, Closure $definition): bool
    {
        if (Schema::hasColumn($table, $firstColumn)) {
            return false;
        }
        Schema::table($table, $definition);

        return true;
    }
};
