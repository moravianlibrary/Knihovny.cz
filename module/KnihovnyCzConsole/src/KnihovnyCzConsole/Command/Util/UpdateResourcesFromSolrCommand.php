<?php

namespace KnihovnyCzConsole\Command\Util;

use KnihovnyCz\Db\Table\Resource as ResourceTable;
use KnihovnyCz\Record\Loader as RecordLoader;
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
class UpdateResourcesFromSolrCommand extends Command
{
    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'util/update_resources_from_solr';

    /**
     * Help description for the command.
     *
     * @var ResourceTable
     */
    protected $resourceTable;

    /**
     * Help description for the command.
     *
     * @var RecordLoader
     */
    protected $recordLoader;

    /**
     * Help description for the command.
     *
     * @var Converter
     */
    protected $converter;

    /**
     * Constructor
     *
     * @param ResourceTable $table        Table on which to expire rows
     * @param RecordLoader  $recordLoader Record loader
     * @param Converter     $converter    Date converter
     * @param string|null   $name         The name of the command; passing null
     *                                    means it must be set in configure()
     */
    public function __construct(
        ResourceTable $table,
        RecordLoader $recordLoader,
        Converter $converter,
        $name = null
    ) {
        parent::__construct($name ?? self::$defaultName);
        $this->resourceTable = $table;
        $this->recordLoader = $recordLoader;
        $this->converter = $converter;
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
