# Bibliothèque — API REST d'emprunt de livres

Test technique Symfony. API REST permettant à une bibliothèque municipale de gérer
son catalogue, ses adhérents et ses emprunts. Conçue pour être consommée par
plusieurs front-ends (web et mobile).

## Stack technique

- **Symfony 8** (PHP 8.5) — API construite avec **API Platform**, ressources exposées via des **DTO** (les entités Doctrine ne sont jamais sérialisées)
- **PostgreSQL 16**
- **FrankenPHP** (serveur applicatif, basé sur le template officiel `dunglas/symfony-docker`)
- **JWT** (LexikJWTAuthenticationBundle) pour l'authentification stateless
- **Docker / Docker Compose** — démarrage en une commande

## Périmètre fonctionnel

Trois rôles, hiérarchisés (`ROLE_ADMIN` > `ROLE_LIBRARIAN`, et `ROLE_MEMBER` distinct) :

- **Adhérent** : consulte le catalogue, emprunte, consulte ses emprunts ;
- **Bibliothécaire** : valide les retours, consulte le nombre de livres empruntés ;
- **Administrateur** : tous les droits du bibliothécaire (+ gestion des comptes, extensible).

Règles métier :
- un adhérent peut emprunter **3 livres maximum** simultanément ;
- la durée d'un emprunt est de **21 jours** ;
- un adhérent ayant un **emprunt en retard** ne peut pas effectuer de nouvel emprunt ;
- le catalogue est consultable et **filtrable par titre**.

Données :
- **100 adhérents** et **100 livres** (issus de l'API **OpenLibrary**) sont injectés à l'initialisation ;
- le catalogue de livres est **mis à jour chaque nuit** (3h) via le Symfony Scheduler.

## Démarrage

Prérequis : **Docker** et **Docker Compose**. Aucune installation de PHP/Composer requise.

```bash
docker compose up -d --wait
```

Au premier démarrage, l'application s'initialise automatiquement : génération des clés
JWT, exécution des migrations, chargement des fixtures (utilisateurs) et import des
100 livres depuis OpenLibrary. L'API est alors disponible sur **https://localhost**
(certificat TLS auto-signé en développement — voir la note ci-dessous).

> **Certificat auto-signé** : le navigateur affiche un avertissement « non sécurisé »,
> ce qui est normal en local. Pour les appels API (Postman, `curl -k`), utilisez
> l'option « ignorer la vérification SSL ».

Arrêt :

```bash
docker compose down            # arrêt
docker compose down -v         # arrêt + suppression des données (réinitialisation)
```

## Comptes de démonstration

Mot de passe commun : **`password`**

| Email | Rôle |
|---|---|
| `admin@biblio.test` | Administrateur |
| `librarian@biblio.test` | Bibliothécaire |
| `member1@biblio.test` … `member100@biblio.test` | Adhérents |

## Authentification

L'API est stateless : on récupère un JWT via `POST /api/login_check`, puis on
l'envoie dans l'en-tête `Authorization: Bearer <token>`.

```bash
curl -k -X POST https://localhost/api/login_check \
  -H 'Content-Type: application/json' \
  -d '{"email":"member1@biblio.test","password":"password"}'
```

## Endpoints

| Méthode | Route | Accès | Description |
|---|---|---|---|
| `POST` | `/api/login_check` | public | Authentification, renvoie un JWT |
| `GET` | `/api/books` | authentifié | Catalogue paginé |
| `GET` | `/api/books?title=…` | authentifié | Filtre par titre |
| `GET` | `/api/books/{id}` | authentifié | Détail d'un livre |
| `POST` | `/api/loans` | `ROLE_MEMBER` | Emprunter un livre (`{ "bookId": 1 }`) |
| `GET` | `/api/loans/me` | `ROLE_MEMBER` | Mes emprunts |
| `GET` | `/api/loans/{id}` | propriétaire ou staff | Détail d'un emprunt |
| `POST` | `/api/loans/{id}/return` | propriétaire (adhérent) | **Rendre** un livre (étape 1) |
| `POST` | `/api/loans/{id}/validate-return` | `ROLE_LIBRARIAN` | **Valider** le retour (étape 2) |
| `GET` | `/api/loans/borrowed-count` | `ROLE_LIBRARIAN` | Nombre de livres actuellement empruntés |

Le retour se fait en **deux temps** : l'adhérent *rend* son livre (statut
`return_requested`), puis le bibliothécaire *valide* le retour (statut `returned`),
ce qui remet le livre à disposition.

### Gestion (CRUD)

| Méthode | Route | Accès | Description |
|---|---|---|---|
| `POST` / `PUT` / `DELETE` | `/api/books` · `/api/books/{id}` | `ROLE_LIBRARIAN` | Gérer le catalogue |
| `GET` / `POST` | `/api/members` | `ROLE_LIBRARIAN` | Lister / créer des adhérents |
| `GET` / `PUT` / `DELETE` | `/api/members/{id}` | `ROLE_LIBRARIAN` | Consulter / modifier / supprimer un adhérent |
| `GET` / `POST` | `/api/librarians` | `ROLE_ADMIN` | Lister / créer des comptes bibliothécaires |
| `GET` / `PUT` / `DELETE` | `/api/librarians/{id}` | `ROLE_ADMIN` | Consulter / modifier / supprimer un bibliothécaire |

La modification (`PUT`) d'un compte applique uniquement les champs fournis ; l'email
n'est pas modifiable et un nouveau mot de passe n'est pris en compte que s'il est envoyé.

Documentation interactive (Swagger UI) : **https://localhost/api**

Codes de réponse notables : `201` (emprunt créé), `200` (retour validé),
`409` (règle métier violée : indisponible, retard, limite atteinte),
`422` (données invalides), `401`/`403` (authentification / autorisation).

## Collection Postman

Le dossier `postman/` contient :
- `Bibliotheque.postman_collection.json` — tous les endpoints, regroupés par
  domaine. Le dossier **Auth** propose trois connexions (**admin**, **adhérent**,
  **bibliothécaire**) ; chacune enregistre automatiquement le JWT dans la variable
  `jwt`, réutilisée par les autres requêtes.
- `Bibliotheque.postman_environment.json` — environnement « Bibliothèque (local) »
  définissant `baseUrl` (`https://localhost`).

Importer les deux fichiers, sélectionner l'environnement, puis lancer un *Login*.
(Pensez à désactiver la vérification SSL dans Postman pour le certificat auto-signé.)

## Tests et qualité

Outils exécutés dans le conteneur `php` :

```bash
docker compose exec php composer test     # PHPUnit (tests unitaires + fonctionnels)
docker compose exec php composer stan     # PHPStan (niveau max)
docker compose exec php composer cs        # PHP-CS-Fixer (vérification)
docker compose exec php composer cs:fix    # PHP-CS-Fixer (correction)
```

- **Tests unitaires** : règles métier d'emprunt (`LoanManager`) avec horloge mockée, mapping OpenLibrary.
- **Tests fonctionnels** : endpoints API (auth, catalogue, emprunt, autorisations) isolés par transaction (DAMA DoctrineTestBundle).
- **CI** : GitHub Actions exécute CS-Fixer, PHPStan et PHPUnit (`.github/workflows/ci.yml`).

## Architecture

```
src/
├── ApiResource/   # DTO exposés par API Platform (Book, Loan, entrées/sorties)
├── Command/       # app:books:sync, app:demo:init
├── DataFixtures/  # utilisateurs de démonstration
├── Domain/        # logique métier (LoanManager), exceptions, client OpenLibrary
├── Entity/        # entités Doctrine (non exposées directement)
├── Enum/          # UserRole, LoanStatus
├── Repository/    # requêtes (emprunts actifs, retard, disponibilité…)
├── Scheduler/     # planification de la synchronisation nocturne
├── Security/      # Voter (consultation des emprunts)
└── State/         # providers / processors API Platform (mapping entité ↔ DTO)
```

Choix structurants :
- **DTO + State Providers/Processors** : découplage total entre le modèle de
  persistance et le contrat d'API ; les entités ne sont jamais sérialisées.
- **Logique métier dans `LoanManager`** (service de domaine), avec une **horloge
  injectée** (`Psr\Clock`) pour des règles testables ; exceptions de domaine
  traduites en `409 Conflict`.
- **Autorisation** par hiérarchie de rôles + **Voter** pour les règles fines
  (un adhérent ne consulte que ses propres emprunts).
- **Synchronisation OpenLibrary** isolée dans une commande idempotente
  (`app:books:sync`, upsert par clé OpenLibrary), réutilisée pour l'initialisation
  et la mise à jour nocturne (Scheduler + worker dédié).

> **Modèle de disponibilité** : un livre est considéré à exemplaire unique ; il est
> indisponible tant qu'un emprunt actif le concerne.
