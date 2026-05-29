# Bibliothèque — API REST d'emprunt de livres

Test technique Symfony. API REST permettant à une bibliothèque municipale de gérer
son catalogue, ses adhérents et ses emprunts. Conçue pour être consommée par
plusieurs front-ends (web et mobile).

## Stack technique

- **Symfony 8** (PHP 8.5) — API construite avec **API Platform** (ressources exposées via des **DTO**)
- **PostgreSQL**
- **FrankenPHP** (serveur applicatif, basé sur le template officiel `dunglas/symfony-docker`)
- **JWT** (LexikJWTAuthenticationBundle) pour l'authentification stateless
- **Docker / Docker Compose** — démarrage en une commande

## Périmètre fonctionnel

Trois rôles : **Adhérent**, **Bibliothécaire**, **Administrateur**.

Règles métier :
- un adhérent peut emprunter **3 livres maximum** simultanément ;
- la durée d'un emprunt est de **21 jours** ;
- un adhérent ayant un **emprunt en retard** ne peut pas effectuer de nouvel emprunt ;
- le catalogue est consultable et **filtrable par titre**.

Données :
- **100 adhérents** et **100 livres** (issus de l'API **OpenLibrary**) sont injectés à l'initialisation ;
- le catalogue de livres est **mis à jour chaque nuit**.

## Démarrage

Prérequis : Docker et Docker Compose.

```bash
docker compose up -d --wait
```

L'API est disponible sur https://localhost (certificat auto-signé en développement).

## Développement

> Documentation détaillée (endpoints, comptes de démo, collection Postman) ajoutée au fil des étapes.
