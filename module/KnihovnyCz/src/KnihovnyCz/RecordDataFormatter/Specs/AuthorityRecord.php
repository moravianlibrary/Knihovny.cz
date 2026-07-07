<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordDataFormatter\Specs;

use VuFind\RecordDataFormatter\Specs\AbstractBase;
use VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder;

/**
 * Class AuthorityRecord
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDataFormatter
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class AuthorityRecord extends AbstractBase
{
    /**
     * Constructor
     *
     * @param array $config Config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * Initialize specs.
     *
     * @return void
     */
    protected function init(): void
    {
        $this->setDefaults('core', [$this, 'getDefaultCoreSpecs']);
    }

    /**
     * Authority record detail display specifications
     *
     * @return array
     */
    protected function getDefaultCoreSpecs(): array
    {
        return new SpecBuilder()
            ->setLine('Occupation', 'getOccupation')
            ->setTemplateLine('Pronunciation', 'getPronunciation', 'pronunciation.phtml')
            ->setLine('Alternative names', 'getAddedEntryPersonalNames')
            ->setLine('Source', 'getSource')
            ->setTemplateLine('Published also like', 'getPseudonyms', 'pseudonyms.phtml')
            ->setLine('Format', 'getFormats', 'RecordHelper', ['helperMethod' => 'getFormatList'])
            ->setTemplateLine('Publications', 'getRelatedUrls', 'publicationurls.phtml')
            ->setLine(
                'Signature',
                'getSignature',
                null,
                [
                    'itemPrefix' => '<img class="signature" src="',
                    'itemSuffix' => '">',
                ]
            )->getArray();
    }
}
