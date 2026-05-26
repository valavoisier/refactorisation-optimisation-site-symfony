# Ina Zaoui — Portfolio & Gestion d'invités

Application Symfony 7.4 LTS développée pour gérer un portfolio photographique, des albums et un système d’accès réservé aux invités.
Le projet propose un Front Office permettant la consultation des albums, ainsi qu’un Back Office dédié à la gestion des utilisateurs, des médias et des contenus.

## Table des matières

- [Pré-requis](#pré-requis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Tests](#tests)
- [Architecture & choix d'implémentation](#architecture--choix-dimplémentation)

---

## Pré-requis

| Outil | Version minimale |
|-------|-----------------|
| PHP | 8.2 |
| Composer | 2.7.1 |
| PostgreSQL | 16 |
| Symfony CLI (optionnel) | 5.16.1 |

> Le projet utilise **Symfony 7.4 LTS** et nécessite l'activation des extensions PHP suivantes : `pdo_pgsql`, `intl`, `gd` ou `imagick`, `mbstring`, `xml`, `ctype`, `iconv`.
Vous pouvez vérifier les extensions activées avec :

```bash
php -m

---

## Installation

### 1. Cloner le dépôt

```bash
git clone <url-du-depot>
cd 876-p15-inazaoui
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer l'environnement

Copier le fichier `.env` et l'adapter à votre environnement local :

```bash
cp .env .env.local
```

Éditer `.env.local` et renseigner votre chaîne de connexion PostgreSQL :

```dotenv
DATABASE_URL="postgresql://utilisateur:motdepasse@127.0.0.1:5432/ina_zaoui?serverVersion=16&charset=utf8"
```

> Générer un nouveau secret d'application si nécessaire :
> ```bash
> php bin/console secret:generate-keys
> ```

### 4. Créer la base de données et exécuter les migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Charger les données de démonstration (optionnel)

```bash
php bin/console doctrine:fixtures:load
```

Cela crée :
- **Administratrice** : `ina@zaoui.com` / `Admin1234!@#`
- **Invité actif** : `invite@example.com` / `Guest1234!@#`
- **Invité bloqué** : `blocked@example.com` (accès révoqué)
- 5 albums et 10 médias de démonstration

> **Mot de passe :** la contrainte (12 caractères minimum, robustesse suffisante) s'applique uniquement lors de la création ou modification d'un invité via le formulaire Back Office. Le compte administrateur d'Ina est géré directement par les fixtures ou la CLI Symfony et n'est pas soumis à cette validation de formulaire — un mot de passe défini ainsi reste fonctionnel même s'il ne respecterait pas ces règles.

### 6. Créer le répertoire d'uploads

```bash
mkdir -p public/uploads
```

### 7. Lancer le serveur de développement

```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

---

## Configuration

### Variables d'environnement clés

| Variable | Description | Exemple |
|----------|-------------|---------|
| `APP_ENV` | Environnement (`dev`, `test`, `prod`) | `dev` |
| `APP_SECRET` | Clé secrète de l'application | chaîne aléatoire 32 chars |
| `DATABASE_URL` | DSN PostgreSQL | voir ci-dessus |

### Répertoire d'uploads

Les fichiers uploadés sont stockés dans `public/uploads/`. Ce répertoire doit être accessible en écriture par le serveur web et est exclu du contrôle de version (`.gitignore`).

---

## Usage

### Front Office (public)

| URL | Description |
|-----|-------------|
| `/` | Page d'accueil |
| `/guests` | Liste des invités actifs et leurs galeries |
| `/guest/{id}` | Galerie d'un invité |
| `/portfolio/{id?}` | Portfolio d'Ina (albums & photos) |
| `/about` | Page à propos |

### Back Office (authentification requise)

Accès via `/login`.

| URL | Rôle requis | Description |
|-----|-------------|-------------|
| `/admin/media` | `ROLE_USER` | Gérer ses propres médias |
| `/admin/album` | `ROLE_ADMIN` | Gérer les albums |
| `/admin/guest` | `ROLE_ADMIN` | Gérer les invités |

> **Hiérarchie des rôles :** `ROLE_ADMIN` (Ina) hérite de `ROLE_USER` (invité connecté).

---

## Tests

### Configuration de l'environnement de test

Créer un fichier `.env.test.local` avec les identifiants de la base de données de test :

```dotenv
DATABASE_URL="postgresql://utilisateur:motdepasse@127.0.0.1:5432/ina_zaoui_test?serverVersion=16&charset=utf8"
```

### Préparer la base de données de test

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test
php bin/console doctrine:fixtures:load --env=test
```

### Lancer les tests

```bash
# Tous les tests
php bin/phpunit

# Avec rapport de couverture HTML (nécessite Xdebug ou PCOV)
php bin/phpunit --coverage-html coverage/

# Tests unitaires uniquement
php bin/phpunit tests/Unit/

# Tests fonctionnels uniquement
php bin/phpunit tests/Functional/
```

> Le rapport de couverture est généré dans le répertoire `coverage/`. La couverture actuelle est de **75.32 %** (232/308 lignes) — au-dessus de la cible de 70 %.

---

## Architecture & choix d'implémentation

### Structure des entités

```
User ──< Media    (OneToMany, cascade remove)
Album ──< Media   (OneToMany, cascade remove)
Media >── User    (ManyToOne, fetch EAGER)
Media >── Album   (ManyToOne, fetch EAGER)
```

La suppression d'un `User` ou d'un `Album` entraîne la suppression en cascade de tous les `Media` associés.

### Gestion des fichiers uploadés

Lors de la suppression d'un `Media`, le fichier physique doit également être supprimé du disque. Cette logique est centralisée dans **`MediaDeleteListener`** (Event Listener Doctrine sur l'événement `preRemove`). Ce choix garantit que la suppression du fichier est déclenchée quelle que soit la source (controller, suppression en cascade, CLI), sans dupliquer la logique.

### Gestion des comptes bloqués

Le blocage d'un invité est géré via le champ `blocked` (booléen) sur l'entité `User`. La vérification est effectuée par **`UserChecker`** (implémente `UserCheckerInterface`) dans la méthode `checkPreAuth()`, ce qui empêche la connexion avant même la vérification du mot de passe. Ce composant est déclaré explicitement dans `security.yaml` sous la clé `user_checker`.

### Validation des fichiers uploadés

Les contraintes de validation sur les uploads sont déclarées directement sur l'entité `Media` via les attributs Symfony Validator :
- **Type** : vérification par MIME type (`image/jpeg`, `image/png`, `image/webp`)
- **Poids** : maximum 2 Mo (`maxSize: '2M'`)

### Sécurité et contrôle d'accès

L'accès aux routes est contrôlé à deux niveaux :
1. **`access_control`** dans `security.yaml` : règles globales par préfixe d'URL
2. **Contrôleurs** : vérification fine (ex. un invité ne peut supprimer que ses propres médias)

### Migration Symfony 5.4 → 7.4

Le projet a été migré depuis Symfony 5.4 (fin 2021) vers **[Symfony 7.4 LTS](https://symfony.com/releases/7.4)**, la version courante à support long terme :

| | Date |
|---|---|
| Sortie | Novembre 2025 |
| Fin des corrections de bugs | Novembre 2028 |
| Fin des correctifs de sécurité | Novembre 2029 |

Ce choix offre :
- Une stabilité à long terme (LTS)
- La compatibilité avec PHP 8.2+
- Les dernières améliorations de performance et de sécurité du framework

---

## Données et images

La base de données se reconstruit entièrement via les migrations et les fixtures :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

> **Images :** les images issues de l'ancienne installation (dossier `public/uploads/` du précédent backup) sont toujours compatibles. Il suffit de les recopier dans `public/uploads/` après l'installation.