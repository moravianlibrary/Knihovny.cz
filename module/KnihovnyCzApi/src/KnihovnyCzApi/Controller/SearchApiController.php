<?php
declare(strict_types=1);

/**
 * Class SearchApiController
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category Knihovny.cz
 * @package  KnihovnyCzApi\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCzApi\Controller;

use KnihovnyCz\ILS\Driver\MultiBackend;
use KnihovnyCz\ILS\Logic\Holdings as HoldingsLogic;
use KnihovnyCzApi\Formatter\ItemFormatter;
use Laminas\ServiceManager\ServiceLocatorInterface;
use VuFindApi\Formatter\FacetFormatter;
use VuFindApi\Formatter\RecordFormatter;

/**
 * Class SearchApiController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCzApi\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SearchApiController extends \VuFindApi\Controller\SearchApiController
{
    /**
     * Default item fields to return if a request does not define the fields
     *
     * @var array
     */
    protected $defaultItemFields = [];

    /**
     * Record formatter
     *
     * @var ItemFormatter
     */
    protected $itemFormatter;

    /**
     * Permission required for the item endpoint
     *
     * @var string
     */
    protected $itemAccessPermission = 'access.api.Item';

    /**
     * Item route uri
     *
     * @var string
     */
    protected $itemRoute = 'item';

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service manager
     * @param RecordFormatter         $rf Record formatter
     * @param FacetFormatter          $ff Facet formatter
     * @param ItemFormatter           $if Item Formatter
     */
    public function __construct(
        ServiceLocatorInterface $sm,
        RecordFormatter $rf,
        FacetFormatter $ff,
        ItemFormatter $if
    ) {
        parent::__construct($sm, $rf, $ff);
        $this->itemFormatter = $if;
        foreach ($if->getRecordFields() as $fieldName => $fieldSpec) {
            if (!empty($fieldSpec['vufind.default'])) {
                $this->defaultItemFields[] = $fieldName;
            }
        }

        // Load configurations from the search options class:
        $settings = $sm->get(\VuFind\Search\Options\PluginManager::class)
            ->get($this->searchClassId)->getAPISettings();

        // Apply all supported configurations:
        $configKeys = ['itemAccessPermission',];
        foreach ($configKeys as $key) {
            if (isset($settings[$key])) {
                $this->$key = $settings[$key];
            }
        }
    }

    /**
     * Get Swagger specification JSON fragment for services provided by the
     * controller
     *
     * @return string
     */
    public function getSwaggerSpecFragment()
    {
        $config = $this->getConfig();
        $results = $this->getResultsManager()->get($this->searchClassId);
        $options = $results->getOptions();
        $params = $results->getParams();

        $viewParams = [
            'config' => $config,
            'version' => \VuFind\Config\Version::getBuildVersion(),
            'searchTypes' => $options->getBasicHandlers(),
            'defaultSearchType' => $options->getDefaultHandler(),
            'recordFields' => $this->recordFormatter->getRecordFieldSpec(),
            'defaultFields' => $this->defaultRecordFields,
            'facetConfig' => $params->getFacetConfig(),
            'sortOptions' => $options->getSortOptions(),
            'defaultSort' => $options->getDefaultSortByHandler(),
            'recordRoute' => $this->recordRoute,
            'searchRoute' => $this->searchRoute,
            'searchIndex' => $this->searchClassId,
            'indexLabel' => $this->indexLabel,
            'modelPrefix' => $this->modelPrefix,
            'maxLimit' => $this->maxLimit,
            'defaultItemFields' => $this->defaultItemFields,
            'itemRoute' => $this->itemRoute,
            'itemFields' => $this->itemFormatter->getRecordFieldSpec(),
        ];
        $json = $this->getViewRenderer()->render(
            'searchapi/swagger',
            $viewParams
        );
        return $json;
    }

    /**
     * Record action
     *
     * @return \Laminas\Http\Response
     */
    public function recordAction()
    {
        $request = $this->getRequest()->getQuery()->toArray()
            + $this->getRequest()->getPost()->toArray();
        $uriPath = $this->getRequest()->getUri()->getPath();
        $id = $request['id'] ?? '';
        if (str_starts_with($id, 'library')
            && str_starts_with($uriPath ?? '', '/api/v1/record')
        ) {
            $url = $this->url()->fromRoute('record2Apiv1');
            $url .= str_contains($url, '?') ? '&' : '?';
            $url .= http_build_query($request);
            return $this->redirect()->toUrl($url);
        }
        return parent::recordAction();
    }

    /**
     * Item action
     *
     * @return \Laminas\Http\Response|bool
     */
    public function itemAction()
    {
        // Disable session writes
        $this->disableSessionWrites();
        $this->determineOutputMode();
        if ($result = $this->isAccessDenied($this->itemAccessPermission)) {
            return $result;
        }

        /**
         * GET and POST parameters
         *
         * @var array $params
         */
        $params = $this->getRequest()->getQuery()->toArray() + $this->getRequest()
            ->getPost()->toArray();
        if (!isset($params['id'])) {
            return $this->output([], self::STATUS_ERROR, 400, 'Missing id');
        }

        $ils = $this->getILS();
        $driver = $ils->getDriver();
        if (!$driver instanceof MultiBackend) {
            return $this->output([], self::STATUS_ERROR, 500, 'Configuration error');
        }

        /**
         * Parse input ID in this format:
         *
         * SIGLA.ITEM_ID
         *
         * Note: In case of Aleph we get also the bibId, so the ID looks like this:
         *
         * SIGLA.BIB_ID.ITEM_ID
         */
        $idParts = explode('.', $params['id']);
        $bibId = $itemId = null;
        if (count($idParts) === 3) {
            [$sigla, $bibId, $itemId] = $idParts;
            $bibId = substr($bibId, 0, 5) . '-' . substr($bibId, 5);
        } elseif (count($idParts) === 2) {
            [$sigla, $itemId] = $idParts;
        } else {
            return $this->output([], self::STATUS_ERROR, 400, 'Bad format');
        }

        $source = $driver->siglaToSource($sigla);
        if ($source === null) {
            return $this->output(
                [],
                self::STATUS_ERROR,
                400,
                'No library for this item'
            );
        }
        $itemId = $itemId !== null ? $source . '.' . $itemId : null;
        $bibId = $bibId !== null ? $source . '.' . $bibId : null;
        $status = $driver->getStatusByItemIdOrBibId($bibId, $itemId);

        if (!isset($status['status'])) {
            return $this->output(
                [],
                self::STATUS_ERROR,
                404,
                'Item information not found'
            );
        }

        $holdingsLogic = $this->serviceLocator->get(HoldingsLogic::class);

        $availability = $holdingsLogic->getAvailabilityByStatus($status['status']);
        $availabilityMapping = [
            HoldingsLogic::STATUS_NOT_AVAILABLE => 'unavailable',
            HoldingsLogic::STATUS_AVAILABLE => 'available',
            HoldingsLogic::STATUS_TEMPORARY_NOT_AVAILABLE => 'on-loan',
            HoldingsLogic::STATUS_UNKNOWN => 'unknown',
            // It is 'unknown' for backwards compatibility:
            HoldingsLogic::STATUS_UNDECIDABLE => 'unknown',
        ];

        $response = [
            'id' => $params['id'],
            'availability' => $availabilityMapping[$availability] ?? 'unknown',
            'availability_note' => $status['status'] ?? null,
            'duedate' => $status['duedate'] ?? null,
            'location' => $status['location'] ?? null,
            'ext' => [
                'opac_status' => $status['status'] ?? null,
            ],
        ];

        $fields = $this->getItemFieldList($params);

        $response = array_filter(
            $response,
            function ($key) use ($fields) {
                return in_array($key, $fields);
            },
            ARRAY_FILTER_USE_KEY
        );

        return $this->output($response, self::STATUS_OK);
    }

    /**
     * Get field list based on the request
     *
     * @param array $request Request params
     *
     * @return array
     */
    protected function getItemFieldList($request)
    {
        $fieldList = $this->defaultItemFields;
        if (isset($request['field'])
            && !empty($request['field'])
            && is_array($request['field'])
        ) {
            $fieldList = $request['field'];
        }
        if (isset($request['ext']) && !empty($request['ext'])) {
            $fieldList[] = 'ext';
        }
        return $fieldList;
    }
}
