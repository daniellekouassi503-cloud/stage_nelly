# Règles métier complémentaires — EPCCI Portail

Document ajouté au dossier WORDstage du projet pour tracer les règles validées après la première version.

## Compte parent sans validation administrative

Un parent crée son compte en indiquant le **matricule d’un étudiant**.

- Si le matricule **existe** dans la base des étudiants de l’établissement, le compte parent est **immédiatement valide**.
- Le rattachement parent–étudiant est créé tout de suite (table `rattacher`).
- **Aucune validation de l’administrateur n’est requise.**

Si le matricule est inconnu, l’inscription est refusée.

## Connexion parent et matricule étudiant

Un parent se connecte avec e-mail, mot de passe et le **matricule de l’étudiant** qui :

1. existe dans la base des étudiants ;
2. est rattaché à son compte parent.

Sans matricule, ou avec un matricule inconnu / non associé, la connexion est refusée.

Après connexion, l’espace parent affiche en grand :
**Bienvenue parent « nom de l’étudiant »**.

## Navigation vers l’accueil

Depuis l’espace connecté, un lien permet de revenir à la page d’accueil publique.
Après déconnexion, l’utilisateur est renvoyé vers la page d’accueil.

## Formalités d’inscription

La page `/formalites-inscription` reprend les informations publiées sur
https://www.epcci.edu.ci/admissions/formalites-dinscription/
(dossiers 1re année BTS, 2e année BTS, licence et master, frais indiqués).
