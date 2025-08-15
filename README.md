# Suivi de Production - Cofat

Ce projet permet de visualiser en temps réel la production des machines US (ultrason) avec affichage sous forme de **flash cards** et de **courbes dynamiques** grâce à Chart.js.  
Il inclut également un système de gestion et de suivi des erreurs.

---

## 🚀 Fonctionnalités

### 🔹 1. Flash Cards dynamiques
- Affiche les statistiques de production pour chaque machine USxx.
- Couleur et style adaptatifs en fonction des résultats.
- Mise à jour automatique via lecture des logs ou depuis la base de données `ultrason`.

### 🔹 2. Courbes Chart.js
- Courbes dynamiques par **machine** et **splice**.
- Zoom et survol interactif pour voir les valeurs exactes.
- Données rafraîchies sans recharger la page.

### 🔹 3. Gestion des erreurs
- Détection automatique des pièces erronées via les logs ou la base de données.
- Les erreurs sont affichées avec **code** et **nom** dans `erreur.php`.
- Les erreurs sont aussi associées à la machine et au splice concernés.

### 🔹 4. Responsive Design
- Navigation **sticky** (reste visible en haut de page même en scrollant).
- Affichage optimisé pour mobile, tablette et desktop.
- Cartes adaptatives pour écran réduit.

---

## 🛠 Technologies utilisées

- **PHP** : Backend, lecture logs & connexion SQL Server.
- **HTML / CSS / JavaScript** : Interface et dynamique.
- **Chart.js** : Graphiques interactifs.
- **Bootstrap** : Mise en page responsive.
- **SQL Server** : Stockage des données de production.
- **Fichiers logs** : Source de données alternative.
## Captures du projet 
![Alt text](https://github.com/malek413/ultrason_cofat/blob/main/images/Capture%20d%E2%80%99%C3%A9cran%202025-08-15%20214754.png)
![Alt text](https://github.com/malek413/ultrason_cofat/blob/main/images/Capture%20d%E2%80%99%C3%A9cran%202025-08-15%20214826.png)
![Alt text](https://github.com/malek413/ultrason_cofat/blob/main/images/Capture%20d%E2%80%99%C3%A9cran%202025-08-15%20214903.png)
![Alt text](https://github.com/malek413/ultrason_cofat/blob/main/images/Capture%20d%E2%80%99%C3%A9cran%202025-08-15%20214925.png)
![Alt text](https://github.com/malek413/ultrason_cofat/blob/main/images/Capture%20d%E2%80%99%C3%A9cran%202025-08-15%20214949.png)
📊

