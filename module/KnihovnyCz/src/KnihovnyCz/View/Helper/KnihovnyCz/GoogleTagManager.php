<?php

declare(strict_types=1);

namespace KnihovnyCz\View\Helper\KnihovnyCz;

/**
 * GoogleTagManager view helper
 *
 * @category Knihovny.cz
 * @package  View_Helpers
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GoogleTagManager extends \VuFind\View\Helper\Root\GoogleTagManager
{
    /**
     * Returns GTM code block meant for the <head> element.
     *
     * @return string
     */
    public function getHeadCode()
    {
        if (!$this->gtmContainerId) {
            return '';
        }

        $js = <<<END
            window.dataLayer = window.dataLayer || [];
            const runGtm = function runGtm() {
                if (VuFind.cookie.isCategoryAccepted('analytics')) {
                    console.log('VuFind: Google Tag Manager is enabled');
                    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                    'https://www.googletagmanager.com/gtm.js?id='+i+dl;var n=d.querySelector('[nonce]');
                    n&&j.setAttribute('nonce',n.nonce||n.getAttribute('nonce'));f.parentNode.insertBefore(j,f);
                    })(window,document,'script','dataLayer','{$this->gtmContainerId}');
                } else {
                    console.log('VuFind: Google Tag Manager is disabled');
                }
            }
            VuFind.listen('cookie-consent-changed', runGtm);
            VuFind.listen('cookie-consent-done', runGtm);
            END;

        return $js;
    }
}
