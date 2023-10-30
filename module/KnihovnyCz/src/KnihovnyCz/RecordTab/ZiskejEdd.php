<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordTab;

use KnihovnyCz\Ziskej\Ziskej;

/**
 * Record tab Ziskej Edd
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejEdd extends ZiskejBase
{
    protected const TYPE = 'edd';

    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getDescription(): string
    {
        return 'tab_title_ziskej_edd';
    }

    /**
     * Is this tab active?
     *
     * @return bool
     *
     * @throws \Exception
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function isActive(): bool
    {
        return $this->isActiveLibraries();
    }

    /**
     * Get Ziskej type (MVS or EDD)
     *
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * Get ZiskejEdd class
     *
     * @return \KnihovnyCz\Ziskej\Ziskej
     */
    public function getZiskejEdd(): Ziskej
    {
        return $this->ziskej;
    }
}
