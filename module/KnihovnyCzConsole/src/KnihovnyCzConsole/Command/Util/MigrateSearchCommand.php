<?php

namespace KnihovnyCzConsole\Command\Util;

use KnihovnyCz\Db\Table\Search as SearchTable;
use Symfony\Component\Console\Attribute\AsCommand;
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
#[AsCommand(
    name: 'util/migrate_search',
    description: 'Migrate search results'
)]
class MigrateSearchCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * Constructor
     *
     * @param SearchTable    $searchTable    Search table
     * @param ResultsManager $resultsManager Search table
     * @param string|null    $name           The name of the command
     */
    public function __construct(
        protected SearchTable $searchTable,
        protected ResultsManager $resultsManager,
        ?string $name = null
    ) {
        parent::__construct($name);
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
