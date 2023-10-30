<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

/**
 * Class GoogleTagManager
 *
 * @category CPK-vufind-6
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GoogleTagManager extends \VuFind\View\Helper\Root\GoogleTagManager
{
    /**
     * Returns GTM Javascript code.
     *
     * @return string
     */
    protected function getRawJavascript(): string
    {
        return <<<JS
            (function (w, d, s, l, i) {
                  w[l] = w[l] || [];
                  w[l].push({
                    'gtm.start': new Date().getTime(),
                    event: 'gtm.js'
                  });
                  var f = d.getElementsByTagName(s)[0],
                      j = d.createElement(s),
                      dl = l != 'dataLayer' ? '&l=' + l : '';
                  j.async = true;
                  j.src = '//www.googletagmanager.com/gtm.js?id=' + i + dl;
                  var n = d.querySelector('[nonce]');
                  n && j.setAttribute('nonce', n.nonce || n.getAttribute('nonce'));
                  f.parentNode.insertBefore(j, f);
                })(window, document, 'script', 'dataLayer', '{$this->gtmContainerId}');
            JS;
    }

    /**
     * Is GTM enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return !empty($this->gtmContainerId);
    }

    /**
     * Returns GTM Javascript code.
     *
     * @return string
     */
    public function getCode(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }
        $inlineScriptHelper = $this->getView()->plugin('inlinescript');
        return $inlineScriptHelper(
            \Laminas\View\Helper\HeadScript::SCRIPT,
            $this->getRawJavascript(),
            'SET'
        )->toString();
    }

    /**
     * Returns GTM noscript alternative code.
     *
     * @return string
     */
    public function getNoScriptCode(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }
        return <<<HTML
            <noscript>
              <iframe src="https://www.googletagmanager.com/ns.html?id={$this->gtmContainerId}"
                      height="0" width="0" style="display:none;visibility:hidden"></iframe>
            </noscript>
            HTML;
    }
}
