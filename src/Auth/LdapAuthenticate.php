<?php
/**
 * QueenCityCodeFactory(tm) : Web application developers (http://queencitycodefactory.com)
 * Copyright (c) Queen City Code Factory, Inc. (http://queencitycodefactory.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Queen City Code Factory, Inc. (http://queencitycodefactory.com)
 * @link          https://github.com/QueenCityCodeFactory/LDAP LDAP Plugin
 * @since         0.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Ldap\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Log\LogTrait;
use Cake\Network\Exception\InternalErrorException;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Network\Request;
use Cake\Network\Response;
use ErrorException;

/**
 * LDAP Authentication adapter for AuthComponent.
 *
 * Provides LDAP authentication support for AuthComponent. LDAP will
 * authenticate users against the specified LDAP Server
 *
 * ### Using LDAP auth
 *
 * In your controller's components array, add auth + the required config
 * ```
 *  public $components = [
 *      'Auth' => [
 *          'authenticate' => ['Ldap']
 *      ]
 *  ];
 * ```
 */
class LdapAuthenticate extends BaseAuthenticate
{

    use LogTrait;

    /**
     * LDAP Object
     *
     * @var object
     */
    private $ldapConnection;

    /**
     * Constructor
     *
     * @param \Cake\Controller\ComponentRegistry $registry The Component registry used on this request.
     * @param array $config Array of config to use.
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);

        if (!defined('LDAP_OPT_DIAGNOSTIC_MESSAGE')) {
            define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
        }

        if (isset($config['host']) && is_object($config['host']) && ($config['host'] instanceof \Closure)) {
            $config['host'] = $config['host']();
        }

        if (empty($config['host'])) {
            throw new InternalErrorException('LDAP Server not specified!');
        }

        if (empty($config['port'])) {
            $config['port'] = null;
        }

        try {
            $this->ldapConnection = ldap_connect($config['host'], $config['port']);
            ldap_set_option($this->ldapConnection, LDAP_OPT_NETWORK_TIMEOUT, 5);
        } catch (Exception $e) {
            throw new InternalErrorException('Unable to connect to specified LDAP Server(s)!');
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        set_error_handler(
            function ($errorNumber, $errorText, $errorFile, $errorLine) {
                throw new ErrorException($errorText, 0, $errorNumber, $errorFile, $errorLine);
            },
            E_ALL
        );

        try {
            ldap_unbind($this->ldapConnection);
        } catch (ErrorException $e) {
            // Do Nothing
        }
        try {
            ldap_close($this->ldapConnection);
        } catch (ErrorException $e) {
            // Do Nothing
        }

        restore_error_handler();
    }

    /**
     * Authenticate a user based on the request information.
     *
     * @param \Cake\Network\Request $request The request to authenticate with.
     * @param \Cake\Network\Response $response The response to add headers to.
     * @return mixed Either false on failure, or an array of user data on success.
     */
    public function authenticate(Request $request, Response $response)
    {
        if (!isset($request->data['username']) || !isset($request->data['password'])) {
            return false;
        }
        return $this->_findUser($request->data['username'], $request->data['password']);
    }

    /**
     * Find a user record using the username and password provided.
     *
     * @param string $username The username/identifier.
     * @param string|null $password The password
     * @return bool|array Either false on failure, or an array of user data.
     */
    protected function _findUser($username, $password = null)
    {
        if (!empty($this->_config['domain']) && !empty($username) && strpos($username, '@') === false) {
            $username .= '@' . $this->_config['domain'];
        }

        set_error_handler(
            function ($errorNumber, $errorText, $errorFile, $errorLine) {
                throw new ErrorException($errorText, 0, $errorNumber, $errorFile, $errorLine);
            },
            E_ALL
        );

        try {
            $ldapBind = ldap_bind($this->ldapConnection, $username, $password);
            if ($ldapBind === true) {
                $searchResults = ldap_search($this->ldapConnection, $this->_config['baseDN']($username, $this->_config['domain']), '(' . $this->_config['search'] . '=' . $username . ')');
                $results = ldap_get_entries($this->ldapConnection, $searchResults);
                $entry = ldap_first_entry($this->ldapConnection, $searchResults);
                return ldap_get_attributes($this->ldapConnection, $entry);
            }
        } catch (ErrorException $e) {
            $this->log($e->getMessage());
            if (ldap_get_option($this->ldapConnection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extendedError)) {
                if (!empty($extendedError)) {
                    foreach ($this->_config['errors'] as $error => $errorMessage) {
                        if (strpos($extendedError, $error) !== false) {
                            $messages[] = [
                                'message' => $errorMessage,
                                'key' => $this->_config['flash']['key'],
                                'element' => $this->_config['flash']['element'],
                                'params' => $this->_config['flash']['params'],
                            ];
                        }
                    }
                }
            }
        }
        restore_error_handler();

        if (!empty($messages)) {
            $controller = $this->_registry->getController();
            $controller->request->session()->write('Flash.' . $this->_config['flash']['key'], $messages);
        }

        return false;
    }
}
