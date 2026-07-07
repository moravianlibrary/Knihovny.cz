<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordDataFormatter\Specs;

use VuFind\RecordDataFormatter\Specs\AbstractBase;
use VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder;

/**
 * Class DictionaryRecord
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDataFormatter
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class DictionaryRecord extends AbstractBase
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
     * Dictionary record detail display specifications
     *
     * @return array
     */
    protected function getDefaultCoreSpecs(): array
    {
        return new SpecBuilder()
            ->setLine('alternative_term', 'getAlternatives')
            ->setLine('english_term', 'getEnglish')
            ->setLine('relative_term', 'getRelatives')
            ->setLine('source_term', 'getSource')
            ->setLine('term_author', 'getTermAuthors')
            ->setLine('Format', 'getFormats', 'RecordHelper', ['helperMethod' => 'getFormatList'])
            ->setTemplateLine('wiki_link', 'getWikidataLinks', 'wikidata_tdkiv_link.phtml')
            ->getArray();
    }
}
