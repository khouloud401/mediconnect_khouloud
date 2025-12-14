# MediConnect - Plateforme de Gestion de Rendez-vous Médicaux

## Description

MediConnect est une application web sécurisée développée avec Symfony 6.4 permettant aux patients de trouver et réserver des médecins selon leurs spécialités et disponibilités, tout en offrant aux médecins et administrateurs une interface complète de gestion.

## Technologies Utilisées

- **Framework** : Symfony 6.4 (LTS)
- **PHP** : 8.1+
- **Base de données** : MySQL 8.0
- **ORM** : Doctrine
- **Template Engine** : Twig
- **Frontend** : Bootstrap 5.3 + Bootstrap Icons
- **Sécurité** : Symfony Security Component
- **Upload de fichiers** : VichUploaderBundle
- **Pagination** : KnpPaginatorBundle
- **Génération PDF** : DomPDF

## Fonctionnalités Implémentées

### Côté Patient (ROLE_PATIENT)

✅ **Inscription et connexion sécurisée**
- Formulaire d'inscription avec validation
- Authentification par email/mot de passe
- Hashage sécurisé des mots de passe

✅ **Recherche de médecins**
- Recherche par spécialité
- Recherche par ville
- Recherche par nom
- Filtres combinables
- Pagination des résultats

✅ **Consultation du profil médecin**
- Photo du médecin
- Expérience et description
- Spécialité
- Notation moyenne
- Avis des patients

✅ **Prise de rendez-vous**
- Sélection de la date et heure
- Indication du motif de consultation
- Système de statuts (pending, accepted, refused, completed)

✅ **Historique des rendez-vous**
- Liste complète des rendez-vous
- Filtrage par statut
- Pagination

✅ **Système d'avis et notation**
- Notation de 1 à 5 étoiles
- Commentaire textuel
- Modération par l'administrateur

✅ **Dashboard patient**
- Vue d'ensemble des prochains rendez-vous
- Accès rapide aux fonctionnalités

### Côté Médecin (ROLE_DOCTOR)

✅ **Authentification avec rôle ROLE_DOCTOR**
- Connexion sécurisée
- Gestion de session

✅ **Mise à jour du profil**
- Photo de profil (upload)
- Description professionnelle
- Horaires de consultation
- Années d'expérience
- Ville d'exercice

✅ **Gestion des rendez-vous**
- Accepter les demandes de rendez-vous
- Refuser les demandes
- Marquer comme terminé
- Vue liste complète avec pagination

✅ **Consultation de la liste des patients**
- Patients ayant eu des consultations terminées
- Historique par patient

✅ **Création d'ordonnances**
- Formulaire de création
- Contenu de l'ordonnance
- Médicaments prescrits
- Instructions pour le patient
- (Génération PDF à finaliser)

✅ **Statistiques personnelles**
- Nombre total de consultations
- Taux de satisfaction moyen
- Nombre de rendez-vous en attente
- Nombre de rendez-vous à venir

✅ **Dashboard médecin**
- Vue d'ensemble des rendez-vous en attente
- Statistiques clés
- Actions rapides

### Côté Administrateur (ROLE_ADMIN)

✅ **Authentification avec rôle ROLE_ADMIN**
- Accès sécurisé à l'interface admin

✅ **Gestion complète des utilisateurs**
- **Médecins** : Ajouter, modifier, supprimer
- **Patients** : Consulter, supprimer
- Liste paginée avec recherche

✅ **Gestion des spécialités médicales (CRUD)**
- Créer des spécialités
- Modifier les spécialités
- Supprimer les spécialités
- Description de chaque spécialité

✅ **Gestion de tous les rendez-vous**
- Vue globale de tous les rendez-vous
- Filtrage par statut
- Pagination

✅ **Gestion des avis**
- Approuver les avis
- Supprimer les avis inappropriés
- Vue de tous les avis avec pagination

✅ **Tableau de bord de statistiques**
- Total médecins
- Total patients
- Total rendez-vous
- Rendez-vous en attente
- Avis en attente d'approbation
- Top médecins notés
- Spécialités disponibles

✅ **Hiérarchie des rôles**
- ROLE_ADMIN hérite de ROLE_DOCTOR et ROLE_PATIENT
- Accès complet à toutes les fonctionnalités

### Sécurité

✅ **Configuration de sécurité Symfony**
- Firewalls configurés
- Access Control Lists (ACL)
- Protection CSRF sur les formulaires
- Hashage des mots de passe avec bcrypt

✅ **Validation des données**
- Validation côté serveur avec Symfony Validator
- Contraintes sur les entités
- Messages d'erreur personnalisés

✅ **Protection des routes**
- Contrôle d'accès par rôle
- Vérification de propriété des ressources
- Redirection automatique selon le rôle

## Structure de la Base de Données

### Entités Principales

1. **User** (table parente avec héritage JOINED)
   - id, email, password, roles, nom, prenom, phone, createdAt

2. **Patient** (hérite de User)
   - ville, adresse
   - Relations : appointments, reviews

3. **Doctor** (hérite de User)
   - specialty, ville, description, experience, horaires, photo
   - Relations : appointments, reviews

4. **Admin** (hérite de User)
   - Pas de champs supplémentaires

5. **Specialty**
   - nom, description
   - Relations : doctors

6. **Appointment**
   - patient, doctor, dateTime, status, motif, notes, createdAt
   - Relations : prescriptions

7. **Review**
   - patient, doctor, rating (1-5), comment, isApproved, createdAt

8. **Prescription**
   - appointment, content, medications, instructions, pdfPath, createdAt

## Installation et Configuration

### Prérequis

- PHP 8.1 ou supérieur
- MySQL 8.0 ou supérieur
- Composer
- Extensions PHP : ctype, iconv, mysql, xml, mbstring, curl, zip, intl, gd

### Installation

1. **Cloner le projet**
```bash
cd /home/ubuntu/mediconnect_new
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configurer la base de données**

Éditer le fichier `.env` :
```env
DATABASE_URL="mysql://mediconnect:mediconnect123@127.0.0.1:3306/mediconnect?serverVersion=8.0.32&charset=utf8mb4"
```

4. **Créer la base de données**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
```

5. **Charger les données de test**
```bash
php bin/console doctrine:fixtures:load --no-interaction
```

6. **Créer les répertoires d'upload**
```bash
mkdir -p public/uploads/doctors public/uploads/prescriptions
chmod -R 777 public/uploads
```

7. **Démarrer le serveur**
```bash
php -S localhost:8000 -t public
```

8. **Accéder à l'application**
```
http://localhost:8000
```

## Comptes de Test

### Administrateur
- **Email** : admin@mediconnect.com
- **Mot de passe** : admin123

### Médecins
- **Email** : jean.dupont@mediconnect.com
- **Mot de passe** : doctor123

Autres médecins :
- marie.martin@mediconnect.com
- pierre.bernard@mediconnect.com
- sophie.dubois@mediconnect.com
- michel.laurent@mediconnect.com

### Patients
- **Email** : thomas.leroy@email.com
- **Mot de passe** : patient123

Autres patients :
- julie.moreau@email.com
- lucas.simon@email.com

## Structure du Projet

```
mediconnect_new/
├── config/              # Configuration Symfony
│   ├── packages/        # Configuration des bundles
│   └── routes/          # Configuration des routes
├── public/              # Point d'entrée web
│   ├── index.php        # Front controller
│   └── uploads/         # Fichiers uploadés
├── src/
│   ├── Controller/      # Contrôleurs
│   │   ├── SecurityController.php
│   │   ├── RegistrationController.php
│   │   ├── HomeController.php
│   │   ├── PatientController.php
│   │   ├── DoctorController.php
│   │   └── AdminController.php
│   ├── Entity/          # Entités Doctrine
│   │   ├── User.php
│   │   ├── Patient.php
│   │   ├── Doctor.php
│   │   ├── Admin.php
│   │   ├── Specialty.php
│   │   ├── Appointment.php
│   │   ├── Review.php
│   │   └── Prescription.php
│   ├── Form/            # Formulaires Symfony
│   ├── Repository/      # Repositories Doctrine
│   └── DataFixtures/    # Données de test
├── templates/           # Templates Twig
│   ├── base.html.twig
│   ├── home/
│   ├── security/
│   ├── registration/
│   ├── patient/
│   ├── doctor/
│   └── admin/
└── var/                 # Cache et logs
```

## Fonctionnalités à Finaliser

Les fonctionnalités suivantes sont partiellement implémentées et nécessitent une finalisation :

1. **Génération PDF des ordonnances**
   - Service de génération PDF avec DomPDF
   - Template PDF personnalisé
   - Téléchargement sécurisé

2. **Système de notifications par email**
   - Configuration SMTP
   - Templates d'emails
   - Envoi automatique lors des événements (nouveau RDV, acceptation, etc.)

3. **Logs d'activité**
   - Enregistrement des actions importantes
   - Interface de consultation des logs

4. **Amélioration du système de créneaux horaires**
   - Gestion fine des disponibilités des médecins
   - Calendrier interactif
   - Prévention des conflits de rendez-vous

## Sécurité

### Mesures Implémentées

- ✅ Hashage des mots de passe avec bcrypt
- ✅ Protection CSRF sur tous les formulaires
- ✅ Validation des données côté serveur
- ✅ Contrôle d'accès basé sur les rôles (RBAC)
- ✅ Vérification de propriété des ressources
- ✅ Sessions sécurisées
- ✅ Hiérarchie des rôles

### Recommandations pour la Production

- Configurer HTTPS
- Activer le mode production (`APP_ENV=prod`)
- Configurer les secrets Symfony
- Mettre en place un système de backup
- Configurer les logs de sécurité
- Activer le rate limiting
- Configurer un WAF (Web Application Firewall)

## Tests

Pour exécuter les tests (à implémenter) :
```bash
php bin/phpunit
```

## Contribution

Ce projet est un projet académique de développement d'applications sécurisées avec Symfony.

## Licence

Propriétaire - Projet académique

## Auteur

Développé pour le projet MediConnect

## Support

Pour toute question ou problème, veuillez consulter la documentation Symfony officielle :
- https://symfony.com/doc/current/index.html
- https://symfony.com/doc/current/security.html
- https://symfony.com/doc/current/doctrine.html
