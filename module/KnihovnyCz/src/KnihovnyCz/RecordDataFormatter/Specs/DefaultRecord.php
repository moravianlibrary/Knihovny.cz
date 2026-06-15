<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordDataFormatter\Specs;

use VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder;

/**
 * Class DefaultRecord
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDataFormatter
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class DefaultRecord extends \VuFind\RecordDataFormatter\Specs\DefaultRecord
{
    /**
     * Initialize specs.
     *
     * @return void
     */
    protected function init(): void
    {
        parent::init();
        $this->setDefaults('ziskej', [$this, 'getDefaultZiskejCoreSpecs']);
    }

    /**
     * Get default specifications for displaying data in core metadata.
     *
     * @return array
     */
    protected function getDefaultCoreSpecs(): array
    {
        return new SpecBuilder()
            ->setTemplateLine('Published in', 'getContainerTitle', 'data-containerTitle.phtml')
            ->setLine('New Title', 'getNewerTitles', null, ['recordLink' => 'title'])
            ->setLine('Previous Title', 'getPreviousTitles', null, ['recordLink' => 'title'])
            ->setMultiLine('Authors', 'getDeduplicatedAuthors', $this->getAuthorFunction())
            ->setLine('Format', 'getFormats', 'RecordHelper', ['helperMethod' => 'getFormatList'])
            ->setLine('Language', 'getLanguages')
            ->setLine('Physical Description', 'getPhysicalDescriptions')
            ->setTemplateLine('From monographic series', 'getMonographicSeries', 'data-monographic-series.phtml')
            ->setTemplateLine('Published', 'getPublicationDetails', 'data-publicationDetails.phtml')
            ->setTemplateLine('field773', 'getField773', 'data-7xx-field.phtml')
            ->setTemplateLine('field770', 'getField770', 'data-7xx-field.phtml')
            ->setTemplateLine('field772', 'getField772', 'data-7xx-field.phtml')
            ->setTemplateLine('field777', 'getField777', 'data-7xx-field.phtml')
            ->setTemplateLine('field780', 'getField780', 'data-7xx-field.phtml')
            ->setTemplateLine('field785', 'getField785', 'data-7xx-field.phtml')
            ->setLine(
                'Edition',
                'getEdition',
                null,
                ['prefix' => '<span property="bookEdition">', 'suffix' => '</span>']
            )->setTemplateLine('Series', 'getSeries', 'data-series.phtml')
            ->setTemplateLine('Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml')
            ->setTemplateLine(
                'child_records',
                'getChildRecordCount',
                'data-childRecords.phtml',
                ['allowZero' => false]
            )->setTemplateLine('Cast', 'getCast382', 'data-cast.phtml')
            ->setLine('Item Description', 'getGeneralNotes')
            ->setLine('Publication Frequency', 'getPublicationFrequency')
            ->setLine('Playing Time', 'getPlayingTimes')
            ->setLine('System Details Note', 'getSystemDetails')
            ->setLine('Audience', 'getTargetAudienceNotes')
            ->setLine('Awards', 'getAwards')
            ->setLine('Production Credits', 'getProductionCredits')
            ->setLine('Bibliography', 'getBibliographyNotes')
            ->setLine('ISBN', 'getISBNs')
            ->setLine('Scale', 'getScales')
            ->setLine('MPT', 'getMpts')
            ->setLine('Non-standard ISBN', 'getNonStandardISBN')
            ->setLine('ISSN', 'getISSNs')
            ->setLine('DOI', 'getCleanDOI')
            ->setLine('Related Items', 'getRelationshipNotes')
            ->setLine('Access', 'getAccessRestrictions')
            ->setLine('Finding Aid', 'getFindingAids')
            ->setLine('Publication_Place', 'getHierarchicalPlaceNames')
            ->setTemplateLine('Author Notes', true, 'data-authorNotes.phtml')
            ->setTemplateLine('Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml')
            ->setLine('Reviewed', 'getReviews')
            ->setLine('Formatted Contents Note', 'getNote505')
            ->setLine('Citation/References Note', 'getNote510')
            ->setLine('Ownership and Custodial History', 'getNote561')
            ->setLine('Binding Information', 'getNote563')
            ->setLine('Defect Note', 'getNote590')
            ->setTemplateLine(
                'digitalization_suggestion_button_text',
                true,
                'data-digitization-request.phtml',
                ['context' => ['hiddenLabel' => true]]
            )->setTemplateLine('Callnumber', 'getMarcCallNumber', 'marc-call-number.phtml')
            ->getArray();
    }

    /**
     * Get default specs for detailed view of document in Ziskej service
     *
     * @return array
     */
    protected function getDefaultZiskejCoreSpecs(): array
    {
        return new SpecBuilder()
            ->setTemplateLine('Published in', 'getContainerTitle', 'data-containerTitle.phtml')
            ->setLine('New Title', 'getNewerTitles', null, ['recordLink' => 'title'])
            ->setLine('Previous Title', 'getPreviousTitles', null, ['recordLink' => 'title'])
            ->setMultiLine('Authors', 'getDeduplicatedAuthors', $this->getAuthorFunction())
            ->setLine('Format', 'getFormats', 'RecordHelper', ['helperMethod' => 'getFormatList'])
            ->setLine('Language', 'getLanguages')
            ->setLine('Physical Description', 'getPhysicalDescriptions')
            ->setTemplateLine('From monographic series', 'getMonographicSeries', 'data-monographic-series.phtml')
            ->setTemplateLine('Published', 'getPublicationDetails', 'data-publicationDetails.phtml')
            ->setLine(
                'Edition',
                'getEdition',
                null,
                ['prefix' => '<span property="bookEdition">', 'suffix' => '</span>']
            )->setTemplateLine('Series', 'getSeries', 'data-series.phtml')
            ->setLine('Published', 'getDateSpan')
            ->setLine('Item Description', 'getGeneralNotes')
            ->setLine('Publication Frequency', 'getPublicationFrequency')
            ->setLine('Playing Time', 'getPlayingTimes')
            ->setLine('System Details Note', 'getSystemDetails')
            ->setLine('Audience', 'getTargetAudienceNotes')
            ->setLine('Awards', 'getAwards')
            ->setLine('Production Credits', 'getProductionCredits')
            ->setLine('Bibliography', 'getBibliographyNotes')
            ->setLine('ISBN', 'getISBNs')
            ->setLine('Scale', 'getScales')
            ->setLine('MPT', 'getMpts')
            ->setLine('Non-standard ISBN', 'getNonStandardISBN')
            ->setLine('ISSN', 'getISSNs')
            ->setLine('DOI', 'getCleanDOI')
            ->setLine('Related Items', 'getRelationshipNotes')
            ->setLine('Access', 'getAccessRestrictions')
            ->setLine('Finding Aid', 'getFindingAids')
            ->setLine('Publication_Place', 'getHierarchicalPlaceNames')
            ->setTemplateLine('Author Notes', true, 'data-authorNotes.phtml')
            ->setTemplateLine('Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml')
            ->getArray();
    }
}
