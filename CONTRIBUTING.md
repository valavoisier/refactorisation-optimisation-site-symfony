# Guide de contribution

Ce document établit les conventions et procédures à respecter pour assurer la continuité du projet lors d'une prise en main par un nouveau développeur, que ce soit pour la maintenance ou l'évolution du site.

## Table des matières

- [Signaler un problème](#signaler-un-problème)
- [Proposer une évolution](#proposer-une-évolution)
- [Conventions de nommage](#conventions-de-nommage)
- [Procédure de travail](#procédure-de-travail)
- [Validation avant merge](#validation-avant-merge)
- [Bonnes pratiques](#bonnes-pratiques)

---

## Signaler un problème

Créer une issue GitHub en précisant :

- **Comportement observé** : ce qui se passe
- **Comportement attendu** : ce qui devrait se passer
- **Étapes pour reproduire** : liste numérotée des actions
- **Environnement** : version de PHP, Symfony, navigateur le cas échéant

Vérifier au préalable qu'aucune issue existante ne couvre déjà le problème.

---

## Proposer une évolution

1. Ouvrir une issue avec le label `enhancement` **avant** d'écrire du code.
2. Décrire le besoin fonctionnel, le cas d'usage et la valeur ajoutée.
3. Documenter la décision prise (choix retenu et alternatives écartées) dans la description de la Pull Request pour faciliter la compréhension future.

---

## Conventions de nommage

### Branches

| Type | Format | Exemple |
|------|--------|---------|
| Nouvelle fonctionnalité | `feature/<description-courte>` | `feature/guest-block` |
| Correction de bug | `fix/<description-courte>` | `fix/upload-mime-check` |
| Refactoring | `refactor/<description-courte>` | `refactor/media-listener` |
| Documentation | `docs/<description-courte>` | `docs/readme-update` |
| Intégration continue | `ci/<description-courte>` | `ci/github-actions` |

- Utiliser le **kebab-case** (minuscules, tirets)
- Toujours partir d'une branche `main` à jour

### Commits

Suivre la convention [Conventional Commits](https://www.conventionalcommits.org/) :

```
<type>(<scope>): <description courte>
```

| Type | Usage |
|------|-------|
| `feat` | Nouvelle fonctionnalité |
| `fix` | Correction de bug |
| `refactor` | Réécriture sans changement de comportement |
| `test` | Ajout ou modification de tests |
| `docs` | Documentation uniquement |
| `ci` | Configuration d'intégration continue |
| `chore` | Tâches de maintenance (dépendances, config…) |

**Exemples :**

```
feat(guest): add block/unblock toggle action
fix(media): delete physical file on cascade remove
test(controller): add functional tests for home routes
docs(readme): update installation instructions
```

- Message en **anglais** de préférence (ou en français), à l'impératif, sans majuscule initiale ni point final
- Limiter la première ligne à 72 caractères
- Référencer l'issue si applicable : `fix(auth): prevent blocked user login (closes #12)`

---

## Procédure de travail

```
main
 └── feature/ma-fonctionnalite   ← développement ici
```

1. Créer une branche depuis `main` selon les conventions ci-dessus
2. Développer et commiter régulièrement avec des messages explicites
3. S'assurer que les tests passent et que la couverture reste ≥ 70 %
4. Lancer l'analyse statique (voir [Bonnes pratiques](#bonnes-pratiques))
5. Ouvrir une Pull Request vers `main` avec :
   - Un titre clair préfixé du type (`feat:`, `fix:`…)
   - Une description expliquant le pourquoi et les choix techniques
   - La référence à l'issue associée (`Closes #XX`)
6. Vérifier que le pipeline CI passe au vert avant de merger
   La CI utilisée est GitHub Actions. Le workflow est défini dans 
   `.github/workflows/ci.yml` et s’exécute automatiquement à chaque Pull Request.


---

## Validation avant merge

Avant tout merge dans `main`, s'assurer que :

- [ ] Les tests unitaires et fonctionnels passent : `php bin/phpunit`
- [ ] La couverture de code est ≥ 70 % : `php bin/phpunit --coverage-html coverage/`
- [ ] L'analyse statique ne remonte pas d'erreur : `vendor/bin/phpstan analyse src/`
- [ ] Le pipeline CI est au vert
- [ ] Aucun secret ou donnée sensible n'est committé

---

## Bonnes pratiques

### Tests

- Tout nouveau comportement doit être couvert par un test.
- Les tests unitaires se trouvent dans `tests/Unit/`, les tests fonctionnels dans `tests/Functional/`.
- Utiliser les fixtures (`AppFixtures`) comme base de données de test — ne pas créer de données en dur dans les tests.

```bash
# Préparer l'environnement de test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test
php bin/console doctrine:fixtures:load --env=test

# Lancer les tests
php bin/phpunit
```

### Analyse statique

```bash
vendor/bin/phpstan analyse src/
```

Aucune erreur ne doit être introduite. Le niveau de rigueur est défini dans `phpstan.neon` (ou équivalent).

### Sécurité

- Ne jamais committer de secrets ou mots de passe — utiliser `.env.local`, ignoré par git.
- Valider toutes les entrées utilisateur via le composant Symfony Validator.
- Respecter les contrôles d'accès définis dans `security.yaml` et les contrôleurs.

### Style de code

Respecter les standards **PSR-12**. Si PHP-CS-Fixer est disponible :

```bash
vendor/bin/php-cs-fixer fix src/
```

