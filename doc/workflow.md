## Contexte

EN TANT QU’étudiant de la Coding Factory  
JE VEUX travailler proprement avec Git  
AFIN DE garder un historique clair et fusionner uniquement du code testé

## Branches utilisées

- `dev` : branche principale de développement. Toute la construction du site se fait ici.
- `main` : branche stable. Une fois le site terminé, fonctionnel et testé, `dev` est fusionnée dans `main`.

## Méthodologie

Tout le travail se fait dans `dev`. Aucune fonctionnalité n'est poussée dans `main` tant qu'elle n'est pas complète et testée.

Ce choix permet un déploiement propre et une séparation claire entre le travail en cours (`dev`) et le code final (`main`).

## Étapes typiques

1. **Initialisation du projet**

```bash
git init
git remote add origin https://github.com/EliasL94/Projet-Web.git
git checkout -b dev
git push -u origin dev