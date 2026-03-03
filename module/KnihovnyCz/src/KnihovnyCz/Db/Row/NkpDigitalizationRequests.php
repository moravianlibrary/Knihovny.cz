<?php

namespace KnihovnyCz\Db\Row;

use DateTime;
use KnihovnyCz\Db\Entity\NkpDigitalizationRequestsEntityInterface;
use Laminas\Db\Adapter\Adapter;
use VuFind\Db\Row\RowGateway;

/**
 * Class NkpDigitalizationRequests
 *
 * @category VuFind
 * @package  Database
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz
 *
 * @property int    $id
 * @property string $cat_username
 * @property string $created
 * @property string $request_data
 */
class NkpDigitalizationRequests extends RowGateway implements NkpDigitalizationRequestsEntityInterface
{
    protected const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * Constructor
     *
     * @param Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('id', 'nkp_digitalization_requests', $adapter);
    }

    /**
     * Id getter
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Id setter
     *
     * @param int $id Id of entity
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function setId(int $id): NkpDigitalizationRequestsEntityInterface
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Cat username getter
     *
     * @return ?string
     */
    public function getCatUsername(): ?string
    {
        return $this->cat_username;
    }

    /**
     * Cat username setter
     *
     * @param ?string $catUsername Catalog username
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function setCatUsername(?string $catUsername): NkpDigitalizationRequestsEntityInterface
    {
        $this->cat_username = $catUsername;
        return $this;
    }

    /**
     * Created date getter
     *
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return DateTime::createFromFormat(self::DATE_FORMAT, $this->created);
    }

    /**
     * Created date setter
     *
     * @param DateTime $created Date of creation
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function setCreated(DateTime $created): NkpDigitalizationRequestsEntityInterface
    {
        $this->created = $created->format(self::DATE_FORMAT);
        return $this;
    }

    /**
     * Request data getter
     *
     * @return ?string
     */
    public function getRequestData(): ?string
    {
        return $this->request_data;
    }

    /**
     * Request data setter
     *
     * @param ?string $requestData Request data (JSON)
     *
     * @return NkpDigitalizationRequestsEntityInterface
     */
    public function setRequestData(?string $requestData): NkpDigitalizationRequestsEntityInterface
    {
        $this->request_data = $requestData;
        return $this;
    }
}
