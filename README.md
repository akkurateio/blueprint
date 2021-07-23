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

En fonction des besoins, commenter les générateurs inutiles dans le fichier de configuration `blueprint.php` :

```php
'generators' => [
    'controller' => \Blueprint\Generators\ControllerGenerator::class,
    'factory' => \Blueprint\Generators\FactoryGenerator::class,
    'migration' => \Blueprint\Generators\MigrationGenerator::class,
    'model' => \Blueprint\Generators\ModelGenerator::class,
    'route' => \Blueprint\Generators\RouteGenerator::class,
    'seeder' => \Blueprint\Generators\SeederGenerator::class,
    'test' => \Blueprint\Generators\TestGenerator::class,
    'event' => \Blueprint\Generators\Statements\EventGenerator::class,
    'form_request' => \Blueprint\Generators\Statements\FormRequestGenerator::class,
    'job' => \Blueprint\Generators\Statements\JobGenerator::class,
    'mail' => \Blueprint\Generators\Statements\MailGenerator::class,
    'notification' => \Blueprint\Generators\Statements\NotificationGenerator::class,
    'resource' => \Blueprint\Generators\Statements\ResourceGenerator::class,
    // 'view' => \Blueprint\Generators\Statements\ViewGenerator::class,
    'typescript_interface' => \Blueprint\Generators\Typescript\TypescriptInterfaceGenerator::class,
    'typescript_service' => \Blueprint\Generators\Typescript\TypescriptServiceGenerator::class,
    'typescript_store' => \Blueprint\Generators\Typescript\TypescriptStoreGenerator::class,
]
```

## Utilisation

Créer un fichier `draft.yaml` :

```bash
php artisan blueprint:init
```

Si le fichier `draft.yaml` contient un model User, celui du projet Laravel de base sera redéfini (migration, model…).

Example de fichier `draft.yaml` :

```yaml
models:
  AddressType:
    name: string
    code: string
    priority: nullable integer default:0
    is_active: nullable boolean default:1
    is_default: nullable boolean default:0    

  Address:
    addressable: morphs # addressable_type: App\Models\User, App\Models\Organization, App\Models\Contact
    name: nullable string
    street: string
    complement_1: nullable string
    complement_2: nullable string
    postcode: string
    city: string
    address_type_id: id foreign
    is_active: nullable boolean default:1
    is_default: nullable boolean default:0
    latitude: nullable string
    longitude: nullable string
    softDeletes
        
  User:
    firstname: nullable string
    lastname: nullable string
    phone_office: nullable string
    phone_mobile: nullable string
    position: nullable string
    email: string
    email_verified_at: nullable timestamp
    password: string
    remember_token: nullable string:100
    is_active: nullable boolean default:1
    preferences: nullable json
    is_notifiable: nullable boolean default:0
    image_path: nullable string
    softDeletes
    relationships:
      belongsToMany: Organization
      morphMany: Address
    traits:
      HasComments: BeyondCode\Comments\Traits\HasComments
      HasRoles: Spatie\Permission\Traits\HasRoles

  Organization:
    name: string
    email: nullable string
    phone: nullable string
    url: nullable string
    image_path: nullable string
    is_active: nullable boolean default:1
    preferences: nullable json
    owner_id: nullable id foreign:users
    softDeletes
    relationships:
      belongsToMany: User
      hasMany: Contact
      hasOne: Setting
      morphMany: Address

  ContactType:
    name: string
    code: string:8

  Contact:
    name: string
    url: nullable string
    email: string
    phone_mobile: nullable string
    phone_office: nullable string
    position: nullable string
    contact_type_id: id foreign
    organization_id: id foreign
    softDeletes
    relationships:
      morphMany: Address
    traits:
      HasComments: BeyondCode\Comments\Traits\HasComments
      
controllers:
  AddressType:
    resource: api
  Address:
    resource: api
  User:
    resource: api.index, api.show, api.update, api.destroy
  Organization:
    resource: api
  ContactType:
    resource: api
  Contact:
    resource: api
    
seeders:
  AddressType,
  Address,
  User,
  Organization,
  ContactType,
  Contact,
```

## Étendre le générateur

Passer le projet akkurateio/blueprint en local.

Dans le fichier `composer.json` (en adaptant le chemin relatif) :

```json
"repositories": [
    {
        "type": "path",
        "url": "../../akkurateio/blueprint"
    }
]
```

Puis 

```bash
composer update
```

Ajouter le nouveau générateur à la liste des générateurs à prendre en compte dans **les** fichiers de config ; 
le fichier de config `blueprint.php` dans akkurateio/blueprint mais également le fichier de config `blueprint.php` situé dans le projet depuis lequel on va tester le générateur.

```php
    'generators' => [
//        'controller' => \Blueprint\Generators\ControllerGenerator::class,
//        'factory' => \Blueprint\Generators\FactoryGenerator::class,
//        'migration' => \Blueprint\Generators\MigrationGenerator::class,
//        'model' => \Blueprint\Generators\ModelGenerator::class,
//        'route' => \Blueprint\Generators\RouteGenerator::class,
//        'seeder' => \Blueprint\Generators\SeederGenerator::class,
//        'test' => \Blueprint\Generators\TestGenerator::class,
//        'event' => \Blueprint\Generators\Statements\EventGenerator::class,
//        'form_request' => \Blueprint\Generators\Statements\FormRequestGenerator::class,
//        'job' => \Blueprint\Generators\Statements\JobGenerator::class,
//        'mail' => \Blueprint\Generators\Statements\MailGenerator::class,
//        'notification' => \Blueprint\Generators\Statements\NotificationGenerator::class,
//        'resource' => \Blueprint\Generators\Statements\ResourceGenerator::class,
//        'view' => \Blueprint\Generators\Statements\ViewGenerator::class,
//        'typescript_interface' => \Blueprint\Generators\Typescript\TypescriptInterfaceGenerator::class,
//        'typescript_service' => \Blueprint\Generators\Typescript\TypescriptServiceGenerator::class,
//        'typescript_store' => \Blueprint\Generators\Typescript\TypescriptStoreGenerator::class,
        'awesome_idea' => \Blueprint\Generators\AwesomeIdeaGenerator::class,
    ],
```

Au niveau du projet `akkurateio/blueprint`, créer le nouveau générateur. Le plus simple, quand c’est possible, est généralement de partir d’un générateur proche.

Dans les grandes lignes, un générateur est composé d’une méthode principale :

```php
public function output(Tree $tree): array
{
    //
}
```

En fonction des contextes, cette méthode va générer des fichiers, souvent à partir de stubs, pour chaque model, controller ou seeder référencé dans le fichier `draft.yaml` du projet, et accessible via le `Tree $tree` de Blueprint.

La méthode contient donc généralement une des trois possibilités suivantes : 

`foreach ($tree->models() as $model) {}`

`foreach ($tree->controllers() as $controller) {}`

`foreach ($tree->seeders() as $seeders) {}`

Chaque générateur a sa propre logique, parfois complexe, mais généralement cela consiste à envoyer à l’emplacement souhaité un fichier stub rempli avec les valeurs attendues :

```php
$this->filesystem->put($path, $this->populateStub($stub, $model));
```

Exemple de méthode populateStub() :

```php
protected function populateStub(string $stub, $model)
{
    $stub = str_replace('{{ model }}', $model->name(), $stub);
    $stub = str_replace('//', $this->buildData($model), $stub);
    $stub = str_replace('{{ imports }}', $this->buildImports($model, $stub), $stub);

    return $stub;
}
```

Développer progressivement, en commençant par envoyer les fichiers, bien nommés, au bon endroit.

Remplacer les valeurs simples et facilement identifiables.

Ajouter les logiques complexes du générateur (généralement via une méthode `$this->buildData()`).

Essayer d’identifier et gérer les cas particuliers.

Tester régulièrement depuis le nouveau projet avec la commande :

```
php artisan blueprint:build
```

Pour générer uniquement les fichiers du nouveau générateur, penser à commenter tous les autres dans le fichier `blueprint.php` du projet, comme dans l’exemple ci-dessus.

Une fois que le résultat attendu est obtenu, emballer le tout dans un joli commit.
