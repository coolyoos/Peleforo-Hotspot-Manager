\# 🌐 Peleforo Hotspot Manager



Application web complète pour gérer votre portail captif Mikrotik en toute simplicité.



\## ✨ Fonctionnalités



\- ✅ \*\*Configuration simple\*\* - Connexion à votre Mikrotik en 3 clics

\- 🎫 \*\*Génération de vouchers\*\* - Créez des codes d'accès automatiquement

\- 🎨 \*\*Personnalisation du portail\*\* - Modifiez thèmes, messages et tarifs

\- 🖨️ \*\*Impression de tickets\*\* - Tickets professionnels prêts à imprimer

\- 📊 \*\*Gestion des profils\*\* - Créez vos propres profils de connexion

\- 📈 \*\*Historique\*\* - Consultez les vouchers générés

\- ⚙️ \*\*Interface moderne\*\* - Design épuré et intuitif



\## 📋 Prérequis



\### Sur votre PC/Serveur :

\- \*\*PHP 7.4+\*\* (avec extension sockets)

\- \*\*Serveur web\*\* (Apache, Nginx) ou XAMPP/WAMP/Laragon

\- \*\*Navigateur web moderne\*\*



\### Sur votre Mikrotik :

\- \*\*RouterOS v6.0+\*\*

\- \*\*API activée\*\* (port 8728)

\- \*\*Hotspot configuré\*\*



\## 🚀 Installation



\### Étape 1 : Installation sur votre PC



\#### Avec XAMPP (Recommandé pour Windows)



1\. \*\*Téléchargez et installez XAMPP\*\* : https://www.apachefriends.org/

2\. \*\*Démarrez Apache\*\* depuis le panneau de contrôle XAMPP

3\. \*\*Copiez les fichiers\*\* dans `C:\\xampp\\htdocs\\peleforo\\`

4\. \*\*Accédez à l'application\*\* : http://localhost/peleforo/



\#### Avec WAMP



1\. \*\*Installez WAMP\*\* : https://www.wampserver.com/

2\. \*\*Copiez les fichiers\*\* dans `C:\\wamp64\\www\\peleforo\\`

3\. \*\*Accédez\*\* : http://localhost/peleforo/



\#### Sur Linux (Ubuntu/Debian)



```bash

\# Installer Apache et PHP

sudo apt update

sudo apt install apache2 php php-sockets



\# Copier les fichiers

sudo cp -r peleforo /var/www/html/



\# Donner les permissions

sudo chown -R www-data:www-data /var/www/html/peleforo

sudo chmod -R 755 /var/www/html/peleforo



\# Accéder à l'application

http://votre-ip/peleforo/

```



\### Étape 2 : Activer l'API Mikrotik



Connectez-vous à votre Mikrotik via \*\*Winbox\*\* ou \*\*Terminal\*\* et exécutez :



```

/ip service enable api

/ip service set api port=8728

```



Vérifiez que l'API est active :

```

/ip service print

```



\### Étape 3 : Configuration initiale



1\. \*\*Ouvrez votre navigateur\*\* : http://localhost/peleforo/

2\. \*\*Remplissez le formulaire\*\* :

&nbsp;  - IP Mikrotik : `192.168.88.1` (votre IP)

&nbsp;  - Port API : `8728`

&nbsp;  - Nom d'utilisateur : `admin`

&nbsp;  - Mot de passe : votre mot de passe admin

&nbsp;  - Nom Hotspot : `hotspot1` (ou votre nom)

3\. \*\*Cliquez sur "Connecter"\*\*



C'est terminé ! 🎉



\## 📁 Structure des fichiers



```

peleforo/

├── index.php              # Page de configuration initiale

├── dashboard.php          # Tableau de bord principal

├── vouchers.php           # Génération de vouchers

├── customize.php          # Personnalisation du portail

├── print.php              # Impression de tickets

├── settings.php           # Paramètres de connexion

├── routeros\_api.php       # Librairie API Mikrotik

├── config.json            # Configuration (généré automatiquement)

├── portal\_custom.json     # Personnalisation portail (généré)

├── vouchers\_history.json  # Historique vouchers (généré)

└── splash\_generated.html  # Page splash générée (généré)

```



\## 🎯 Utilisation rapide



\### Générer des vouchers



1\. \*\*Dashboard\*\* → Cliquez sur "Générer des vouchers"

2\. \*\*Sélectionnez\*\* :

&nbsp;  - Profil (1h, 3h, 1 jour, etc.)

&nbsp;  - Durée de validité

&nbsp;  - Prix

&nbsp;  - Nombre de vouchers

3\. \*\*Cliquez sur "Générer"\*\*

4\. \*\*Options\*\* : Imprimer, Exporter CSV, ou Créer des tickets



\### Personnaliser le portail



1\. \*\*Dashboard\*\* → "Personnaliser le portail"

2\. \*\*Modifiez\*\* :

&nbsp;  - Thème de couleur (Bleu, Vert, Violet, Orange)

&nbsp;  - Nom du réseau

&nbsp;  - Message de bienvenue

&nbsp;  - Tarifs affichés

3\. \*\*Cliquez sur "Enregistrer"\*\*

4\. \*\*Téléchargez\*\* le fichier `splash\_generated.html`

5\. \*\*Uploadez-le\*\* sur votre Mikrotik via \*\*Files\*\*



\### Uploader la page splash sur Mikrotik



\#### Via Winbox :

1\. \*\*Files\*\* → Glissez `splash\_generated.html`

2\. Renommez en `hotspot/login.html`



\#### Via FTP :

```bash

ftp 192.168.88.1

\# Connectez-vous

put splash\_generated.html hotspot/login.html

```



\## 🔧 Configuration avancée



\### Créer des profils personnalisés



Les profils permettent de définir :

\- \*\*Limite de débit\*\* : `512k/512k` (upload/download)

\- \*\*Durée de session\*\* : `1h`, `3h`, `1d`, `1w`, `1m`

\- \*\*Utilisateurs partagés\*\* : Nombre de connexions simultanées



\*\*Exemple via Winbox\*\* :

```

IP → Hotspot → User Profiles → Add New

Name: 1h-1Mbps

Rate Limit: 1M/1M

Session Timeout: 01:00:00

Shared Users: 1

```



\### Format des durées



\- `1h` = 1 heure

\- `3h` = 3 heures

\- `1d` = 1 jour

\- `1w` = 1 semaine

\- `1m` = 1 mois



\### Format des débits



\- `512k/512k` = 512 Kbps (upload/download)

\- `1M/1M` = 1 Mbps

\- `2M/5M` = 2 Mbps upload / 5 Mbps download



\## 🔒 Sécurité



\- ✅ Les mots de passe sont stockés localement

\- ✅ Connexion API sécurisée

\- ✅ Pas de données envoyées à des serveurs externes

\- ⚠️ \*\*Important\*\* : Protégez l'accès à l'application avec un mot de passe htaccess



\### Protéger avec .htaccess (optionnel)



Créez un fichier `.htaccess` dans le dossier peleforo :



```apache

AuthType Basic

AuthName "Peleforo Admin"

AuthUserFile /chemin/vers/.htpasswd

Require valid-user

```



Générez le fichier `.htpasswd` :

```bash

htpasswd -c .htpasswd admin

```



\## ❓ Dépannage



\### L'API ne se connecte pas



\*\*Vérifiez\*\* :

1\. L'API est activée : `/ip service print`

2\. Le pare-feu autorise le port 8728

3\. L'IP et les identifiants sont corrects

4\. PHP a l'extension `sockets` active : `php -m | grep sockets`



\### Les vouchers ne sont pas créés



\*\*Vérifiez\*\* :

1\. Le profil existe sur Mikrotik

2\. Le serveur Hotspot est actif

3\. La connexion API fonctionne



\### La page splash ne s'affiche pas



\*\*Vérifiez\*\* :

1\. Le fichier est bien uploadé dans `/hotspot/`

2\. Le nom du fichier est `login.html`

3\. Le Hotspot est configuré pour utiliser cette page



\## 📞 Support



Pour toute question ou problème :

\- Vérifiez d'abord ce README

\- Consultez les logs PHP (dans XAMPP : `xampp/logs/error.log`)

\- Vérifiez les logs Mikrotik : `/log print`



\## 📝 Notes importantes



\- \*\*Sauvegardez\*\* régulièrement votre configuration Mikrotik

\- \*\*Testez\*\* d'abord sur un environnement de test

\- \*\*Mettez à jour\*\* régulièrement RouterOS

\- Les fichiers JSON contiennent vos configurations, ne les supprimez pas



\## 🎨 Personnalisation avancée



Le fichier `splash\_generated.html` peut être modifié directement pour :

\- Ajouter votre logo

\- Modifier les couleurs exactes

\- Ajouter des images de fond

\- Intégrer des QR codes

\- Ajouter des conditions d'utilisation



\## 📊 Feuille de route



Fonctionnalités à venir :

\- 📱 Application mobile

\- 📈 Statistiques avancées

\- 💳 Intégration paiement mobile

\- 🔔 Notifications

\- 🌍 Multi-routeurs

\- 📧 Envoi de vouchers par email/SMS



\## 📄 Licence



Peleforo Hotspot Manager - Libre d'utilisation



---



\*\*Créé avec ❤️ pour simplifier la gestion des hotspots Mikrotik\*\*



Version 1.0 - 2025

