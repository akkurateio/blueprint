# Akkurate Laravel Blueprint

Ce package étend laravel-shift/blueprint dont la documentation se trouve ci-dessous :

- [Laravel Blueprint](https://blueprint.laravelshift.com/)
- [Définition des models](https://blueprint.laravelshift.com/docs/defining-models/)
- [Définition des controllers](https://blueprint.laravelshift.com/docs/defining-controllers/)
- [Configuration avancée](https://blueprint.laravelshift.com/docs/advanced-configuration/)
- [Étendre Blueprint](https://blueprint.laravelshift.com/docs/extending-blueprint/)

## Installation

Ajouter les lignes suivantes dans le fichier `composer.json` du projet :
```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/akkurateio/blueprint"
    }
]
```

```bash
composer require akkurateio/blueprint:dev-master
```

## Configuration

```
php artisan vendor:publish --tag=blueprint-config
php artisan blueprint:config
```
