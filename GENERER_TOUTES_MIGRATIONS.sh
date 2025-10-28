#!/bin/bash

echo "🚀 GÉNÉRATION DE TOUTES LES 34 MIGRATIONS..."
echo ""

# Niveau 1 - Tables de base (6)
echo "📦 Niveau 1 - Tables de base..."
php artisan make:migration create_users_table --create=users
php artisan make:migration create_roles_table --create=roles
php artisan make:migration create_permissions_table --create=permissions
php artisan make:migration create_cycles_table --create=cycles
php artisan make:migration create_entry_diplomas_table --create=entry_diplomas
php artisan make:migration create_academic_years_table --create=academic_years

# Niveau 2 - Dépendances simples (7)
echo "📦 Niveau 2 - Dépendances simples..."
php artisan make:migration create_departments_table --create=departments
php artisan make:migration create_students_table --create=students
php artisan make:migration create_professors_table --create=professors
php artisan make:migration create_grades_table --create=grades
php artisan make:migration create_files_table --create=files
php artisan make:migration create_permission_role_table --create=permission_role
php artisan make:migration create_role_user_table --create=role_user

# Niveau 3 - Tables avec relations (5)
echo "📦 Niveau 3 - Tables avec relations..."
php artisan make:migration create_personal_information_table --create=personal_information
php artisan make:migration create_pending_students_table --create=pending_students
php artisan make:migration create_submission_periods_table --create=submission_periods
php artisan make:migration create_reclamation_periods_table --create=reclamation_periods
php artisan make:migration create_programs_table --create=programs

# Niveau 4 - Tables avancées (8)
echo "📦 Niveau 4 - Tables avancées..."
php artisan make:migration create_amounts_table --create=amounts
php artisan make:migration create_paiements_table --create=paiements
php artisan make:migration create_exonerations_table --create=exonerations
php artisan make:migration create_transactions_table --create=transactions
php artisan make:migration create_academic_paths_table --create=academic_paths
php artisan make:migration create_class_groups_table --create=class_groups
php artisan make:migration create_student_groups_table --create=student_groups
php artisan make:migration create_student_pending_student_table --create=student_pending_student

# Niveau 5 - Tables cours (5)
echo "📦 Niveau 5 - Tables cours..."
php artisan make:migration create_teaching_units_table --create=teaching_units
php artisan make:migration create_course_elements_table --create=course_elements
php artisan make:migration create_course_element_professor_table --create=course_element_professor
php artisan make:migration create_course_element_resources_table --create=course_element_resources

# Niveau 6 - Tables stockage (3)
echo "📦 Niveau 6 - Tables stockage..."
php artisan make:migration create_file_activities_table --create=file_activities
php artisan make:migration create_file_permissions_table --create=file_permissions
php artisan make:migration create_file_shares_table --create=file_shares

# Niveau 7 - Tables système (2)
echo "📦 Niveau 7 - Tables système..."
php artisan make:migration create_sessions_table --create=sessions
php artisan make:migration create_cache_table --create=cache

echo ""
echo "✅ Toutes les 34 migrations ont été créées!"
echo "📝 Maintenant, remplissez chaque migration avec les colonnes appropriées"
echo ""
echo "📊 Résumé:"
ls -1 database/migrations/*.php 2>/dev/null | wc -l
echo "migrations créées"
