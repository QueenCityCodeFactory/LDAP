# LDAP Authenticate plugin
LDAP Authenticate Plugin for CakePHP 3.x and AuthComponent.

## Requirements
* CakePHP 3.0
* php5-ldap module or
* php7.0-ldap module


## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require queencitycodefactory/ldap
```

You can also add `"queencitycodefactory/ldap" : "dev-master"` to `require` section in your application's `composer.json`.

## Usage

Include the CakeSoap library files:
```php
    use Ldap\Auth\LdapAuthenticate;
```

## Configuration:

Setup the authentication class settings

### AppController Setup:

```php
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Flash');
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
                    'host' => Configure::read('Ldap.host'),
                    'domain' => Configure::read('Ldap.domain'),
                    'baseDN' => Configure::read('Ldap.baseDN'),
                    'search' => Configure::read('Ldap.search'),
                    'errors' => Configure::read('Ldap.errors'),
                    'flash' => [
                        'key' => 'ldap',
                        'element' => 'Flash/error',
                    ]
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
     * - `domain` - The domain name to match against or auto complete so user isn't
     *    required to enter full email address
     * - `host` - The domain controller hostname. This can be a closure or a string.
     *    The closure allows you to modify the rules in the configuration without the
     *    need to modify the LDAP plugin. One host (string) should be returned when
     *    using closure.
     * - `baseDN` - The base DN for directory - Closure must be used here, the plugin
     *    is expecting a closure object to be set.
     * - `search` - The attribute to search against. Usually 'UserPrincipalName'
     * - `port` - The port to use. Default is 389 and is not required.
     * - `errors` - Array of errors where key is the error and the value is the error
     *    message. Set in session to Flash.ldap for flashing
     *
     * @link http://php.net/manual/en/function.ldap-search.php - for more info on ldap search
     */
    'Ldap' => [
        'domain' => 'example.com',
        'host' => function() {
            $hosts = ['192.168.1.13', '127.0.0.1'];
            shuffle($hosts);
            return $hosts[0];
        },
        //'host' => '127.0.0.1',
        'port' => 389,
        'search' => 'UserPrincipalName',
        'baseDN' => function($username, $domain) {
            if (strpos($username, $domain) !== false) {
                $baseDN = 'OU=example,DC=domain,DC=local';
            } else {
                $baseDN = 'CN=Users,DC=domain,DC=local';
            }
            return $baseDN;
        },
        'errors' => [
            'data 773' => 'Some error for Flash',
            'data 532' => 'Some error for Flash',
        ]
    ]
```
