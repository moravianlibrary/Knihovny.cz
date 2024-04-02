<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Psr\Container\ContainerInterface;
use VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder;
use VuFind\View\Helper\Root\RecordDataFormatterFactory
as RecordDataFormatterFactoryBase;

/**
 * Class RecordDataFormatterFactory
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class RecordDataFormatterFactory extends RecordDataFormatterFactoryBase
{
    /**
     * Create the helper.
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        /**
         * Record data formatter view helper
         *
         * @var \VuFind\View\Helper\Root\RecordDataFormatter
         */
        $helper = parent::__invoke($container, $requestedName, $options);
        $helper->setDefaults('library', [$this, 'getDefaultLibraryCoreSpecs']);
        $helper->setDefaults('dictionary', [$this, 'getDefaultDictionaryCoreSpecs']);
        $helper->setDefaults('authority', [$this, 'getDefaultAuthorityCoreSpecs']);
        $helper->setDefaults('ziskej', [$this, 'getDefaultZiskejCoreSpecs']);

        return $helper;
    }

    /**
     * Library record detail display specifications
     *
     * @return array
     */
    public function getDefaultLibraryCoreSpecs()
    {
        $fields = $this->getDefaultLibraryCoreFields();
        $spec = new SpecBuilder();
        foreach ($fields as $key => $data) {
            $function = $data['method'];
            $spec->$function(
                $key,
                $data['dataMethod'],
                $data['template'],
                [
                    'context' => [
                        'icon' => $data['icon'],
                        'heading' => $data['heading'],
                        'content-class' => $data['content-class'],
                    ],
                ]
            );
        }

        return $spec->getArray();
    }

    /**
     * Utility function for getting fields for library core metadata
     *
     * @return array
     */
    public function getDefaultLibraryCoreFields()
    {
        $fields = [];
        $setLine = function (
            $key,
            $dataMethod,
            $template = null,
            $icon = 'library-general-info',
            $heading = true,
            $contentClass = ''
        ) use (&$fields) {
            $fields[$key] = [
                    'method' => ($template === null) ? 'setLine' : 'setTemplateLine',
                    'dataMethod' => $dataMethod,
                    'template' => $template,
                    'icon' => $icon,
                    'heading' => $heading,
                    'content-class' => $contentClass,
                ];
        };

        $setLine(
            'Book search',
            'getBookSearchFilter',
            'search_in_library_link.phtml',
            'search',
            false
        );
        $setLine(
            'Address',
            'getLibraryAddress',
            null,
            'map-marker',
            false,
            'library-large'
        );
        $setLine(
            'Opening hours',
            'getLibraryHours',
            'opening_hours.phtml',
            'opening-hours'
        );
        $setLine('Additional information', 'getLibNote');
        $setLine('Additional information2', 'getLibNote2');
        $setLine(
            'Web sites',
            'getWebsites',
            'library_websites.phtml',
            'website'
        );
        $setLine('Library type', 'getType');
        $setLine('Regional library', 'getRegLibrary', 'regional_library.phtml');
        $setLine('Interlibrary loan', 'getMvs');
        $setLine('Wheelchair accessibility', 'getWheelchairAccessibility', 'wheelchair-accessibility.phtml');

        return $fields;
    }

    /**
     * Dictionary record detail display specifications
     *
     * @return array
     */
    public function getDefaultDictionaryCoreSpecs()
    {
        $spec = new SpecBuilder();

        $spec->setLine('alternative_term', 'getAlternatives');
        $spec->setLine('english_term', 'getEnglish');
        $spec->setLine('relative_term', 'getRelatives');
        $spec->setLine('source_term', 'getSource');
        $spec->setLine('term_author', 'getTermAuthors');
        $spec->setLine(
            'Format',
            'getFormats',
            'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setTemplateLine(
            'wiki_link',
            'getWikidataLinks',
            'wikidata_tdkiv_link.phtml'
        );

        return $spec->getArray();
    }

    /**
     * Authority record detail display specifications
     *
     * @return array
     */
    public function getDefaultAuthorityCoreSpecs()
    {
        $spec = new SpecBuilder();
        $spec->setLine('Occupation', 'getOccupation');
        $spec->setTemplateLine(
            'Pronunciation',
            'getPronunciation',
            'pronunciation.phtml'
        );
        $spec->setLine('Alternative names', 'getAddedEntryPersonalNames');
        $spec->setLine('Source', 'getSource');
        $spec->setTemplateLine(
            'Published also like',
            'getPseudonyms',
            'pseudonyms.phtml'
        );
        $spec->setLine(
            'Format',
            'getFormats',
            'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setTemplateLine(
            'Publications',
            'getRelatedUrls',
            'publicationurls.phtml'
        );
        $spec->setLine(
            'Signature',
            'getSignature',
            null,
            [
                'itemPrefix' => '<img class="signature" src="',
                'itemSuffix' => '">',
            ]
        );

        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in core metadata.
     *
     * @return array
     */
    public function getDefaultCoreSpecs()
    {
        $spec = new SpecBuilder();
        $spec->setTemplateLine(
            'Published in',
            'getContainerTitle',
            'data-containerTitle.phtml'
        );
        $spec->setLine(
            'New Title',
            'getNewerTitles',
            null,
            ['recordLink' => 'title']
        );
        $spec->setLine(
            'Previous Title',
            'getPreviousTitles',
            null,
            ['recordLink' => 'title']
        );
        $spec->setMultiLine(
            'Authors',
            'getDeduplicatedAuthors',
            $this->getAuthorFunction()
        );
        $spec->setLine(
            'Format',
            'getFormats',
            'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setLine('Language', 'getLanguages');
        $spec->setLine('Document range', 'getRange');
        $spec->setTemplateLine(
            'From monographic series',
            'getMonographicSeries',
            'data-monographic-series.phtml'
        );
        $spec->setTemplateLine(
            'Published',
            'getPublicationDetails',
            'data-publicationDetails.phtml'
        );
        $spec->setTemplateLine('field773', 'getField773', 'data-7xx-field.phtml');
        $spec->setTemplateLine('field770', 'getField770', 'data-7xx-field.phtml');
        $spec->setTemplateLine('field772', 'getField772', 'data-7xx-field.phtml');
        $spec->setTemplateLine('field777', 'getField777', 'data-7xx-field.phtml');
        $spec->setTemplateLine('field780', 'getField780', 'data-7xx-field.phtml');
        $spec->setTemplateLine('field785', 'getField785', 'data-7xx-field.phtml');
        $spec->setLine(
            'Edition',
            'getEdition',
            null,
            ['prefix' => '<span property="bookEdition">', 'suffix' => '</span>']
        );
        $spec->setTemplateLine('Series', 'getSeries', 'data-series.phtml');
        $spec->setTemplateLine(
            'Subjects',
            'getAllSubjectHeadings',
            'data-allSubjectHeadings.phtml'
        );
        $spec->setTemplateLine(
            'child_records',
            'getChildRecordCount',
            'data-childRecords.phtml',
            ['allowZero' => false]
        );
        $spec->setLine('Item Description', 'getGeneralNotes');
        $spec->setLine('Physical Description', 'getPhysicalDescriptions');
        $spec->setLine('Publication Frequency', 'getPublicationFrequency');
        $spec->setLine('Playing Time', 'getPlayingTimes');
        $spec->setLine('System Details Note', 'getSystemDetails');
        $spec->setLine('Audience', 'getTargetAudienceNotes');
        $spec->setLine('Awards', 'getAwards');
        $spec->setLine('Production Credits', 'getProductionCredits');
        $spec->setLine('Bibliography', 'getBibliographyNotes');
        $spec->setLine('ISBN', 'getISBNs');
        $spec->setLine('Scale', 'getScales');
        $spec->setLine('MPT', 'getMpts');
        $spec->setLine('Non-standard ISBN', 'getNonStandardISBN');
        $spec->setLine('ISSN', 'getISSNs');
        $spec->setLine('DOI', 'getCleanDOI');
        $spec->setLine('Related Items', 'getRelationshipNotes');
        $spec->setLine('Access', 'getAccessRestrictions');
        $spec->setLine('Finding Aid', 'getFindingAids');
        $spec->setLine('Publication_Place', 'getHierarchicalPlaceNames');
        /* @phpstan-ignore-next-line */
        $spec->setTemplateLine('Author Notes', true, 'data-authorNotes.phtml');
        $spec->setTemplateLine(
            'Related Items',
            'getAllRecordLinks',
            'data-allRecordLinks.phtml'
        );
        $spec->setLine('Reviewed', 'getReviews');
        $spec->setLine('Formatted Contents Note', 'getNote505');
        $spec->setLine('Citation/References Note', 'getNote510');
        $spec->setLine('Ownership and Custodial History', 'getNote561');
        $spec->setLine('Binding Information', 'getNote563');
        $spec->setLine('Defect Note', 'getNote590');

        return $spec->getArray();
    }

    /**
     * Get default specs for detailed view of document in Ziskej service
     *
     * @return array
     */
    public function getDefaultZiskejCoreSpecs()
    {
        $spec = new SpecBuilder();
        $spec->setTemplateLine(
            'Published in',
            'getContainerTitle',
            'data-containerTitle.phtml'
        );
        $spec->setLine(
            'New Title',
            'getNewerTitles',
            null,
            ['recordLink' => 'title']
        );
        $spec->setLine(
            'Previous Title',
            'getPreviousTitles',
            null,
            ['recordLink' => 'title']
        );
        $spec->setMultiLine(
            'Authors',
            'getDeduplicatedAuthors',
            $this->getAuthorFunction()
        );
        $spec->setLine(
            'Format',
            'getFormats',
            'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setLine('Language', 'getLanguages');
        $spec->setLine('Document range', 'getRange');
        $spec->setTemplateLine(
            'From monographic series',
            'getMonographicSeries',
            'data-monographic-series.phtml'
        );
        $spec->setTemplateLine(
            'Published',
            'getPublicationDetails',
            'data-publicationDetails.phtml'
        );
        $spec->setLine(
            'Edition',
            'getEdition',
            null,
            ['prefix' => '<span property="bookEdition">', 'suffix' => '</span>']
        );
        $spec->setTemplateLine('Series', 'getSeries', 'data-series.phtml');
        $spec->setLine('Published', 'getDateSpan');
        $spec->setLine('Item Description', 'getGeneralNotes');
        $spec->setLine('Physical Description', 'getPhysicalDescriptions');
        $spec->setLine('Publication Frequency', 'getPublicationFrequency');
        $spec->setLine('Playing Time', 'getPlayingTimes');
        $spec->setLine('System Details Note', 'getSystemDetails');
        $spec->setLine('Audience', 'getTargetAudienceNotes');
        $spec->setLine('Awards', 'getAwards');
        $spec->setLine('Production Credits', 'getProductionCredits');
        $spec->setLine('Bibliography', 'getBibliographyNotes');
        $spec->setLine('ISBN', 'getISBNs');
        $spec->setLine('Scale', 'getScales');
        $spec->setLine('MPT', 'getMpts');
        $spec->setLine('Non-standard ISBN', 'getNonStandardISBN');
        $spec->setLine('ISSN', 'getISSNs');
        $spec->setLine('DOI', 'getCleanDOI');
        $spec->setLine('Related Items', 'getRelationshipNotes');
        $spec->setLine('Access', 'getAccessRestrictions');
        $spec->setLine('Finding Aid', 'getFindingAids');
        $spec->setLine('Publication_Place', 'getHierarchicalPlaceNames');
        /* @phpstan-ignore-next-line */
        $spec->setTemplateLine('Author Notes', true, 'data-authorNotes.phtml');
        $spec->setTemplateLine(
            'Related Items',
            'getAllRecordLinks',
            'data-allRecordLinks.phtml'
        );
        return $spec->getArray();
    }
}
