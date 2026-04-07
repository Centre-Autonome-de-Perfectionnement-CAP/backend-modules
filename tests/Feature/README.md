# 🧪 Documentation des Tests Feature

## 📋 Vue d'ensemble

Les tests Feature testent l'intégration complète des différentes parties de l'application, notamment :
- Les routes API
- Les contrôleurs
- Les middlewares (authentification)
- Les validations
- Les interactions avec la base de données

## 🏗️ Structure des Tests

```
tests/Feature/
├── AuthTest.php                      # Tests d'authentification
├── PendingStudentTest.php            # Tests des inscriptions en attente
├── StudentTest.php                   # Tests de la gestion des étudiants
├── ClassGroupTest.php                # Tests des groupes de classe
├── CycleAndAcademicYearTest.php      # Tests des cycles et années académiques
└── README.md                         # Ce fichier
```

## 🚀 Exécution des Tests

### Exécuter tous les tests Feature
```bash
./vendor/bin/phpunit tests/Feature
```

### Exécuter un fichier de test spécifique
```bash
./vendor/bin/phpunit tests/Feature/AuthTest.php
```

### Exécuter un test spécifique
```bash
./vendor/bin/phpunit --filter test_user_can_login_with_valid_credentials
```

### Exécuter avec le rapport de couverture
```bash
./vendor/bin/phpunit --coverage-html coverage
```

### Via le script global
```bash
./run-tests.sh  # Exécute tous les tests (Unit + Feature)
```

## 🛠️ Méthodes Utilitaires du TestCase

Le fichier `tests/TestCase.php` fournit des méthodes utilitaires :

### `authenticatedUser(array $attributes = [])`
Crée un utilisateur et l'authentifie automatiquement pour le test.

```php
public function test_example(): void
{
    $user = $this->authenticatedUser(['email' => 'test@example.com']);
    // L'utilisateur est maintenant authentifié pour toutes les requêtes
}
```

### `createUser(array $attributes = [])`
Crée un utilisateur sans l'authentifier.

```php
$user = $this->createUser(['first_name' => 'John']);
```

### `authHeaders(User $user)`
Génère les headers d'authentification avec un token Sanctum.

```php
$user = $this->createUser();
$headers = $this->authHeaders($user);
$response = $this->getJson('/api/auth/me', $headers);
```

### `jsonHeaders()`
Retourne les headers JSON standards.

```php
$response = $this->postJson('/api/endpoint', $data, $this->jsonHeaders());
```

### `assertValidationErrors($response, array $fields)`
Vérifie que la réponse contient des erreurs de validation pour les champs spécifiés.

```php
$response = $this->postJson('/api/auth/login', []);
$this->assertValidationErrors($response, ['email', 'password']);
```

## 📝 Structure d'un Test Type

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test: Description claire de ce qui est testé
     */
    public function test_description_du_test(): void
    {
        // 1. ARRANGE: Préparer les données
        $this->authenticatedUser();
        $data = ['field' => 'value'];

        // 2. ACT: Exécuter l'action
        $response = $this->postJson('/api/endpoint', $data);

        // 3. ASSERT: Vérifier les résultats
        $response->assertstatus(200)
            ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('table', ['field' => 'value']);
    }
}
```

## ✅ Bonnes Pratiques

### 1. **Nommage des tests**
- Utiliser le pattern: `test_[action]_[expected_result]`
- Exemples:
  - `test_user_can_login_with_valid_credentials`
  - `test_unauthenticated_user_cannot_access_protected_route`
  - `test_validation_fails_when_email_is_missing`

### 2. **Organisation des tests**
- Un fichier de test par contrôleur ou fonctionnalité
- Regrouper les tests similaires
- Tester les cas positifs ET négatifs

### 3. **Isolation des tests**
- Chaque test doit être indépendant
- Utiliser `RefreshDatabase` pour nettoyer la DB entre les tests
- Ne pas dépendre de l'ordre d'exécution

### 4. **Assertions claires**
```php
// ✅ BON
$response->assertstatus(200);
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);

// ❌ ÉVITER
$this->assertTrue($response->status() == 200);
```

### 5. **Données de test**
- Utiliser les factories pour créer des données
- Créer uniquement les données nécessaires
- Utiliser des valeurs réalistes

## 🎯 Cas de Test à Couvrir

Pour chaque endpoint API, tester :

### Routes Authentifiées
- ✅ Accès réussi avec authentification
- ✅ Accès refusé sans authentification (401)
- ✅ Validation des données d'entrée
- ✅ Cas d'erreur (404, 422, etc.)

### Routes Publiques
- ✅ Accès sans authentification
- ✅ Validation des données
- ✅ Cas de succès et d'erreur

### Opérations CRUD
- ✅ Create: Création réussie + erreurs de validation
- ✅ Read: Liste + détail + pagination + filtres
- ✅ Update: Mise à jour réussie + validation
- ✅ Delete: Suppression réussie + 404 si inexistant

## 📊 Assertions Courantes

```php
// status HTTP
$response->assertstatus(200);
$response->assertOk();
$response->assertCreated();
$response->assertNoContent();
$response->assertUnauthorized();
$response->assertForbidden();
$response->assertNotFound();

// Structure JSON
$response->assertJsonStructure([
    'data' => ['*' => ['id', 'name']],
    'meta',
]);

// Contenu JSON
$response->assertJson(['success' => true]);
$response->assertJsonFragment(['email' => 'test@example.com']);

// Base de données
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'deleted@example.com']);
$this->assertDatabaseCount('users', 5);

// Validation
$response->assertJsonValidationErrors(['email', 'password']);
```

## 🔧 Configuration

### phpunit.xml
Les tests utilisent une base de données SQLite en mémoire :

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Cela garantit :
- Rapidité d'exécution
- Isolation complète
- Pas de modification de la DB de développement

## 📈 Couverture de Test Actuelle

| Module | Fichier de Test | status |
|--------|----------------|--------|
| Authentification | `AuthTest.php` | ✅ Complet |
| Inscriptions | `PendingStudentTest.php` | ✅ Complet |
| Étudiants | `StudentTest.php` | ✅ Complet |
| Groupes | `ClassGroupTest.php` | ✅ Complet |
| Cycles/Années | `CycleAndAcademicYearTest.php` | ✅ Complet |

## 🚀 Ajouter de Nouveaux Tests

### 1. Créer un nouveau fichier de test
```bash
php artisan make:test MonNouveauTest
```

### 2. Étendre le TestCase
```php
namespace Tests\Feature;
use Tests\TestCase;

class MonNouveauTest extends TestCase
{
    // Vos tests ici
}
```

### 3. Suivre la structure AAA (Arrange-Act-Assert)
```php
public function test_mon_test(): void
{
    // Arrange: Préparer
    $user = $this->authenticatedUser();
    
    // Act: Agir
    $response = $this->getJson('/api/endpoint');
    
    // Assert: Vérifier
    $response->assertstatus(200);
}
```

## 🐛 Debugging des Tests

### Afficher la réponse JSON
```php
dd($response->json());
```

### Afficher le contenu de la réponse
```php
dump($response->getContent());
```

### Afficher les erreurs de validation
```php
dd($response->json('errors'));
```

### Voir les requêtes SQL
```php
\DB::enableQueryLog();
// ... votre code
dd(\DB::getQueryLog());
```

## 📚 Ressources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [HTTP Tests Laravel](https://laravel.com/docs/http-tests)
- [Database Testing Laravel](https://laravel.com/docs/database-testing)

## 💡 Tips

1. **Exécuter les tests fréquemment** pendant le développement
2. **Écrire les tests en même temps** que le code (TDD)
3. **Maintenir une couverture élevée** (80%+ recommandé)
4. **Documenter les cas de test complexes** avec des commentaires
5. **Utiliser les factories** pour générer des données de test
6. **Nettoyer les tests obsolètes** régulièrement

---

**Dernière mise à jour**: Novembre 2024
