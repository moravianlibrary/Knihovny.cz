<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;

/**
 * Class ResultsCount
 *
 * @category VuFind
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <josef.moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ResultsCount extends AbstractHelper implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * Create a javascript snippet to fetch count of results for specific query and insert it into DOM
     *
     * @param string $searchUrl Url of the original search
     * @param string $selector  The place where the result should be inserted
     *
     * @return string
     */
    public function getAjaxCode(string $searchUrl, string $selector): string
    {
        $resultsTranslation = $this->translate('results');
        $apiUrl = $this->searchUrlToApiUrl($searchUrl);
        if (empty($apiUrl)) {
            return '';
        }
        return <<<JS
                      document.addEventListener('DOMContentLoaded', async function() {
                        const response = await fetch('$apiUrl');
                        const data = await response.json();
                        if (data.status === "OK") {
                          const span = document.querySelector('$selector');
                          span.textContent = '(' + data.resultCount + ' $resultsTranslation)';
                        }
                      });
            JS;
    }

    /**
     * Helper method to make API URL from standard search URL
     *
     * @param string $searchUrl Original search URL
     *
     * @return string
     */
    protected function searchUrlToApiUrl(string $searchUrl): string
    {
        $searches = ['Search/Results', 'Libraries/Results'];
        $replaces = ['api/v1/search', 'api/v1/libraries/search'];
        $apiUrl = str_replace($searches, $replaces, $searchUrl, $count);
        $apiUrl = str_replace('&amp;', '&', $apiUrl);
        if ($count >= 1) {
            $apiUrl .= (str_contains($apiUrl, '?') ? '&' : '?') . 'limit=0';
            return $apiUrl;
        }
        return '';
    }
}
