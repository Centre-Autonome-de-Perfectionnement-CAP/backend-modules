# Changelog

Toutes les modifications notables de ce module seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Versioning Sémantique](https://semver.org/lang/fr/).

## [1.0.0] - 2025-10-25

### Ajouté
- Système complet de gestion de fichiers
- Gestion des fichiers publics et privés
- Système de permissions granulaires (read, write, delete, share, admin)
- Attribution de permissions par utilisateur ou par rôle
- Système de partage avec liens sécurisés
- Protection par mot de passe pour les partages
- Limitation du nombre de téléchargements
- Expiration automatique des partages et permissions
- Verrouillage de fichiers
- Historique complet des activités
- Organisation par collections
- Métadonnées personnalisables
- Support multi-modules (liaison avec d'autres modules)
- API REST complète
- Documentation complète
- Traits réutilisables (HasFiles, HasRolesAndPermissions)
- Policies Laravel pour les autorisations
- Form Requests pour la validation
- API Resources pour les réponses structurées
- Configuration flexible via .env
- Hash SHA-256 pour vérification d'intégrité
- Soft delete avec rétention configurable

### Sécurité
- Fichiers privés non accessibles via URL directe
- Vérification des permissions à chaque accès
- Logging de toutes les actions
- Validation stricte des uploads
- Protection CSRF
- Authentification requise pour les endpoints sensibles
