Bundle de test
=============

Initialisation 
---------------
Ajouter les lignes suvantes au kernel :

    new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle()

Ajouter au composer.json :

    "repositories": [
        {
            "type": "vcs",
            "url": "git@git.sedona.fr:php/test-bundle.git"
        }
    ]
Exécuter

    composer require sedona/test-bundle --dev
    ou
    php -d memory_limit=-1 composer.phar require sedona/test-bundle --dev
    
Copier les fichiers suivant à la racine de votre projet :
- test.sh
- behat.yml.dist

Copier les dossiers suivant à la racine de votre projet :
- features
- TestBundle à renommer par le nom de votre bundle


Emplacement des tests
----------------
    Tests béhat : /features/testName.feature
    Tests unitaires : /tests/bundleName/Controller/
    DataFixtures : /tests/bundleName/DataFixtures/ORM

Lancement des tests
-----------
    Tous les tests : ./test.sh
    Seulement les tests behat : ./test.sh --behat
    Seulement les tests phpunit : ./test.sh --phpunit

Personnaliser
-------------
features/boostrap/FeatureContext.php permet de personnalisé les commandes disponibles pour le test béhat.

On peut créer un fichier behat.yml pour personnaliser ces tests.

####Exemple de configuration pour docker utilisant phantomjs :

    Phantomjs
    default:
        extensions:
            Behat\Symfony2Extension: ~
            Behat\MinkExtension:
                sessions:
                    browserkit_driver:
                         selenium2:
                            wd_host: "http://127.0.0.1:4444/wd/hub"
                default_session: browserkit_driver
                browser_name: 'phantomjs'
                base_url: "http://127.0.0.1:8000/app_dev.php"