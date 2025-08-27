<?php

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use VuFind\RecordDriver\AbstractBase as BaseRecord;
use VuFind\View\Helper\Root\RecordLinker as Base;

/**
 * RecordLinker
 *
 * @category VuFind
 * @package  KnihovnyCz\View_Helpers
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class RecordLinker extends Base
{
    /**
     * Record loader
     *
     * @var \VuFind\Record\Loader
     */
    protected $recordLoader;

    /**
     * Plugin manager
     *
     * @var array
     */
    protected $searchConfig;

    /**
     * Current language
     *
     * @var string
     */
    protected $language;

    /**
     * Constructor
     *
     * @param \VuFind\Record\Router $router       Record router
     * @param \VuFind\Record\Loader $recordLoader Record loader
     * @param array                 $searchConfig Search config
     * @param string                $language     Current language
     */
    public function __construct(
        \VuFind\Record\Router $router,
        \VuFind\Record\Loader $recordLoader,
        array $searchConfig,
        string $language
    ) {
        parent::__construct($router);
        $this->recordLoader = $recordLoader;
        $this->searchConfig = $searchConfig;
        $this->language = $language;
    }

    /**
     * Return a link to main portal
     *
     * @param BaseRecord $driver Record driver
     * representing record to link to
     *
     * @return string
     */
    public function getLinkToMainPortal($driver)
    {
        $baseUrl = $this->searchConfig['OtherPortals']['main'] ?? null;

        if ($baseUrl == null) {
            return null;
        }
        return rtrim($baseUrl, '/') . $this->getTabUrl($driver) . '&lng=' . $this->language;
    }

    /**
     * Given a source and record ID, get a URL for that record that links to local
     * record.
     *
     * @param string      $recordId    source|id pipe-delimited string
     * @param string|null $institution Institution to prefer
     *
     * @return string
     */
    public function getLinkToLocalRecord(
        string $recordId,
        ?string $institution = null
    ): string {
        $record = $this->loadRecord($recordId);
        $records = $record->tryMethod('getDeduplicatedRecords', [], []);
        if (!empty($records)) {
            $first = $records[$institution] ?? reset($records);
            $source = $record->getSourceIdentifier();
            $recordId = $source . '|' . reset($first);
        }
        return $this->getUrl($recordId);
    }

    /**
     * Load record.
     *
     * @param string $recordId source|id pipe-delimited string
     *
     * @return BaseRecord
     */
    protected function loadRecord(string $recordId): BaseRecord
    {
        [$sourceId, $recordId] = explode('|', $recordId, 2);
        return $this->recordLoader->load($recordId, $sourceId);
    }
}
