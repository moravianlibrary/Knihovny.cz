<?php

namespace KnihovnyCzConsole\Command\Util;

use KnihovnyCz\Db\Table\Search as SearchTable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VuFind\Search\Results\PluginManager as ResultsManager;

/**
 * Class MigrateSearchCommand
 *
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class MigrateSearchCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * Search table
     *
     * @var SearchTable
     */
    protected SearchTable $searchTable;

    /**
     * Results manager
     *
     * @var PluginManager
     */
    protected ResultsManager $resultsManager;

    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'util/migrate_search';

    /**
     * Constructor
     *
     * @param SearchTable    $table         Search table
     * @param ResultsManager $pluginManager Search table
     * @param string|null    $name          The name of the command; passing null
     *                                      means it must be set in configure()
     */
    public function __construct(
        SearchTable $table,
        ResultsManager $pluginManager,
        $name = null
    ) {
        parent::__construct($name ?? self::$defaultName);
        $this->searchTable = $table;
        $this->resultsManager = $pluginManager;
    }

    /**
     * Run the command.
     *
     * @param InputInterface  $input  Input object
     * @param OutputInterface $output Output object
     *
     * @return int 0 for success
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->searchTable->getSearchesForMigration() as $search) {
            try {
                if (!$search->migrate($output)) {
                    $output->writeln('Search '
                        . $search->id . ' has no results');
                }
            } catch (\Exception $ex) {
                $output->writeln('Search '
                    . $search->id . ' could not be migrated: '
                    . $ex->getMessage());
            }
        }
        $output->writeln('Migration finished');
        foreach ($this->searchTable->getSearchesForUpdate() as $search) {
            try {
                if (!$search->update($this->resultsManager, $output)) {
                    $output->writeln('Search '
                        . $search->id . ' has no results');
                }
            } catch (\Exception $ex) {
                $output->writeln('Search '
                    . $search->id . ' could not be migrated: '
                    . $ex->getMessage());
            }
        }
        return 0;
    }
}
