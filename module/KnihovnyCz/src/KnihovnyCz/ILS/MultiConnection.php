<?php

namespace KnihovnyCz\ILS;

use VuFind\Exception\ILS as ILSException;

/**
 * Catalog Connection Class
 *
 * This wrapper works with a driver class to pass information from the ILS to
 * VuFind.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 *
 * @method mixed patronLogin($username, $password) Patron login
 */
class MultiConnection extends Connection
{
    /**
     * Container for fetching AuthManager that can not be passed in constructor
     *
     * @var \Psr\Container\ContainerInterface
     */
    protected $container;

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config            $config        Configuration
     * representing the [Catalog] section of config.ini
     * @param \VuFind\ILS\Driver\PluginManager  $driverManager Driver plugin manager
     * @param \VuFind\Config\PluginManager      $configReader  Configuration loader
     * @param \Laminas\Http\Request             $request       Request object
     * @param \Psr\Container\ContainerInterface $container     Container
     */
    public function __construct(
        \Laminas\Config\Config $config,
        \VuFind\ILS\Driver\PluginManager $driverManager,
        \VuFind\Config\PluginManager $configReader,
        \Laminas\Http\Request $request = null,
        \Psr\Container\ContainerInterface $container = null
    ) {
        parent::__construct($config, $driverManager, $configReader, $request);
        $this->container = $container;
    }

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     *
     * @param array $patron The patron array from patronLogin
     * @param array $params Parameters
     *
     * @return mixed Array of the patron's transactions
     */
    public function getMyTransactions($patron, $params = [])
    {
        $results = $this->callAll('getMyTransactions', $params);
        return [
            'count' => count($results),
            'records' => $results,
        ];
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed      Array of the patron's holds
     */
    public function getMyHolds($patron)
    {
        return $this->callAll('getMyHolds');
    }

    /**
     * Get Patron Fines
     *
     * This is responsible for retrieving all fines
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's fines on success.
     */
    public function getMyFines($patron)
    {
        return $this->callAll('getMyFines');
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $patron The patron array
     *
     * @throws ILSException
     * @return array      Array of the patron's profile data on success.
     */
    public function getMyProfile($patron)
    {
        $profiles = $this->callAll('getMyProfile', [], false);
        $profile = $profiles[0] ?? [];
        foreach ($profiles as $profile) {
            if (isset($profile['expired']) && $profile['expired']) {
                $profile['expired'] = true;
            }
        }
        return $profile;
    }

    /**
     * Call ILS method for every connected user card
     *
     * @param string  $method ILS method to call
     * @param array   $params parameters
     * @param boolean $merge  merge results into one array
     *
     * @return array
     */
    protected function callAll($method, $params = [], $merge = true)
    {
        $allResults = [];
        $user = $this->getAuthManager()->getUserObject();
        if (!$user) {
            throw new \Exception('User is not logged in!');
        }
        foreach ($user->getLibraryCardsWithILS() as $card) {
            $user->cat_username = $card->cat_username;
            $user->cat_password = $card->cat_password;
            $patron = $this->patronLogin(
                $user->cat_username,
                $user->getCatPassword()
            );
            $results = $transactions = $this->__call(
                $method,
                [$patron, $params]
            );
            if ($merge) {
                if (isset($results['records'])) {
                    $results = $results['records'];
                }
                foreach ($results as $result) {
                    array_push($allResults, $result);
                }
            } else {
                array_push($allResults, $results);
            }
        }
        return $allResults;
    }

    /**
     * Get auth manager
     *
     * @return \KnihovnyCz\Auth\Manager
     */
    protected function getAuthManager()
    {
        return $this->container->get(\KnihovnyCz\Auth\Manager::class);
    }
}
