# Projet de Lucas POYART

Ce projet Symfony utilise une base de données PostgreSQL, orchestrée via Docker Compose.

## Lancement du projet

Pour démarrer l'environnement de développement, exécutez :

```bash
docker compose up -d
```

Cela lancera à la fois la base de données PostgreSQL et Adminer pour la gestion des données.

## Lancer les migrations Doctrine

Après le démarrage des conteneurs, appliquez les migrations Doctrine pour créer la structure de la base de données :

```bash
php bin/console doctrine:migrations:migrate
```

## Accès à l’interface d’administration (Adminer)

Vous pouvez consulter et modifier les données via Adminer à l’adresse :  
[http://localhost:8080](http://localhost:8080)

**Paramètres de connexion :**

-   **Système** : PostgreSQL
-   **Serveur** : database
-   **Utilisateur** : db_user
-   **Mot de passe** : db_password

## Informations complémentaires

-   Le code source de l’application se trouve dans ce dépôt.
-   Les identifiants et paramètres de connexion sont configurés dans le fichier `.env` du projet Symfony.
-   Pour arrêter l’environnement :
    ```bash
    docker compose down
    ```

N’hésitez pas à consulter la documentation du projet pour plus de détails sur l’installation,
