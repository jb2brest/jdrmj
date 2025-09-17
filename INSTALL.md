# Guide d'Installation - JDR 4 MJ

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur (ou MariaDB 10.2+)
- Serveur web (Apache/Nginx)
- Extension PHP PDO MySQL

## Installation

### 1. Configuration de la base de données

1. Créez une base de données MySQL :
```sql
CREATE DATABASE dnd_characters CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importez le schéma de la base de données :
```bash
mysql -u votre_utilisateur -p dnd_characters < database/schema.sql
```

### 2. Configuration de l'application

1. Modifiez le fichier `config/database.php` avec vos informations de connexion :
```php
define('DB_HOST', 'localhost');     // Votre hôte MySQL
define('DB_NAME', 'dnd_characters'); // Nom de votre base de données
define('DB_USER', 'votre_utilisateur'); // Votre nom d'utilisateur MySQL
define('DB_PASS', 'votre_mot_de_passe'); // Votre mot de passe MySQL
```

### 3. Configuration du serveur web

#### Apache
Assurez-vous que le module `mod_rewrite` est activé et ajoutez ce fichier `.htaccess` à la racine :

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Sécurité
<Files "config/*">
    Order allow,deny
    Deny from all
</Files>

<Files "database/*">
    Order allow,deny
    Deny from all
</Files>
```

#### Nginx
Ajoutez cette configuration à votre serveur :

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 4. Permissions des fichiers

Assurez-vous que les permissions sont correctes :
```bash
chmod 755 -R /chemin/vers/votre/application
chmod 644 config/database.php
```

### 5. Test de l'installation

1. Accédez à votre application via le navigateur
2. Créez un compte utilisateur
3. Testez la création d'un personnage

## Fonctionnalités

### ✅ Implémentées
- ✅ Système d'authentification (inscription/connexion)
- ✅ Création de personnages D&D 5e
- ✅ Gestion des races et classes
- ✅ Calcul automatique des statistiques
- ✅ Interface responsive avec Bootstrap
- ✅ Visualisation détaillée des personnages
- ✅ Suppression de personnages

### 🔄 En développement
- [ ] Édition de personnages
- [ ] Gestion des sorts
- [ ] Système de compétences
- [ ] Gestion de l'équipement
- [ ] Export PDF des feuilles de personnage
- [ ] Système de campagnes
- [ ] Gestion des jets de dés

## Structure des fichiers

```
/
├── config/
│   └── database.php          # Configuration de la base de données
├── database/
│   └── schema.sql            # Schéma de la base de données
├── includes/
│   └── functions.php         # Fonctions utilitaires
├── index.php                 # Page d'accueil
├── login.php                 # Page de connexion
├── register.php              # Page d'inscription
├── logout.php                # Déconnexion
├── characters.php            # Liste des personnages
├── create_character.php      # Création de personnage
├── view_character.php        # Visualisation de personnage
├── edit_character.php        # Édition de personnage (à venir)
└── README.md                 # Documentation
```

## Sécurité

- Mots de passe hashés avec `password_hash()`
- Protection contre les injections SQL avec PDO
- Nettoyage des entrées utilisateur
- Sessions sécurisées
- Validation des données

## Support

Pour toute question ou problème :
1. Vérifiez les logs d'erreur PHP
2. Vérifiez la connexion à la base de données
3. Assurez-vous que toutes les extensions PHP sont installées

## Licence

Ce projet est sous licence MIT. Vous êtes libre de l'utiliser, le modifier et le distribuer.























