<?php

declare(strict_types=1);

namespace KnihovnyCz\Form\Handler;

use Google\Service\Sheets;
use KnihovnyCz\Record\Loader;
use KnihovnyCz\View\Helper\KnihovnyCz\RecordLinker;
use Laminas\Config\Config;
use Laminas\Log\LoggerAwareInterface;
use Laminas\View\Helper\ServerUrl;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Form\Handler\HandlerInterface;
use VuFind\Log\LoggerAwareTrait;

/**
 * Class DigitalizationRequest
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Form\Handler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class DigitalizationRequest implements HandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * DigitalizationRequest constructor.
     *
     * @param Config       $config       Configuration
     * @param RecordLinker $recordLinker Record linker
     * @param Loader       $recordLoader Record loader
     * @param ServerUrl    $serverUrl    Server URL helper
     * @param string       $host         Host URL from configuration
     */
    public function __construct(
        protected Config $config,
        protected RecordLinker $recordLinker,
        protected Loader $recordLoader,
        protected ServerUrl $serverUrl,
        protected string $host
    ) {
    }

    /**
     * Get data from submitted form and process them.
     *
     * @param \VuFind\Form\Form                     $form   Submitted form
     * @param \Laminas\Mvc\Controller\Plugin\Params $params Request params
     * @param ?UserEntityInterface                  $user   Authenticated user
     *
     * @return bool
     */
    public function handle(
        \VuFind\Form\Form $form,
        \Laminas\Mvc\Controller\Plugin\Params $params,
        ?UserEntityInterface $user = null
    ): bool {
        $fields = $form->mapRequestParamsToFieldValues($params->fromPost());
        $fields = array_column($fields, 'value', 'name');
        $record = $this->recordLoader->load($fields['recordId']);
        $source = $record->getSourceId();
        $orderNumber = $this->getLastId($source) + 1;
        $link = empty($this->host)
            ? $this->serverUrl->__invoke($this->recordLinker->getUrl($record))
            : rtrim($this->host, '/') . $this->recordLinker->getUrl($record);
        $title = $record->getTitle();
        $date = date('d.m.Y');
        $reason = $fields['reason'];
        $email = $fields['email'];
        return $this->addValues([$orderNumber, $link, $title, '', $date, '', $reason, '', '', $email], $source);
    }

    /**
     * Add values to Google Sheets
     *
     * @param array  $values Values to add
     * @param string $source Institution identifier
     *
     * @return bool
     */
    protected function addValues(array $values, string $source): bool
    {
        $range = $this->getSheetName($source) . '!A2:A';
        $body = new Sheets\ValueRange(
            [
                'values' => [$values],
            ]
        );
        $params = [
            'valueInputOption' => 'USER_ENTERED',
            'insertDataOption' => 'INSERT_ROWS',
        ];

        try {
            $this->getSheetsService($source)->spreadsheets_values->append(
                $this->getSpreadsheetId($source),
                $range,
                $body,
                $params
            );
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Get id of last updated row
     *
     * @param string $source Institution identifier
     *
     * @return int
     */
    protected function getLastId(string $source): int
    {

        try {
            $data =  $this->getSheetsService($source)->spreadsheets_values->get(
                $this->getSpreadsheetId($source),
                $this->getSheetName($source) . '!A2:A'
            );
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            return 0;
        }
        return intval(max(array_column($data->getValues(), 0)));
    }

    /**
     * Get current sheet name
     *
     * @param string $source Institution identifier
     *
     * @return string
     */
    protected function getSheetName(string $source): string
    {
        return $this->config[$source]['sheetName'] ?? 'List 1';
    }

    /**
     * Get spreadsheet identifier
     *
     * @param string $source Institution identifier
     *
     * @return string
     */
    protected function getSpreadsheetId(string $source): string
    {
        return $this->config[$source]['spreadsheetId'] ?? '';
    }

    /**
     * Get Google Sheets service
     *
     * @param string $source Institution identifier
     *
     * @return Sheets
     * @throws \Google\Exception
     */
    protected function getSheetsService(string $source): Sheets
    {
        $googleApiConfig = $this->config->$source;
        $client = new \Google\Client();
        $client->setAuthConfig($googleApiConfig->authConfig ?? '');
        $client->addScope(\Google\Service\Sheets::SPREADSHEETS);
        $client->setRedirectUri($this->serverUrl->__invoke(true));
        $sheetsService = new \Google\Service\Sheets($client);
        if (isset($_GET['code'])) {
            $client->fetchAccessTokenWithAuthCode($_GET['code']);
        }
        return $sheetsService;
    }
}
