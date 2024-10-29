<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordDriver;

use VuFind\Cache\CacheTrait;
use VuFind\View\Helper\Root\RecordLinker;

/**
 * Class Summon
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class Summon extends \VuFind\RecordDriver\Summon
{
    use CacheTrait;
    use Feature\CitaceProTrait;
    use Feature\WikidataTrait;

    /**
     * Record linker view helper
     *
     * @var RecordLinker|null
     */
    protected RecordLinker|null $recordLinker;

    /**
     * Get fulltext links
     *
     * @param RecordLinker $linker Record linker helper (optional; may be used to
     * inject record URLs into XML when appropriate).
     *
     * @return array
     */
    public function getFullTextLinks(RecordLinker $linker)
    {
        $links = [];
        if (isset($this->fields['OpenAccessLink'])) {
            $links['Open access link'] = $this->fixOpenAccessLink(
                $this->fields['OpenAccessLink'][0]
            );
        }
        return $links;
    }

    /**
     * Checks the current record if it's supported for generating OpenURLs.
     *
     * @return bool
     */
    public function supportsOpenUrl()
    {
        return true;
    }

    /**
     * Method to ensure uniform cache keys for cached VuFind objects.
     *
     * @param string|null $suffix Optional suffix that will get appended to the
     * object class name calling getCacheKey()
     *
     * @return string
     */
    protected function getCacheKey($suffix = null)
    {
        $id = str_replace(['.', ','], '_', $this->getUniqueID());
        return 'summonrecord_' . $id . '_' . $suffix;
    }

    /**
     * Fix Open access link
     *
     * @param string $url Url to fix
     *
     * @return mixed
     */
    protected function fixOpenAccessLink($url)
    {
        if (str_contains($url, '%requestingapplication%')) {
            return str_replace(
                '%requestingapplication%',
                'summon',
                $url
            );
        }
        return $url;
    }

    /**
     * Get OpenURL for CitacePro
     *
     * @param int $style Citations style id
     *
     * @return string
     */
    public function getOpenUrlLinkForCitations(?string $style = null): string
    {
        $params = $this->getOpenUrlParamsForCitation();
        return  'https://www.citacepro.com/sfx?' . http_build_query($params);
    }

    /**
     * Get params for OpenUrl for CitacePro
     *
     * @return array
     */
    protected function getOpenUrlParamsForCitation(): array
    {
        $containerTitle = $this->getContainerTitle();
        $title = $this->getTitle();
        $openUrlParams = [
            'genre' => $this->getFormatForCitacePro(),
            'date' => $this->getPublicationDates()[0] ?? '',
            'authors' => str_replace(' ', '', implode(';', $this->getPrimaryAuthors())),
            'corporation' => implode(';', $this->getCorporateAuthors()),
            'place' => implode(',', $this->getPlacesOfPublication()),
            'publisher' => implode(',', $this->getPublishers()),
            'edition' => $this->getEdition(),
            'spage' => $this->getContainerStartPage(),
            'epage' => $this->getContainerEndPage(),
            'volume' => $this->getContainerVolume(),
            'issue' => $this->getContainerIssue(),
            'doi' => $this->getCleanDOI(),
            'issn' => $this->getCleanISSN(),
            'isbn' => $this->getCleanISBN(),
            'url' => $this->getFullTextLinks($this->recordLinker)['Open access link'] ?? '',
            'pages' => $this->fields['PageCount'][0] ?? '',
            'pubform' => $this->fields['isPrint'] ? 0 : 1,
            'title' => empty($containerTitle) ? $title : $containerTitle,
            'atitle' => empty($containerTitle) ? '' : $title,
        ];
        $openUrlParams = array_filter($openUrlParams, fn ($value) => !empty($value));
        return $openUrlParams;
    }

    /**
     * Get format string for CitacePro genre parameter
     *
     * @return string
     */
    protected function getFormatForCitacePro(): string
    {
        $formats = $this->getFormats();
        $citFormat = '';
        foreach ($formats as $format) {
            $citFormat = match ($format) {
                'Journal', 'eJournal', 'Magazine', 'Newspaper' => 'journal',
                'Journal Article', 'Newspaper article', 'Magazine article', 'Book Review', 'Newsletter',
                'Trade Publication Article', 'Paper', 'Report', 'Publication Article', 'Publication',
                'Technical Report', 'Market Research', 'Play', 'Text Resource', 'Article'
                    => 'article',
                'Book', 'eBook' => 'book',
                'Book Chapter', 'Book chapter' => 'bookitem',
                //'' => 'conference',
                'Conference Proceeding' => 'proceeding',
                'Dissertation' => 'thesis',
                'Map' => 'map',
                'Web Resource', 'Electronic Resource' => 'post-weblog',
                //'' => 'webpage',
                'Reference', 'Transcript', 'Image', 'Data Set', 'Poem', 'Streaming Audio', 'Streaming Video', 'Patent',
                'Presentation', 'Standard', 'Audio Recording', 'Archival Material', 'Government Document', 'Poster',
                'Spoken Word Recording', 'Personal Narrative', 'Photograph', 'Audio Tape', 'Exam', 'Music Score',
                'Video Recording'
                    => 'misc',
                default => '',
            };
            if ($citFormat !== '') {
                break;
            }
        }
        return empty($citFormat) ? 'article' : $citFormat;
    }

    /**
     * Attach record linker view helper
     *
     * @param RecordLinker $linker Record linker view helper
     *
     * @return void
     */
    public function attachRecordLinker(RecordLinker $linker)
    {
        $this->recordLinker = $linker;
    }

    /**
     * Get the end page of the item that contains this record.
     *
     * @return string
     */
    public function getContainerEndPage()
    {
        if (isset($this->fields['EndPage'])) {
            return $this->fields['EndPage'][0];
        } elseif (
            isset($this->fields['PageCount'])
            && $this->fields['PageCount'] > 1
            && intval($this->fields['StartPage'][0] ?? 0) > 0
        ) {
            return $this->fields['StartPage'][0] + $this->fields['PageCount'][0] - 1;
        }
        return $this->getContainerStartPage();
    }
}
