<?php

namespace KnihovnyCz\RecordTab;

use KnihovnyCz\Service\PalmknihyApiService;

/**
 * Class EVersion
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class EVersion extends \VuFind\RecordTab\AbstractBase
{
    /**
     * Constructor
     *
     * @param PalmknihyApiService $palmknihyApiService Palmknihy API service class
     */
    public function __construct(protected PalmknihyApiService $palmknihyApiService)
    {
    }

    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Electronic Version';
    }

    /**
     * Is this tab visible?
     *
     * @return bool
     */
    public function isActive()
    {
        $hasLinks = $this->getRecordDriver()->tryMethod('hasLinks');
        $isPalmknihy = $this->getRecordDriver()->tryMethod('isPalmknihyRecord');
        $palmknihyConfig = $this->getRecordDriver()->tryMethod('isPalmknihyAudioBook')
            ? $this->palmknihyApiService->getEnabledConfigForAudioBooks()
            : $this->palmknihyApiService->getEnabledConfigForBooks();

        return $hasLinks || ($isPalmknihy && !empty($palmknihyConfig));
    }
}
