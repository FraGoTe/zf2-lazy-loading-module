ZF2 Lazy loading module
==============

Version 1.5.2 Created by [Vincent Blanchon](http://developpeur-zend-framework.fr/)

Introduction
------------

For this project, i redefined a party of the module manager to have a lazy loading and increase the performance.
Project exemple is available on [the loazy loading module website](http://lazy-loading.zend-framework-2.fr/)

Installation
------------

1) Install with composer :

Add the dependency in composer.json :

```json
{
    "require" : {
        "blanchonvincent/zf2-lazy-loading-module" : "dev-master"
    }
}
```

```bash
php composer.phar update
```

2) Modify your ./init_autoloader.php :
```php
<?php

require_once __DIR__ . '/vendor/ZF2/library/Zend/Loader/AutoloaderFactory.php';

// Composer autoloading
if (file_exists('vendor/autoload.php')) {
    $loader = include 'vendor/autoload.php';
}

Zend\Loader\AutoloaderFactory::factory(array(
    'Zend\Loader\StandardAutoloader' => array(
        'autoregister_zf' => true,
        'namespaces' => array(
            'ZFMLL' => __DIR__ . '/vendor/blanchonvincent/zf2-lazy-loading-module',
        ),
    ),
));
```

3) Modify your ./config/application.config.php :
```php
<?php
return array(
    'modules' => array(
        // your modules
    ),
    'module_listener_options' => array(
        'module_paths' => array(
            './module',
            './vendor',
        ),
        'config_glob_paths' => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
        'lazy_loading' => array(
            // define here your rules
        ),
    ),
    'service_manager' => array(
        'factories'    => array(
            'ModuleManager' => 'ZFMLL\Mvc\Service\ModuleManagerFactory',
        ),
    ),
);
```

Lazy Loading module usage
------------

Lazy loading module concept can load only authorize module for the current environment.
Exemple :

1) I want load my "Administration" module only on port 443, and if my client ip is valid.
Without that, to load this module is useless.

The config to load module only on port 443 and ip on white list, with config/application.config.php :

```php
<?php
return array(
    'modules' => array(
        'Application',
        'Cron',
        'Administration',
        'Blog',
    ),
    'module_listener_options' => array(
        'config_glob_paths'    => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
        'config_cache_enabled' => false,
        'cache_dir'            => 'data/cache',
        'module_paths' => array(
            'Application' => './module/Application',
            'Cron' => './module/Cron',
            'Administration' => './module/Administration',
            'Blog' => './module/Blog',
        ),
         'lazy_loading' => array(
            'Administration' => array(
                'port' => '443',
                'remote_addr' => array('127.0.0.1'),
            ),
        ),
    ),
    'service_manager' => array(
        'factories'    => array(
            'ModuleManager' => 'ZFMLL\Mvc\Service\ModuleManagerFactory',
        ),
    ),
);
?>
```

2) I want load my "Cron" module only in "cli" sapi and run url in argument :

```php
<?php
return array(
    'modules' => array(
        'Application',
        'Cron',
        'Administration',
        'Blog',
    ),
    'module_listener_options' => array(
        'config_glob_paths'    => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
        'config_cache_enabled' => false,
        'cache_dir'            => 'data/cache',
        'module_paths' => array(
            'Application' => './module/Application',
            'Cron' => './module/Cron',
            'Administration' => './module/Administration',
            'Blog' => './module/Blog',
        ),
        'lazy_loading' => array(
            'Cron' => array(
                'getopt' => array('cron=s' => 'cron url'),
                'sapi' => 'cli',
            ),
        ),
    ),
    'service_manager' => array(
        'factories'    => array(
            'ModuleManager' => 'ZFMLL\Mvc\Service\ModuleManagerFactory',
        ),
    ),
);
?>
```

Filter available are : argument in command line, sapi, domain, https protocol, server port, url and remote address.

The cache key will be automatically update with the module loaded.

Benchmark
------------

Benchmark condition :
- no module cache enable
- 100 modules loading, for each test
- homepage action
- lastest master branch version (at the May 1st)

In the first case :

- 4 modules : Application, Administration, Cron and Blog
- In each other Application module, a simple class (like in the project code) :

```php
<?php

class Module implements AutoloaderProvider
{
    public function init(Manager $moduleManager)
    {
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
```

=> ZFMLL performance increases **up to 5%** on the module loading.

In the seconde case :

- 4 modules : Application, Administration, Cron and Blog
- Cron & Blog have minimal class Module and Administration attach three listeners (listeners function are empty) :

```php
<?php

$events = $manager->events()->getSharedManager();
$events->attach('application', MvcEvent::EVENT_BOOTSTRAP, array($this, 'firstListener'), 100);
$events->attach('application', MvcEvent::EVENT_BOOTSTRAP, array($this, 'secondListener'), 100);
$events->attach('Zend\ModuleManager\ModuleManager', 'loadModules.post', array($this, 'thirdListener'), -100);
```

=> ZFMLL performance increases **up to 55%**on the module loading.

In the third case :

- 4 modules :Application, Administration, Cron and Blog
- Administration attach the same listeners with the second case, and Blog attach two listeners (listener function are empty) :

```php
<?php

$events = $manager->events()->getSharedManager();
$events->attach('application', MvcEvent::EVENT_BOOTSTRAP, array($this, 'firstListener'), 100);
$events->attach('application', MvcEvent::EVENT_BOOTSTRAP, array($this, 'secondListener'), 100);
```

=> ZFMLL performance increases **up to 60%** on the module loading.

With a real code in the several listeners, ZFMLL can increase more of 75% performance on the module loading !
