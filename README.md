# LDAP Authenticate plugin
LDAP Authenicate Plugin for CakePHP 3.x and AuthComponent.

## Requirements
* CakePHP 3.0
* php5-ldap module


## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require queencitycodefactory/ldap
```

You can also add `"queencitycodefactory/ldap" : "dev-master"` to `require` section in your application's `composer.json`.

## Usage

In your app's `config/bootstrap.php` add: `Plugin::load('QueenCityCodeFactory/LDAP');`

## Configuration:

Setup the authentication class settings

### AppController Setup:

```php
    //in $components
    public $components = [
        'Auth' => [
            'QueenCityCodeFactory/LDAP.Ldap' => [
                'fields' => [
                    'username' => 'username',
                    'password' => 'password'
                ],
                'port' => Configure::read('Ldap.port'),
                'hostname' => Configure::read('Ldap.hostname'),
                'domain' => Configure::read('Ldap.domain'),
                'OU' => Configure::read('Ldap.OU'),
                'errors' => Configure::read('Ldap.errors')
            ]
        ]
    ];

    // Or in beforeFilter()
    $this->Auth->config('authenticate', [
        'QueenCityCodeFactory/LDAP.Ldap' => [
            'fields' => [
                'username' => 'username',
                'password' => 'password'
            ],
            'port' => Configure::read('Ldap.port'),
            'hostname' => Configure::read('Ldap.hostname'),
            'domain' => Configure::read('Ldap.domain'),
            'OU' => Configure::read('Ldap.OU'),
            'errors' => Configure::read('Ldap.errors')
        ]
    ]);

    // Or in initialize()
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Flash');

        // Load the LDAP servers & shuffle them - will use a random server from the list of servers
        $hosts = Configure::read('Ldap.servers');
        shuffle($hosts);

        $this->loadComponent('Auth', [
            'loginAction' => [
                'controller' => 'Users',
                'action' => 'login'
            ],
            'authError' => 'Insufficient privileges to view requested resources. Please login to continue!',
            'authenticate' => [
                'QueenCityCodeFactory/LDAP.Ldap' => [
                    'fields' => [
                        'username' => 'username',
                        'password' => 'password'
                    ],
                    'port' => Configure::read('Ldap.port'),
                    'hostname' => $hosts[0],
                    'domain' => Configure::read('Ldap.domain'),
                    'OU' => Configure::read('Ldap.OU'),
                    'errors' => Configure::read('Ldap.errors')
                ]
            ]
        ]);
    }
```

### Setting the Base LDAP settings

config/app.php:
```php

    /**
     * LDAP Configuration.
     *
     * Contains an array of settings to use for the LDAP configuration.
     *
     * ## Options
     *
     * - `domain` - The domain name to match against or auto complete so user isn't required to enter full email address
     * - `OU` - OU for login
     * - `servers` - List of LDAP servers, one server is required. Used to randomly hit domain controllers. Uses PHP array shuffle function to get a random server.
     * - `port` - The port to use. Default is 389 and is not required.
     * - `errors` - Array of errors where key is the error and the value is the error message. Set in session to Flash.ldap for flashing
     *
     * You may want to define some other method for randomizing multiple domain controllers.
     */
    'Ldap' => [
        'domain' => 'example.com',
        'OU' => 'example',
        'servers' => ['127.0.0.1', 'dc.example.com'],
        //'hostname' => '127.0.0.1'
        'port' => 389,
        'errors' => [
            'data 773' => 'Some error for Flash',
            'data 532' => 'Some error for Flash',
        ]
    ]
```
