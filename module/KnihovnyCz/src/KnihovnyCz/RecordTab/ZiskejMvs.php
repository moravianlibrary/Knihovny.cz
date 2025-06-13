<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordTab;

use KnihovnyCz\Ziskej\Ziskej;

/**
 * Record tab Ziskej MVS
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordTab
 * @author   Robert Sipek <sipek@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class ZiskejMvs extends ZiskejBase
{
    protected const TYPE = 'mvs';

    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getDescription(): string
    {
        return 'tab_title_ziskej_mvs';
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
        $isActiveLibraries = $this->isActiveLibraries();
        return $isActiveLibraries || ($this->isZiskejActive && $this->isApiDown());
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
     * Get ZiskejMvs class
     *
     * @return \KnihovnyCz\Ziskej\Ziskej
     */
    public function getZiskejMvs(): Ziskej
    {
        return $this->ziskej;
    }
}
