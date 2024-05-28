<?php

declare(strict_types=1);

namespace KnihovnyCz\Search\Solr;

use Laminas\EventManager\EventInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Psr\Container\ContainerInterface;
use VuFindSearch\Backend\Solr\Backend;
use VuFindSearch\Service;

/**
 * Solr merged record handling listener.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class MultiplyingDeduplicationListener
{
    protected const CONTEXTS = ['search', 'similar'];

    /**
     * Backend.
     *
     * @var Backend
     */
    protected $backend;

    /**
     * Whether deduplication is enabled.
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Child filter to apply
     *
     * @var string
     */
    protected $childFilter = null;

    /**
     * Results facets
     *
     * @var array
     */
    protected $facets = [];

    /**
     * List of fields to return from Solr
     *
     * @var string
     */
    protected $listOfFields = '*,score';

    /**
     * Constructor.
     *
     * @param Backend            $backend        Search backend
     * @param ContainerInterface $serviceLocator Service locator
     * @param string             $searchCfg      Search config file id
     * @param string             $facetCfg       Facet config file id
     * @param string             $dataSourceCfg  Data source file id
     * @param bool               $enabled        Whether deduplication is
     * enabled
     */
    public function __construct(/* @phpstan-ignore-line */
        Backend $backend,
        ContainerInterface $serviceLocator,
        $searchCfg,
        $facetCfg,
        $dataSourceCfg = 'datasources',
        $enabled = true
    ) {
        $this->backend = $backend;
        $this->enabled = $enabled;
        $config = $serviceLocator->get(
            \VuFind\Config\PluginManager::class
        );
        $searchConfig = $config->get($searchCfg);
        if (isset($searchConfig->ChildRecordFilters)) {
            $this->childFilter = implode(
                ' AND ',
                array_values($searchConfig->ChildRecordFilters->toArray())
            );
        }
        $this->listOfFields = $searchConfig->General->default_record_fields
            ?? '*,score';
        $facetConfig = $config->get($facetCfg);
        if (($facets = $facetConfig->Results) !== null) {
            $this->facets = array_keys($facets->toArray());
        }
    }

    /**
     * Attach listener to shared event manager.
     *
     * @param SharedEventManagerInterface $manager Shared event manager
     *
     * @return void
     */
    public function attach(
        SharedEventManagerInterface $manager
    ) {
        $manager->attach(
            'VuFind\Search',
            Service::EVENT_PRE,
            [$this, 'onSearchPre']
        );
        $manager->attach(
            'VuFind\Search',
            Service::EVENT_POST,
            [$this, 'onSearchPost']
        );
    }

    /**
     * Set up filter for excluding merge children.
     *
     * @param EventInterface $event Event
     *
     * @return EventInterface
     */
    public function onSearchPre(EventInterface $event)
    {
        /**
         * Search command
         *
         * @var \VuFindSearch\Command\SearchCommand
         */
        $command = $event->getParam('command');
        if ($command->getTargetIdentifier() === $this->backend->getIdentifier()) {
            $params = $command->getSearchParameters();
            $fetchRecords = true;
            if ($command instanceof \VufindSearch\Command\SearchCommand) {
                $arguments = $command->getArguments();
                $fetchRecords = $arguments[2] > 0;
            }
            $context = $command->getContext();
            $enabled = in_array($context, self::CONTEXTS);
            if ($enabled) {
                $this->configureFilter($params, $fetchRecords);
            }
        }
        return $event;
    }

    /**
     * Get filter for limiting results
     *
     * @param \VuFindSearch\ParamBag $params       Search parameters
     * @param boolean                $fetchRecords fetch records
     *
     * @return void
     */
    protected function configureFilter($params, $fetchRecords)
    {
        $fq = $params->get('fq') ?? [];
        $facetFields = $params->get('facet.field') ?? [];
        $facetFields = array_map(
            function ($field) {
                [$field, ] = DeduplicationHelper::parseField($field);
                return $field;
            },
            $facetFields
        );
        $facets = array_intersect($this->facets, $facetFields);
        $hasFacets = !empty($facets) || $params->hasParam('json.facet');
        $switchToParentQuery = !DeduplicationHelper::hasChildFilter($params)
            && ($fetchRecords || $hasFacets);
        if ($switchToParentQuery) {
            $params->set('switchToParentQuery', true);
            $fq[] = DeduplicationHelper::CHILD_FILTER;
            $fl = $params->get('fl');
            if (empty($fl)) {
                $fl = $this->listOfFields;
            } else {
                $fl = $fl[0];
            }
            $newFieldList = $fl . ', parent:[subquery]';
            $params->set('fl', $newFieldList);
            $params->set('parent.fl', $fl);
            $params->set('parent.q', '{!term f=id v=$row.parent_id_str}');
        }
        if (
            $this->childFilter != null
            && !($params instanceof \KnihovnyCz\Search\ParamBag
            && !$params->isApplyChildFilter())
        ) {
            $fq[] = $this->childFilter;
        }
        $params->set('fq', $fq);
    }

    /**
     * Fetch appropriate dedup child
     *
     * @param EventInterface $event Event
     *
     * @return EventInterface
     */
    public function onSearchPost(EventInterface $event)
    {
        // Inject deduplication details into record objects:
        $command = $event->getParam('command');

        if ($command->getTargetIdentifier() !== $this->backend->getIdentifier()) {
            return $event;
        }
        $context = $command->getContext();
        if ($this->enabled && in_array($context, self::CONTEXTS)) {
            $this->fetchLocalRecords($event);
        }
        return $event;
    }

    /**
     * Fetch local records for all the found dedup records
     *
     * @param EventInterface $event Event
     *
     * @return void
     */
    protected function fetchLocalRecords($event)
    {
        $command = $event->getParam('command');
        $result = $command->getResult();
        foreach ($result->getRecords() as $record) {
            $data = $record->getRawData();
            if (!empty($data['parent']['docs'])) {
                $parent = $data['parent']['docs'][0];
                $data['parent_data'] = $parent;
                $data['local_ids_str_mv'] = $data['id'];
                $record->setRawData($data);
            }
        }
    }
}
