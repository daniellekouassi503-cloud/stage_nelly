# Plateforme scolaire

Application web de gestion scolaire inspirée du fonctionnement observé à l’EPCCI et décrite dans le dossier `WORDstage`. Elle centralise les comptes, les notes, les supports de cours et le suivi des frais de scolarité. Le paiement réel reste un versement à la banque : la plateforme ne débite aucun compte.

Cette application est une proposition. Elle n’a pas été adoptée ni déployée par l’établissement.

## Technologies

- Laravel 13 et PHP 8.3 pour l’API REST
- Vue.js 3, Vue Router et Pinia pour l’interface
- MySQL 8 / 9 pour les données
- Laravel Sanctum pour les jetons d’authentification
- DomPDF pour les fiches de versement, les reçus et les relevés
- Feuilles de style, polices et icônes du template Preskool (Dreams Technologies), adaptées en français

## Organisation

- `backend/` : API Laravel, migrations, règles métier, PDF, tests
- `frontend/` : interface Vue.js
- `frontend/public/preskool/` : ressources visuelles du template (CSS, polices, images, Bootstrap)
- `WORDstage/` : règles métier complémentaires du projet (ex. connexion parent avec matricule)
- Le dossier Word d’origine sur le Bureau a aussi servi de source métier

La base déjà présente `gestion_scolaire` n’a pas été modifiée. Cette application utilise une base séparée, `plateforme_scolaire`.

## Prérequis

- PHP 8.3 avec les extensions habituelles de Laravel (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`)
- Composer
- Node.js 20 ou plus récent et npm
- MySQL (WAMP convient). Le compte local utilisé ici est `root` sans mot de passe.

## Installation

```powershell
cd backend
copy .env.example .env
php artisan key:generate
```

Renseigner dans `.env` :

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=plateforme_scolaire
DB_USERNAME=root
DB_PASSWORD=
```

Créer la base, puis lancer les migrations et les données de démonstration. Le seeder refuse de s’exécuter si `APP_ENV` n’est pas `local`.

```powershell
mysql -u root -e "CREATE DATABASE IF NOT EXISTS plateforme_scolaire CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
php artisan db:seed
```

```powershell
cd ..\frontend
npm install
```

## Démarrage

Deux terminaux :

```powershell
cd backend
php artisan serve --host=127.0.0.1 --port=8001
```

```powershell
cd frontend
npm run dev
```

Ouvrir [http://localhost:5173](http://localhost:5173). L’API est sur [http://127.0.0.1:8001/api](http://127.0.0.1:8001/api). En développement, Vite relaie `/api` vers Laravel. Le port 8001 est utilisé parce qu’un autre projet local occupe déjà le port 8000 sur cette machine. Si le port 8000 est libre, vous pouvez lancer `php artisan serve` et aligner le proxy de `frontend/vite.config.js`.

Compilation de production du frontend :

```powershell
cd frontend
npm run build
```

## Comptes de démonstration

Uniquement pour un environnement local. Mot de passe commun : `Demo-2026!`

| Rôle | E-mail |
| --- | --- |
| Administrateur | admin@plateforme.test |
| Enseignant | enseignant@plateforme.test |
| Étudiant | etudiant@plateforme.test |
| Second étudiant | etudiant2@plateforme.test |
| Parent d’Aya (saisir aussi le matricule `ETU-2025-001`) | parent@plateforme.test |
| Parent de Moussa (saisir aussi le matricule `ETU-2025-002`) | parent2@plateforme.test |

Ces identifiants sont fictifs. Ne pas les réutiliser hors d’une machine de test. Le seeder ne tourne pas si l’environnement n’est pas `local`.

## Fonctionnalités

- Quatre espaces distincts : administrateur, enseignant, étudiant, parent
- Comptes étudiants et enseignants créés par l’administration
- Inscription parent : il faut le matricule de l’enfant **et** le téléphone du parent enregistré dans le dossier de l’étudiant (second facteur). Si les deux correspondent, le compte est actif immédiatement (pas de validation admin) ; sinon, un message unique ne révèle pas si le matricule existe
- Connexion parent : matricule obligatoire, existant et rattaché au compte ; message « Bienvenue parent « nom de l’étudiant » »
- Retour possible vers la page d’accueil depuis l’espace connecté ; déconnexion vers l’accueil
- Page publique `/formalites-inscription` alignée sur les formalités EPCCI
- Années, filières, niveaux, classes, matières, coefficients, inscriptions et affectations
- Notes sur 20, brouillon, soumission, publication
- Une note non publiée n’est pas renvoyée aux étudiants ni aux parents
- Signalement d’une note publiée et traitement par l’enseignant
- Moyenne de matière : moyenne arithmétique des notes présentes (devoirs et interrogations au même poids)
- Moyenne générale : somme(moyenne × coefficient) / somme des coefficients des matières qui ont au moins une note
- Absence de note non comptée comme zéro ; sinon le libellé « Moyenne non disponible »
- Supports liés à une matière, une filière et un niveau, téléchargement autorisé seulement pour les inscrits concernés
- Tranches en FCFA, fiche de versement PDF, validation ou rejet avec motif
- Reçu PDF seulement après validation
- Simulation de justificatif, clairement identifiée, qui ne valide pas le paiement toute seule
- Notifications et journal d’activité
- Tableau de bord financier réservé à l’administration

Filières reprises du dossier : IDA, Logistique Transport, Finance Comptabilité, Gestion Commerciale, Tourisme et Hôtellerie.

## Tests exécutés

Commande : `php artisan test` dans `backend`.

Résultat au 16 octobre 2026 : **10 tests, 10 réussites, 40 assertions**.

Ils couvrent :

- le calcul et l’arrondi des moyennes
- l’invisibilité d’une note non publiée, y compris par appel direct
- l’interdiction pour un étudiant de lire le relevé d’un autre
- le parent limité à son enfant rattaché ; inscription parent refusée si le matricule est inconnu
- la publication seulement après soumission
- la simulation de paiement, le rejet sans motif, l’interdiction pour l’étudiant de valider, le reçu PDF et l’accès refusé à un autre étudiant
- le téléchargement d’un support refusé à une autre filière
- la génération du relevé PDF

`npm run build` dans `frontend` s’est terminé sans erreur.

Vérification dans le navigateur, le 16 octobre 2026, avec l’API sur le port 8001 et Vite sur le port 5173 :

- connexion administrateur, tableau de bord (2 étudiants, 5 filières, 800 000 FCFA dus, 0 encaissé) et menu propre au rôle ;
- liste des étudiants et ouverture d’une évaluation publiée (notes 14,00 et 11,50 en lecture seule) ;
- affichage réduit sur une largeur mobile, puis sur une largeur bureau avec le menu latéral ;
- connexion étudiante : moyenne générale 15,00, notes publiées seulement, frais de 400 000 FCFA ;
- génération d’une fiche pour la 1re tranche d’Aya Konan : le statut reste « En attente de paiement » ;
- connexion parent : un seul enfant rattaché, relevé de cet enfant affiché sans saisie manuelle du matricule.

Cette vérification a laissé une fiche en attente pour `ETU-2025-001`. Pour retrouver les données de démonstration d’origine : `php artisan migrate:fresh --seed` dans `backend` (uniquement la base `plateforme_scolaire`).

Les tests automatisés utilisent SQLite en mémoire. Les contraintes `CHECK` MySQL (note entre 0 et 20, coefficient positif, semestre 1 ou 2, motif de rejet, vérificateur obligatoire) sont ajoutées lors de la migration MySQL. L’application les contrôle aussi avant l’enregistrement.

## Hypothèses

Le dossier ne fixe pas toutes les valeurs opérationnelles. Les choix suivants sont techniques et signalés comme tels :

- Codes de filière `LT` pour Logistique Transport, et niveaux `N1` / `N2` pour la première et la deuxième année.
- Une année peut être marquée courante (`est_courante`) afin de savoir laquelle notifier. Ce drapeau n’est pas dans le MPD.
- Le statut `brouillon` s’ajoute à `non_publiee` et `publiee`. La soumission fait passer le brouillon à « non publiée », puis la publication le rend visible.
- L’arrondi est la moitié supérieure, à deux décimales, appliqué à la moyenne de matière puis à la moyenne générale. Le rapport indique que la règle d’arrondi reste à confirmer avec l’établissement.
- Les montants des tranches de démonstration sont fictifs (par exemple 150 000 FCFA). L’établissement fixe les vrais montants.
- Le rattachement parent–étudiant est enregistré dans `rattacher` dès l’inscription si le matricule est valide. `demandes_rattachement` conserve une trace acceptée.
- Le journal d’activité est un complément demandé pour l’exploitation, en plus des notifications du MPD.
- Le mode `simulation` sur la fiche de versement prépare un essai sans banque. Il ne constitue pas une passerelle de paiement.
- La fiche PDF reprend les champs décrits dans l’étude (matricule, année, identité, filière, niveau, classe, produit, montant). Elle ne reproduit pas le formulaire officiel BANKCOLLECT ni les logos de la Chambre de Commerce et de UBA.

## Limites

- Aucune passerelle de paiement réelle. L’architecture sépare la génération de la fiche, le dépôt d’un justificatif et la décision administrative, ce qui permettra plus tard de brancher une banque sans traiter un clic comme un encaissement.
- Pas de réinitialisation de mot de passe par e-mail.
- Pas d’antivirus sur les fichiers téléversés. Les extensions et la taille sont contrôlées, et les fichiers restent hors du dossier public.
- L’index MySQL de ce poste est limité à 1 000 octets. Les chaînes indexées utilisent donc une longueur compatible (`Schema::defaultStringLength(191)`).

## Identité EPCCI

Le nom affiché est **EPCCI**, à la place de Preskool. Les couleurs reprennent le bordeaux du site public [epcci.edu.ci](https://www.epcci.edu.ci/) (`#5f060d`). La page d’accueil reprend les informations publiques de l’établissement : nom complet, devise CRAC, téléphone, e-mail, rentrée 2026-2027 et filières BTS. Ce portail n’est pas le site institutionnel WordPress ; il sert au suivi des notes, des supports et des frais.

## Versions

La première version de l’interface, plus simple, est conservée dans Git à la racine du projet, commit `63c6f6d`. Pour la revoir sans perdre le travail actuel :

```powershell
git stash
git checkout 63c6f6d
```

Pour revenir à la version en cours : `git checkout main`.

Les trois archives de `template stage` (administration, enseignant, étudiant) fournissent le gabarit Preskool utilisé ensuite : en-tête, menu latéral par rôle et tableaux de bord. Les pages de démonstration du template (chat, paie, faux établissements) ne sont pas reprises.

## Licence du template

Les CSS, polices, icônes et images Preskool proviennent de l’archive fournie pour ce stage. Ils restent soumis à la licence Dreams Technologies. Ils ne doivent pas être redistribués comme un template. Le code de l’application (Laravel et Vue) est distinct de ce template.

## Pistes

- Brancher le circuit bancaire réel après accord de l’établissement et de la banque
- Confirmer avec l’établissement l’arrondi, le nombre de tranches et les montants
- Ajouter l’envoi d’e-mails en plus des notifications internes
