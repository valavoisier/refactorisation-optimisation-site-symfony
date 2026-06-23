# Ina Zaoui — Portfolio & Gestion d'invités

Application Symfony 7.4 LTS développée pour gérer un portfolio photographique, des albums et un système d’accès réservé aux invités.
Le projet propose un Front Office permettant la consultation des albums, ainsi qu’un Back Office dédié à la gestion des utilisateurs, des médias et des contenus.

## Table des matières

- [Pré-requis](#pré-requis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Tests](#tests)
- [Intégration Continue](#intégration-continue)
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
```

---

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/valavoisier/refactorisation-optimisation-site-symfony.git
cd refactorisation-optimisation-site-symfony
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

### 4. Créer la base de données et initialiser le schéma

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:migrations:sync-metadata-storage
php bin/console doctrine:migrations:version --add --all --no-interaction
```

> La migration existante est incrémentale (elle modifie un schéma préexistant). Sur une installation fraîche, `doctrine:schema:create` génère toutes les tables depuis les entités, puis les trois commandes suivantes marquent la migration comme déjà appliquée sans l'exécuter.

### ⚠️ Compatibilité des anciens fichiers SQL

Les fichiers SQL provenant d’anciennes versions du site (ou éventuellement conservés lors d’installations antérieures) **ne doivent pas être réutilisés**.  
Le schéma de base de données a été entièrement revu dans cette version Symfony 7.4, et ces fichiers ne sont désormais **plus compatibles** avec la structure actuelle (entités, relations, types et contraintes).

Toute tentative de réimporter ces anciens fichiers entraînerait des erreurs d’intégrité ou un comportement incohérent.  
La base doit donc être initialisée **uniquement** à partir :

- des **migrations**, qui définissent le schéma actuel,  
- et des **fixtures**, qui fournissent les données nécessaires au fonctionnement et aux tests.

Cette approche garantit un environnement propre, cohérent et conforme à l’architecture du projet.

### 5. Charger les données de démonstration (fixtures optionnelles)

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

## connexion
| Role | Email | Mot de passe |
|----------|-------------|---------|
| Administrateur (Ina) | ina@zaoui.com | Admin1234!@# |
| Invité actif | invite@example.com | Guest1234!@# |
| Invité bloqué | blocked@example.com | Guest1234!@# |


Les mots de passe initialement créés sont valables même s'ils ne respectent pas les règles de validation du formulaire. Cependant, lors de la modification de la création d'un invité via le Back Office ou modification, les nouveaux mots de passe doivent respecter les contraintes de robustesse (12 caractères minimum, majuscules, minuscules, chiffres et caractères spéciaux).

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
> Les administrateurs (`ROLE_ADMIN`) voient tous les médias, tandis que les invités (`ROLE_USER`) ne voient que les leurs.
---

## Tests

### Configuration de l'environnement de test

Créer un fichier `.env.test.local` avec les identifiants de la base de données de test :

```dotenv
DATABASE_URL="postgresql://utilisateur:motdepasse@127.0.0.1:5432/ina_zaoui?serverVersion=16&charset=utf8"
```

> Doctrine ajoute automatiquement le suffixe `_test` en environnement de test (`dbname_suffix` dans `doctrine.yaml`). La base réellement utilisée sera `ina_zaoui_test`.

### Préparer la base de données de test (fixtures nécessaires)

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:migrations:sync-metadata-storage --env=test
php bin/console doctrine:migrations:version --add --all --no-interaction --env=test
php bin/console doctrine:fixtures:load --env=test
```
! L’environnement de test doit être isolé afin que les tests n’affectent jamais la base de données réelle.

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

## Intégration continue

Ce projet utilise **GitHub Actions** pour automatiser :

- les tests
- l’analyse statique
- la vérification du style de code

Le workflow est défini dans : .github/workflows/ci.yml
Il s’exécute automatiquement à chaque **push** et **Pull Request**.

## Architecture & choix d'implémentation

### Structure des entités

```
User ──< Media    (OneToMany, cascade remove)
Album ──< Media   (OneToMany, cascade remove)
Media >── User    (ManyToOne)
Media >── Album   (ManyToOne)
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

### User Provider — chargement depuis la base de données

La configuration `providers` dans `security.yaml` indique à Symfony comment charger un utilisateur lors de l'authentification :

```yaml
# config/packages/security.yaml
providers:
    app_user_provider:
        entity:
            class: App\Entity\User
            property: email   # identifiant de connexion = adresse e-mail
```

Lors du login, Symfony appelle automatiquement `UserRepository::loadUserByIdentifier($email)` (fourni par `EntityUserProvider`), charge l'entité `User` depuis PostgreSQL et vérifie ensuite le mot de passe hashé.

Le `UserRepository` implémente également `PasswordUpgraderInterface` : si l'algorithme de hashage configuré change, Symfony rehash silencieusement le mot de passe au prochain login sans action manuelle.

### Architecture des contrôleurs

```
src/Controller/
├── HomeController.php          ← Front Office (pages publiques)
└── Admin/
    ├── SecurityController.php  ← Login / Logout
    ├── AlbumController.php     ← CRUD albums (ROLE_ADMIN)
    ├── MediaController.php     ← CRUD médias (ROLE_USER + vérification propriétaire)
    └── GuestController.php     ← CRUD invités (ROLE_ADMIN)
```

| Contrôleur | Rôle requis | Responsabilité |
|---|---|---|
| `HomeController` | public | Accueil, liste invités, portfolio, à propos |
| `SecurityController` | — | Formulaire de connexion / déconnexion |
| `AlbumController` | `ROLE_ADMIN` | Créer, modifier, supprimer les albums |
| `MediaController` | `ROLE_USER` | Ajouter / supprimer ses propres médias ; Ina peut tout gérer |
| `GuestController` | `ROLE_ADMIN` | Lister, ajouter, bloquer/débloquer, supprimer les invités |

La protection d'accès est appliquée à **deux niveaux** : globalement dans `access_control` (security.yaml) pour tous les préfixes `/admin/*`, et localement via l'attribut `#[IsGranted]` sur chaque action pour les vérifications fines (ex. un invité ne peut supprimer que ses propres médias).

### Structure des templates

```
templates/
├── base.html.twig          ← Layout racine (balises HTML, assets communs)
├── front.html.twig         ← Layout Front Office (extends base)
├── admin.html.twig         ← Layout Back Office (extends base)
├── front/
│   ├── home.html.twig
│   ├── guests.html.twig    ← Liste des invités actifs
│   ├── guest.html.twig     ← Galerie d'un invité
│   ├── portfolio.html.twig
│   └── about.html.twig
└── admin/
    ├── security/           ← login.html.twig
    ├── album/              ← index, add, edit
    ├── media/              ← index, add
    └── guest/              ← index, add
```

Chaque page hérite d'un layout dédié (`front.html.twig` ou `admin.html.twig`) qui hérite lui-même de `base.html.twig`. Cela permet d'avoir deux chartes graphiques distinctes (site public vs espace admin) sans dupliquer la structure HTML de base.

### Optimisation N+1 — chargement des invités

La page publique `/guests` charge les invités actifs **et leurs médias** en une seule requête SQL grâce à un `LEFT JOIN FETCH` Doctrine dans `findActiveGuests()` :

```php
// UserRepository::findActiveGuests()
$admin = $this->findAdmin();

$qb = $this->createQueryBuilder('u')
    ->leftJoin('u.medias', 'm')
    ->addSelect('m')                        // hydrate les médias dans la même requête
    ->where('u.blocked = :blocked')
    ->setParameter('blocked', false)        // filtre les invités actifs
    ->orderBy('u.id', 'ASC');

if ($admin !== null) {
    $qb->andWhere('u != :admin')
        ->setParameter('admin', $admin);    // exclut Ina en SQL (WHERE u.id != ?)
}

return $qb->getQuery()->getResult();
```

Sans le `addSelect('m')`(hydrate les médias dans la même requête), Doctrine chargerait les médias de chaque invité dans des requêtes séparées (N+1). L’exclusion d’Ina empêche également Doctrine de charger ses médias et albums, ce qui évite d’hydrater des données inutiles.

Cette logique est aussi utilisée dans `findGuests()` (liste d'administration), qui charge tous les invités (actifs et bloqués) avec leurs médias en une seule requête, sans inclure Ina.

### Chargement et pagination des médias (Back Office)

La liste des médias dans l’administration utilise une méthode dédiée :

```php
findPaginatedWithRelations(User $user|null, int $page)
```
Cette méthode :
charge Media, User et Album dans une seule requête SQL ;
applique la pagination via LIMIT et OFFSET ;
filtre les médias lorsqu’un invité est connecté (un admin voit tout) ;
évite toute requête supplémentaire lors du rendu Twig.
Elle garantit une pagination correcte pour chaque utilisateur et un affichage performant dans le Back Office.

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
php bin/console doctrine:schema:create
php bin/console doctrine:migrations:sync-metadata-storage
php bin/console doctrine:migrations:version --add --all --no-interaction
php bin/console doctrine:fixtures:load
```

> **Images :** les images issues de l'ancienne installation (dossier `public/uploads/` du précédent backup) sont toujours compatibles. Il suffit de les recopier dans `public/uploads/` après l'installation.

---

## Optimisation des images — conversion JPEG → WebP

Le dépôt d'origine contenait plus de 5 000 images pour environ 1 Go. Afin de réduire le poids du dossier `public/uploads/`, une commande Symfony permet de convertir en lot tous les fichiers JPEG/JPG en WebP.

### Commande

```bash
php bin/console app:jpeg-to-webp
```

Symfony fournit un système de commandes accessible via bin/console.
Une commande est une classe PHP annotée avec #[AsCommand] et automatiquement enregistrée par le framework.
Elle peut être exécutée depuis le terminal et permet d’automatiser des tâches comme le traitement de fichiers, la maintenance, l’import/export ou la manipulation de données.
La logique de la commande est définie dans la méthode execute() (ou __invoke() dans les versions récentes).
Symfony met à disposition des outils pour gérer les arguments, options, services injectés, ainsi qu’un système de sortie formatée via SymfonyStyle.
Documentation complète : https://symfony.com/doc/current/console.html

### Ce que fait la commande

- Parcourt `public/uploads/` et détecte tous les fichiers `.jpg` / `.jpeg` (insensible à la casse).
- Convertit chaque fichier en `.webp` (qualité 80) via l'extension GD de PHP.
- Met à jour le champ `path` en base de données pour chaque `Media` concerné.
- Supprime le fichier JPEG d'origine après conversion réussie.
- Laisse intacts les fichiers `.png` et `.webp` déjà présents.


### Prérequis

L'extension PHP **GD** doit être activée (vérifier avec `php -m | grep gd`).

### Résultat attendu

Seuls les fichiers `.webp` subsistent dans `public/uploads/` ; les enregistrements en base pointent tous vers des chemins `.webp`.