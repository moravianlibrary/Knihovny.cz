<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

/**
 * Class WayfController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class WayfController extends \VuFind\Controller\AbstractBase
{
    /**
     * Returns text response with encoded filter for CESNET DS/WAYF service
     *
     * @return \Laminas\Http\Response|\Laminas\View\Model\ViewModel
     */
    public function indexAction()
    {
        $wayfGenerator = $this->serviceLocator->get(
            \KnihovnyCz\Service\WayfFilterGenerator::class
        );
        $filter = $wayfGenerator->generate();
        /**
         * HTTP response
         *
         * @var \Laminas\Http\Response
         */
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-type', 'text/plain');
        $response->setContent($filter);
        return $response;
    }
}
