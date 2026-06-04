<?php

namespace KnihovnyCzConsole\Command\Util;

use KnihovnyCz\Db\Table\Resource as ResourceTable;
use KnihovnyCz\Record\Loader as RecordLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VuFind\Date\Converter;

/**
 * Class UpdateResourcesFromSolrCommand
 *
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
#[AsCommand(
    name: 'util/update_resources_from_solr',
    description: 'Update resource in database with current data from Solr'
)]
class UpdateResourcesFromSolrCommand extends Command
{
    /**
     * Constructor
     *
     * @param ResourceTable $resourceTable Table on which to expire rows
     * @param RecordLoader  $recordLoader  Record loader
     * @param Converter     $converter     Date converter
     * @param string|null   $name          The name of the command
     */
    public function __construct(
        protected ResourceTable $resourceTable,
        protected RecordLoader $recordLoader,
        protected Converter $converter,
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
        foreach ($this->resourceTable->findAll() as $resource) {
            $driver = $this->recordLoader->load(
                $resource->record_id,
                $resource->source,
                true
            );
            if (!($driver instanceof \VuFind\RecordDriver\Missing)) {
                $resource->assignMetadata($driver, $this->converter);
                $resource->save();
            }
        }
        return 0;
    }
}
