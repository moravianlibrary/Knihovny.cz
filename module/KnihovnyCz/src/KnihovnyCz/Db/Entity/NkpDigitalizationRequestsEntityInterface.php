<?php

declare(strict_types=1);

namespace KnihovnyCz\Db\Entity;

use DateTime;
use VuFind\Db\Entity\EntityInterface;

/**
 * Entity model interface for nkp_digitalization_requests.
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 */
interface NkpDigitalizationRequestsEntityInterface extends EntityInterface
{
    /**
     * Id getter
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Id setter
     *
     * @param int $id Id of entity
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function setId(int $id): NkpDigitalizationRequestsEntityInterface;

    /**
     * Cat username getter
     *
     * @return ?string
     */
    public function getCatUsername(): ?string;

    /**
     * Cat username setter
     *
     * @param ?string $catUsername Catalog username
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function setCatUsername(?string $catUsername): NkpDigitalizationRequestsEntityInterface;

    /**
     * Created date getter
     *
     * @return DateTime
     */
    public function getCreated(): DateTime;

    /**
     * Created date setter
     *
     * @param DateTime $created Date of creation
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function setCreated(DateTime $created): NkpDigitalizationRequestsEntityInterface;

    /**
     * Request data getter
     *
     * @return ?string
     */
    public function getRequestData(): ?string;

    /**
     * Request data setter
     *
     * @param ?string $requestData Request data (JSON)
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function setRequestData(?string $requestData): NkpDigitalizationRequestsEntityInterface;
}
