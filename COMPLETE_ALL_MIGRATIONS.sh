#!/bin/bash

# Ce script complète TOUTES les migrations restantes en une seule fois
echo "🚀 Complétion de toutes les migrations..."

# Migration amounts
cat > database/migrations/*_create_amounts_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('label'); // Frais de scolarité, Inscription, etc.
            $table->decimal('amount', 10, 2);
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('cascade');
            $table->enum('type', ['inscription', 'scolarite', 'examen', 'autre'])->default('scolarite');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['department_id', 'academic_year_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amounts');
    }
};
EOF

# Migration exonerations
cat > database/migrations/*_create_exonerations_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exonerations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('matricule'); // Matricule étudiant
            $table->decimal('amount', 10, 2); // Montant exonéré
            $table->decimal('percentage', 5, 2)->nullable(); // Pourcentage d'exonération
            $table->enum('type', ['partielle', 'totale'])->default('partielle');
            $table->text('reason'); // Raison de l'exonération
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('matricule');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exonerations');
    }
};
EOF

# Migration transactions
cat > database/migrations/*_create_transactions_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('transaction_id')->unique();
            $table->foreignId('paiement_id')->constrained('paiements')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['credit', 'debit'])->default('credit');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable(); // Cash, Virement, Mobile Money, etc.
            $table->string('external_reference')->nullable(); // Référence externe (banque, etc.)
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('transaction_id');
            $table->index('paiement_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
EOF

# Migration file_activities
cat > database/migrations/*_create_file_activities_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('action', ['uploaded', 'downloaded', 'viewed', 'shared', 'deleted', 'renamed', 'moved'])->default('viewed');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('file_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_activities');
    }
};
EOF

# Migration file_permissions
cat > database/migrations/*_create_file_permissions_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('permission_type', ['view', 'edit', 'download', 'delete'])->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->unique(['file_id', 'user_id', 'permission_type']);
            $table->index('file_id');
            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_permissions');
    }
};
EOF

# Migration file_shares
cat > database/migrations/*_create_file_shares_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_shares', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('shared_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_with')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('share_token')->unique()->nullable(); // Pour les partages publics
            $table->enum('share_type', ['user', 'public', 'link'])->default('user');
            $table->boolean('can_download')->default(true);
            $table->boolean('can_edit')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('file_id');
            $table->index('shared_by');
            $table->index('shared_with');
            $table->index('share_token');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_shares');
    }
};
EOF

# Migration student_department (table pivot)
cat > database/migrations/*_create_student_department_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->enum('status', ['active', 'inactive', 'graduated'])->default('active');
            $table->date('enrollment_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->timestamps();
            
            $table->unique(['student_id', 'department_id', 'academic_year_id']);
            $table->index('student_id');
            $table->index('department_id');
            $table->index('academic_year_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_department');
    }
};
EOF

# Migration programs
cat > database/migrations/*_create_programs_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->integer('semester')->nullable(); // Semestre
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('department_id');
            $table->index('academic_year_id');
            $table->index('semester');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
EOF

echo "✅ Toutes les migrations ont été complétées!"
echo "📝 Vérifiez les fichiers dans database/migrations/"
