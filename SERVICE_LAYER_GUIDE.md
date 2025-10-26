# 🎯 Guide du Pattern Service Layer

## 📚 Principe Fondamental

Le **Service Layer Pattern** sépare clairement les responsabilités entre les différentes couches de l'application.

```
Request → Controller → Service → Model → Database
             ↓           ↓         ↓
        Validation   Logique   Persistence
        HTTP I/O     Métier    Données
```

---

## ✅ Ce Qui Doit Être Dans Un Controller

### Responsabilités AUTORISÉES
- ✅ Recevoir et valider les requêtes HTTP via FormRequest
- ✅ Extraire les données de la requête
- ✅ Appeler les méthodes du Service
- ✅ Formatter les réponses JSON
- ✅ Gérer les codes de statut HTTP
- ✅ Logger les erreurs HTTP

### Exemple Correct
```php
class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->create($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
            ], 201);
        } catch (Exception $e) {
            Log::error('Erreur création', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }
}
```

---

## ❌ Ce Qui NE Doit PAS Être Dans Un Controller

### Responsabilités INTERDITES
- ❌ Logique métier
- ❌ Transactions DB (DB::transaction)
- ❌ Appels directs aux Models (User::create, User::where)
- ❌ Manipulation de données complexe
- ❌ Upload de fichiers
- ❌ Envoi d'emails
- ❌ Calculs métier
- ❌ Gestion des relations entre modèles

### Exemple INCORRECT
```php
// ❌ MAUVAIS - Logique métier dans le controller
public function store(Request $request)
{
    DB::transaction(function () use ($request) {
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        
        if ($request->hasFile('avatar')) {
            // Upload logic...
        }
        
        $user->roles()->sync($request->role_ids);
    });
}
```

---

## ✅ Ce Qui Doit Être Dans Un Service

### Responsabilités du Service
- ✅ Toute la logique métier
- ✅ Transactions DB
- ✅ Appels aux Models
- ✅ Upload de fichiers via FileStorageService
- ✅ Gestion des relations
- ✅ Calculs et transformations
- ✅ Validation métier (au-delà de la validation HTTP)
- ✅ Logging des opérations métier
- ✅ Lancer des exceptions métier

### Exemple Correct
```php
class UserService
{
    public function __construct(
        protected FileStorageService $fileService
    ) {}

    public function create(array $data, $avatarFile = null): User
    {
        return DB::transaction(function () use ($data, $avatarFile) {
            // Hash password
            $data['password'] = Hash::make($data['password']);
            
            // Upload avatar
            if ($avatarFile) {
                $uploaded = $this->fileService->uploadFile(...);
                $data['avatar_id'] = $uploaded->id;
            }
            
            // Create user
            $user = User::create($data);
            
            // Attach roles
            if (!empty($data['role_ids'])) {
                $user->roles()->sync($data['role_ids']);
            }
            
            Log::info('Utilisateur créé', ['user_id' => $user->id]);
            
            return $user;
        });
    }
}
```

---

## 📋 Checklist de Conformité

### Pour Créer un Nouveau Module

- [ ] Créer le dossier `Services/`
- [ ] Créer un Service par entité principale
- [ ] Le Controller injecte le Service via le constructeur
- [ ] Le Controller n'a AUCUN appel direct aux Models
- [ ] Le Controller n'a AUCUNE transaction DB
- [ ] Toute la logique métier est dans le Service
- [ ] Le Service logue les opérations importantes
- [ ] Les méthodes du Service retournent des Models ou collections
- [ ] Le Controller utilise des Resources pour formatter les réponses

---

## 🔍 Exemples de Refactoring

### Avant (❌ Incorrect)
```php
class ProductController
{
    public function store(Request $request)
    {
        $product = Product::create($request->all());
        
        if ($request->hasFile('image')) {
            // Upload logic in controller ❌
            $path = $request->file('image')->store('products');
            $product->update(['image' => $path]);
        }
        
        return response()->json($product);
    }
}
```

### Après (✅ Correct)
```php
// Controller
class ProductController
{
    public function __construct(protected ProductService $productService) {}
    
    public function store(CreateProductRequest $request)
    {
        try {
            $product = $this->productService->create(
                $request->validated(),
                $request->file('image')
            );
            
            return response()->json([
                'success' => true,
                'data' => new ProductResource($product),
            ], 201);
        } catch (Exception $e) {
            Log::error('Erreur création produit', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }
}

// Service
class ProductService
{
    public function __construct(protected FileStorageService $fileService) {}
    
    public function create(array $data, $imageFile = null): Product
    {
        return DB::transaction(function () use ($data, $imageFile) {
            if ($imageFile) {
                $uploaded = $this->fileService->uploadFile(
                    uploadedFile: $imageFile,
                    userId: auth()->id(),
                    collection: 'products'
                );
                $data['image_id'] = $uploaded->id;
            }
            
            $product = Product::create($data);
            
            Log::info('Produit créé', ['product_id' => $product->id]);
            
            return $product;
        });
    }
}
```

---

## 🚀 Méthodes Standard d'un Service

Chaque Service devrait implémenter ces méthodes de base:

```php
class ExampleService
{
    // Liste paginée avec filtres
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Model::query();
        // Apply filters...
        return $query->paginate($perPage);
    }
    
    // Créer une entité
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $model = Model::create($data);
            Log::info('Entité créée', ['id' => $model->id]);
            return $model;
        });
    }
    
    // Récupérer par ID
    public function getById(int $id): ?Model
    {
        return Model::with(['relations'])->find($id);
    }
    
    // Mettre à jour
    public function update(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            $model->update($data);
            Log::info('Entité mise à jour', ['id' => $model->id]);
            return $model->fresh();
        });
    }
    
    // Supprimer
    public function delete(Model $model): bool
    {
        try {
            $model->delete();
            Log::info('Entité supprimée', ['id' => $model->id]);
            return true;
        } catch (Exception $e) {
            Log::error('Erreur suppression', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

---

## ⚠️ Erreurs Courantes à Éviter

### 1. Appel Direct aux Models dans le Controller
```php
// ❌ MAUVAIS
public function index()
{
    $users = User::where('active', true)->paginate(15);
    return response()->json($users);
}

// ✅ BON
public function index(Request $request)
{
    $users = $this->userService->getActive($request->input('per_page', 15));
    return response()->json(UserResource::collection($users));
}
```

### 2. Transactions DB dans le Controller
```php
// ❌ MAUVAIS
public function store(Request $request)
{
    DB::transaction(function () use ($request) {
        $user = User::create($request->all());
        $user->profile()->create($request->profile);
    });
}

// ✅ BON
public function store(CreateUserRequest $request)
{
    $user = $this->userService->createWithProfile($request->validated());
    return response()->json(new UserResource($user), 201);
}
```

### 3. Logique Métier dans le Controller
```php
// ❌ MAUVAIS
public function calculateTotal(Request $request)
{
    $items = $request->items;
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
        if ($item['discount']) {
            $total -= ($total * $item['discount'] / 100);
        }
    }
    return response()->json(['total' => $total]);
}

// ✅ BON
public function calculateTotal(Request $request)
{
    $total = $this->orderService->calculateTotal($request->items);
    return response()->json(['total' => $total]);
}
```

---

## 📊 Bénéfices du Pattern

### Maintenabilité
- Code organisé et prévisible
- Facile à trouver et modifier la logique
- Réduction des bugs

### Testabilité
- Services facilement testables unitairement
- Controllers testables avec mocks
- Tests isolés

### Réutilisabilité
- Services utilisables partout (Console, Jobs, Events)
- Pas de duplication de code
- DRY (Don't Repeat Yourself)

### Scalabilité
- Facile d'ajouter des fonctionnalités
- Pas de "god controllers"
- Architecture évolutive

---

## 🎓 Règle d'Or

> **Si vous hésitez:** Tout ce qui n'est pas strictement lié à HTTP (Request/Response) doit être dans le Service.

---

## 📝 Convention de Nommage

### Services
- Nom: `{Entity}Service`
- Exemple: `UserService`, `ProductService`, `OrderService`

### Méthodes
- Liste: `getAll()`
- Détails: `getById()`
- Créer: `create()`
- Modifier: `update()`
- Supprimer: `delete()`
- Autres: verbes d'action (`calculateTotal()`, `sendEmail()`, `generateReport()`)

---

## ✅ Checklist Avant Commit

- [ ] Aucun appel direct aux Models dans les Controllers
- [ ] Aucune transaction DB dans les Controllers
- [ ] Toute la logique métier est dans les Services
- [ ] Les Services sont injectés via le constructeur
- [ ] Les Resources sont utilisées pour les réponses
- [ ] Le logging est fait au niveau du Service
- [ ] Les exceptions métier sont lancées par les Services
- [ ] Les Controllers se limitent à HTTP I/O

---

## 🔗 Ressources

- [Laravel Service Container](https://laravel.com/docs/container)
- [Dependency Injection](https://laravel.com/docs/container#automatic-injection)
- [API Resources](https://laravel.com/docs/eloquent-resources)
