<?php

namespace KnihovnyCz\Db\Row;

use Symfony\Component\Console\Output\OutputInterface;
use VuFind\Db\Row\Search as Base;
use VuFind\Search\Minified;
use VuFind\Search\Results\PluginManager as ResultsManager;

/**
 * Row Definition for search
 *
 * @category VuFind
 * @package  KnihovnyCz\Db\Row
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 *
 * @property int     $migrate
 */
class Search extends Base
{
    protected const FACETS = [
        'language' => [
            'facet' => 'language_facet_mv',
        ],
        'format'   => [
            'facet' => 'record_format_facet_mv',
            'values' => [
                'Book' => '0/BOOKS/',
                'Audio' => '0/AUDIO/',
                'NewspaperOrJournal' => '0/PERIODICALS/',
                'Musical Score' => '0/MUSICAL_SCORES/',
                'Photo' => '0/VISUAL_DOCUMENTS/',
            ],
        ],
        'base_txtF_mv' => [
            'facet' => 'local_base_facet_mv',
        ],
        'statuses' => [
            'facet' => 'local_view_statuses_facet_mv',
            'values' => [
                'available_online' => 'local_online_facet_mv:online',
                'available_for_eod' => 'available_for_eod',
                'free-stack' => 'free_stack',
                'free_stack' => 'free_stack',
                'absent' => 'absent',
                'present' => 'present',
            ],
        ],
        'publishDate' => [
            'facet' => 'publishDate_facet_mv',
        ],
        'publishDateFacet' => [
            'facet' => 'publishDate_facet_mv',
        ],
        'topic_facet' => [
            'facet' => 'topic',
        ],
        'geographic_facet' => [
            'facet' => 'geographic',
        ],
    ];

    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct($adapter);
    }

    /**
     * Migrate search
     *
     * @param OutputInterface $out Output
     *
     * @return void
     */
    public function migrate(OutputInterface $out)
    {
        if (!$this->migrate) {
            return;
        }
        $search = $this->getSearchObject();
        $newF = [];
        $oldF = $search->f;
        foreach ($oldF as $key => $value) {
            $mapping = self::FACETS[$key] ?? null;
            if ($mapping != null) {
                $key = $mapping['facet'];
                $values = $mapping['values'] ?? [];
                foreach ($value as $val) {
                    $newValue = $values[$val] ?? null;
                    if (!empty($values) && $newValue == null) {
                        $out->writeln('Missing mapping for: ' . $key . ':' . $val . ' for search with id='
                            . $search->id);
                    }
                    if ($newValue == null) {
                        $newValue = $val;
                    }
                    if (str_contains($newValue, ':')) {
                        [$key, $newValue] = explode(':', $newValue);
                    }
                    $newF[$key][] = $newValue;
                }
            }
        }
        $search->f = $newF;
        $this->search_object = serialize($search);
        $this->migrate = 1;
        $this->save();
        return true;
    }

    /**
     * Update search
     *
     * @param ResultsManager  $resultsManager Results manager
     * @param OutputInterface $out            Output
     *
     * @return void
     */
    public function update(
        ResultsManager $resultsManager,
        OutputInterface $out
    ) {
        try {
            $search = $this->getSearchObject();
            $results = $search->deminify($resultsManager);
            $results->getParams()->setLimit(1);
            $results->getResults();
            $results = $search->deminify($resultsManager);
            $minified = new Minified($results);
            // Keep the original search time
            $minified->i = $search->i;
            $this->search_object = serialize($minified);
            $this->normalizeSearchObject();
            $this->migrate = 0;
            $this->save();
            $oldResults = $search->r ?? null;
            $newResults = $results->getResultTotal();
            $success = ($oldResults == null)
                 || ($oldResults == 0 && $newResults == 0)
                 || ($oldResults > 0 && $newResults > 0);
            if (!$success) {
                $out->writeln('Results : ' . $oldResults . ' -> ' . $newResults);
            }
            return $success;
        } catch (\Error $err) {
            $out->writeln('Error: ' . $err->getMessage());
        }
    }
}
