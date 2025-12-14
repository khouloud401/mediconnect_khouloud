# Guide d'Installation - MediConnect

## Installation Rapide

### 1. Prérequis

Assurez-vous d'avoir installé :
- PHP 8.1 ou supérieur
- MySQL 8.0 ou supérieur
- Composer
- Extensions PHP nécessaires

#### Vérifier PHP
```bash
php -v
```

#### Installer les extensions PHP manquantes (Ubuntu/Debian)
```bash
sudo apt-get install php8.1-cli php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip php8.1-intl php8.1-gd
```

### 2. Installation de MySQL

```bash
# Installer MySQL
sudo apt-get install mysql-server

# Démarrer MySQL
sudo service mysql start

# Créer l'utilisateur et la base de données
sudo mysql -e "CREATE DATABASE IF NOT EXISTS mediconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'mediconnect'@'localhost' IDENTIFIED BY 'mediconnect123';"
sudo mysql -e "GRANT ALL PRIVILEGES ON mediconnect.* TO 'mediconnect'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### 3. Installation du Projet

```bash
# Se placer dans le répertoire du projet
cd /chemin/vers/mediconnect_new

# Installer les dépendances
composer install

# Créer le schéma de base de données
php bin/console doctrine:schema:update --force

# Charger les données de test
php bin/console doctrine:fixtures:load --no-interaction

# Créer les répertoires d'upload
mkdir -p public/uploads/doctors public/uploads/prescriptions
chmod -R 777 public/uploads
```

### 4. Configuration

Le fichier `.env` est déjà configuré avec les paramètres par défaut :

```env
DATABASE_URL="mysql://mediconnect:mediconnect123@127.0.0.1:3306/mediconnect?serverVersion=8.0.32&charset=utf8mb4"
```

Si vous utilisez des identifiants différents, modifiez cette ligne dans le fichier `.env`.

### 5. Démarrage du Serveur

#### Option 1 : Serveur PHP intégré (développement)
```bash
php -S localhost:8000 -t public
```

#### Option 2 : Symfony CLI (recommandé)
```bash
# Installer Symfony CLI
curl -sS https://get.symfony.com/cli/installer | bash

# Démarrer le serveur
symfony server:start
```

### 6. Accès à l'Application

Ouvrez votre navigateur et accédez à :
```
http://localhost:8000
```

## Comptes de Test

### Administrateur
- **URL** : http://localhost:8000/login
- **Email** : admin@mediconnect.com
- **Mot de passe** : admin123

### Médecin
- **Email** : jean.dupont@mediconnect.com
- **Mot de passe** : doctor123

### Patient
- **Email** : thomas.leroy@email.com
- **Mot de passe** : patient123

## Résolution des Problèmes Courants

### Erreur de connexion à la base de données

**Problème** : `SQLSTATE[HY000] [1698] Access denied for user 'root'@'localhost'`

**Solution** :
```bash
# Créer un utilisateur MySQL avec mot de passe
sudo mysql -e "CREATE USER IF NOT EXISTS 'mediconnect'@'localhost' IDENTIFIED BY 'mediconnect123';"
sudo mysql -e "GRANT ALL PRIVILEGES ON mediconnect.* TO 'mediconnect'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### Erreur de permissions sur les fichiers

**Problème** : Erreur lors de l'upload de fichiers

**Solution** :
```bash
chmod -R 777 public/uploads
chmod -R 777 var/cache
chmod -R 777 var/log
```

### Erreur "Class not found"

**Problème** : Classes introuvables après installation

**Solution** :
```bash
composer dump-autoload
php bin/console cache:clear
```

### Port 8000 déjà utilisé

**Problème** : Le port 8000 est déjà occupé

**Solution** : Utiliser un autre port
```bash
php -S localhost:8080 -t public
```

## Commandes Utiles

### Vider le cache
```bash
php bin/console cache:clear
```

### Recréer la base de données
```bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load --no-interaction
```

### Voir les routes disponibles
```bash
php bin/console debug:router
```

### Créer un nouvel administrateur
```bash
php bin/console doctrine:fixtures:load --no-interaction
```

Ou manuellement via MySQL :
```sql
-- Remplacer 'votre_email' et le hash du mot de passe
INSERT INTO user (user_type, email, roles, password, nom, prenom, phone, created_at) 
VALUES ('admin', 'votre_email@example.com', '["ROLE_ADMIN"]', '$2y$13$hash...', 'Nom', 'Prenom', '0600000000', NOW());
```

## Configuration pour la Production

### 1. Modifier le fichier .env

```env
APP_ENV=prod
APP_DEBUG=0
```

### 2. Optimiser l'application

```bash
# Installer les dépendances de production
composer install --no-dev --optimize-autoloader

# Vider et réchauffer le cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### 3. Configurer Apache/Nginx

#### Apache (.htaccess déjà configuré)
```apache
<VirtualHost *:80>
    ServerName mediconnect.local
    DocumentRoot /chemin/vers/mediconnect_new/public
    
    <Directory /chemin/vers/mediconnect_new/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name mediconnect.local;
    root /chemin/vers/mediconnect_new/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

## Support

Pour plus d'informations, consultez :
- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Documentation Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html)
- README.md du projet
