# ep-plugin
Plugin WordPress du site tela-botanica.org

Il fournit :
 - l'espace projets : intégration de [cumulus-front](https://github.com/telabotanica/cumulus-front), [ezmlm-forum](https://github.com/telabotanica/ezmlm-forum), [yeswiki](https://github.com/telabotanica/yeswiki), widgets du CeL
 - la gestion de la lettre d'actualités : [dés]abonnement, rédaction, envoi
 - la gestion des _hooks_ à appeler lors de la création d'un utilisateur ou lors d'un changement d'adresse email
 - la gestion d'un jeton SSO admin longue durée pour effectuer des opérations d'administration

Il est conçu pour être utilisé avec :
 - le [thème Tela Botanica](https://github.com/telabotanica/wp-theme-telabotanica)

## installation
Copier / cloner ce code dans le répertoire wp-content/plugins de Wordpress

Lancer Composer :
```
composer install
```

## configuration
Copier le fichier `config.default.json` en `config.json`

Se rendre dans le Tableau de Bord de Wordpress, dans le menu `Tela Botanica`
(...)
