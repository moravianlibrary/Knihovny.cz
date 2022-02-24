<?php
declare(strict_types=1);

/**
 * Class GoogleTagManager
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\View\Helper\KnihovnyCz;

use Laminas\View\Helper\AbstractHelper;

/**
 * Class GoogleTagManager
 *
 * @category CPK-vufind-6
 * @package  KnihovnyCz\View\Helper\KnihovnyCz
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GoogleTagManager extends AbstractHelper
{
    /**
     * API key (false if disabled)
     *
     * @var string
     */
    protected $key;

    /**
     * Constructor
     *
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

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
    })(window, document, 'script', 'dataLayer', '{$this->key}');
JS;
    }

    /**
     * Returns GTM noscript alternative code.
     *
     * @return string
     */
    protected function getNoScript(): string
    {
        return <<<HTML
<noscript>
  <iframe src="https://www.googletagmanager.com/ns.html?id={$this->key}"
          height="0" width="0" style="display:none;visibility:hidden"></iframe>
</noscript>
HTML;
    }

    /**
     * Runs the view helper
     *
     * @return string
     */
    public function __invoke(): string
    {
        if (empty($this->key)) {
            return '';
        }
        $jsCode = $this->getRawJavascript();
        $inlineScriptHelper = $this->getView()->plugin('inlinescript');
        $inlineScript = $inlineScriptHelper(
            \Laminas\View\Helper\HeadScript::SCRIPT, $jsCode, 'SET'
        );
        $noScript = $this->getNoScript();
        return $inlineScript . $noScript;
    }
}
