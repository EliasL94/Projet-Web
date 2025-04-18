# Projet-Web

Ce projet permet aux utilisateurs de localiser les fontaines à boire à Paris grâce à une carte interactive et la géolocalisation automatique.

## Techno utilisées
- HTML / CSS / JavaScript
- Leaflet.js (librairie pour la carte)
- Base de données MySQL
- PHP (si connexion à la BDD)

## Structure
- `/www` : contient le site
- `/doc` : documentation complète (BDD, workflow, installation…)

## Source des données
Les données des fontaines proviennent de : 

https://opendata.paris.fr/explore/embed/dataset/fontaines-a-boire/table/?disjunctive.type_objet&disjunctive.modele&disjunctive.commune&disjunctive.dispo&sort=-commune&basemap=jawg.dark&location=16,48.82164,2.35112

Bibliothèques CSS :

Font Awesome 6.4.0: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css
Leaflet CSS 1.9.4: https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css
Leaflet Routing Machine CSS: https://cdnjs.cloudflare.com/ajax/libs/leaflet-routing-machine/3.2.12/leaflet-routing-machine.css

Bibliothèques JavaScript :

Leaflet JS 1.9.4: https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js
Leaflet Routing Machine JS: https://cdnjs.cloudflare.com/ajax/libs/leaflet-routing-machine/3.2.12/leaflet-routing-machine.min.js

Services externes :  

OpenStreetMap pour les tuiles cartographiques: https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png
OSRM (Open Source Routing Machine) pour le calcul d'itinéraires: https://router.project-osrm.org/route/v1