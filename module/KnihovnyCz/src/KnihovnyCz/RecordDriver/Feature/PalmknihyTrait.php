<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordDriver\Feature;

/**
 * Trait PalmknihyTrait
 *
 * @category VuFind
 * @package  KnihovnyCz\RecordDriver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait PalmknihyTrait
{
    /**
     * Get Palmknihy document identifier
     *
     * @return string|null
     * @throws \Exception
     */
    public function getPalmknihyId(): ?string
    {
        [$source, $recordId] = explode('.', $this->getUniqueID());
        if ($source === 'palmknihy') {
            return $recordId;
        }
        return null;
    }

    /**
     * Get Palmknihy loan price
     *
     * @return int
     * @throws \Exception
     */
    public function getPalmknihyPrice(): int
    {
        $defaultPrice = 0; // Default price if no price set
        $price = $this->fields['price_int'] ?? null;
        if ($price !== null) {
            return intval($price);
        }
        $parentRecord = $this->getParentRecord();
        if ($parentRecord !== null) {
            return $parentRecord->getPalmknihyPrice();
        }
        return $defaultPrice;
    }

    /**
     * Is document a Palmknihy record?
     *
     * @return bool
     * @throws \Exception
     */
    public function isPalmknihyRecord(): bool
    {
        return (bool)$this->getPalmknihyId();
    }

    /**
     * Is current document a Palmknihy book?
     *
     * @return bool
     * @throws \Exception
     */
    public function isPalmknihyBook(): bool
    {
        return $this->isPalmknihyRecord() && $this->checkFormat('0/EBOOK/');
    }

    /**
     * Is current document a Palmknihy audiobook?
     *
     * @return bool
     * @throws \Exception
     */
    public function isPalmknihyAudioBook(): bool
    {
        return $this->isPalmknihyRecord() && $this->checkFormat('0/EAUDIOBOOK/');
    }
}
