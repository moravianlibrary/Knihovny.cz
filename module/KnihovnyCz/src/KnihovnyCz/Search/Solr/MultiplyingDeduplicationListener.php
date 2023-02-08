<?php
declare(strict_types=1);

/**
 * Solr deduplication (merged records) listener.
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
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
            $context = $command->getContext();
            $enabled = in_array($context, ['search', 'similar', 'retrieve']);
            if ($enabled) {
                $this->configureFilter($params);
            }
        }
        return $event;
    }

    /**
     * Get filter for limiting results
     *
     * @param \VuFindSearch\ParamBag $params Search parameters
     *
     * @return void
     */
    protected function configureFilter($params)
    {
        $fq = $params->get('fq') ?? [];
        $enabled = !($params instanceof \KnihovnyCz\Search\ParamBag &&
            !$params->isMultiplyingDeduplicationListener());
        if ($enabled && !DeduplicationHelper::hasChildFilter($params)) {
            $fq[] = DeduplicationHelper::CHILD_FILTER;
            $fl = $params->get('fl');
            if (empty($fl)) {
                $fl = $this->listOfFields;
            } else {
                $fl = $fl[0];
            }
            $newFieldList = $fl . ", parent:[subquery]";
            $params->set('fl', $newFieldList);
            $params->set(
                'parent.q',
                '{!term f=id v=$row.parent_id_str} -'
                . DeduplicationHelper::CHILD_FILTER
            );
        }
        if ($this->childFilter != null
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
        $contexts = ['search', 'similar', 'retrieve'];
        if ($this->enabled && in_array($context, $contexts)) {
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
