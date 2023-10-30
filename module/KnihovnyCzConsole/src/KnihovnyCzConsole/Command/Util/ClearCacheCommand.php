<?php

namespace KnihovnyCzConsole\Command\Util;

use GlobIterator;
use Laminas\Stdlib\ErrorHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VuFind\Cache\Manager as CacheManager;

/**
 * Class ClearCacheCommand
 *
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ClearCacheCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'util/clear_cache';

    /**
     * Cache manager
     *
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * Constructor
     *
     * @param CacheManager $cacheManager VuFind cache manager
     * @param string|null  $name         Command name
     */
    public function __construct(CacheManager $cacheManager, $name = null)
    {
        $this->cacheManager = $cacheManager;
        parent::__construct($name);
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Clear cache')
            ->setHelp(
                'Clear VuFind instance cache. Note, that VUFIND_LOCAL_DIR ' .
                'environment variable needs to be set'
            );
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
        $return = 0;
        $caches = $this->cacheManager->getCacheList();
        $baseCacheDir = $this->cacheManager->getCacheDir(false);
        foreach ($caches as $cacheName) {
            $flags = GlobIterator::SKIP_DOTS | GlobIterator::CURRENT_AS_PATHNAME;
            $cacheDir = $baseCacheDir . $cacheName . 's';
            $clearFolder = null;
            $clearFolder = function ($dir) use (&$clearFolder, $flags) {
                $it = new GlobIterator($dir . DIRECTORY_SEPARATOR . '*', $flags);
                foreach ($it as $pathname) {
                    if ($it->isDir()) {
                        $clearFolder($pathname);
                        rmdir($pathname);
                    } else {
                        // remove the file by ignoring errors if the file doesn't
                        // exist afterwards to fix a possible race condition if
                        // another process removed the file already.
                        ErrorHandler::start();
                        unlink($pathname);
                        $err = ErrorHandler::stop();
                        if ($err && file_exists($pathname)) {
                            ErrorHandler::addError(
                                $err->getSeverity(),
                                $err->getMessage(),
                                $err->getFile(),
                                $err->getLine()
                            );
                        }
                    }
                }
            };

            ErrorHandler::start();
            $clearFolder($cacheDir);
            $error = ErrorHandler::stop();
            if ($error) {
                $output->writeln('Error clearing cacheName "' . $cacheName . '"');
                $return = 1;
            } else {
                $output->writeln('Cache "' . $cacheName . '" cleared sucessfully');
            }
        }
        return $return;
    }
}
