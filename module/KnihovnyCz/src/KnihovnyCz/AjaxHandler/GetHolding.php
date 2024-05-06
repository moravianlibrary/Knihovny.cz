<?php

namespace KnihovnyCz\AjaxHandler;

use KnihovnyCz\ILS\Logic\Holdings as HoldingsLogic;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\ILS\Connection;
use VuFind\ILS\Logic\Holds as Holds;
use VuFind\Session\Settings as SessionSettings;
use VuFind\View\Helper\Root\RecordLinker as RecordLinker;

/**
 * Class Get Holding
 *
 * @category VuFind
 * @package  KnihovnyCz\AjaxHandler
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class GetHolding extends AbstractBase implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * ILS connection
     *
     * @var Connection
     */
    protected $ils;

    /**
     * Holds logic
     *
     * @var Holds
     */
    protected $holds;

    /**
     * Record link helper
     *
     * @var RecordLinker
     */
    protected $recordLinker;

    /**
     * Holdings logic helper
     *
     * @var HoldingsLogic
     */
    protected HoldingsLogic $holdingsLogic;

    /**
     * Constructor
     *
     * @param SessionSettings $ss            Session settings
     * @param Holds           $holds         Hold logic
     * @param RecordLinker    $recordLinker  Record link
     * @param HoldingsLogic   $holdingsLogic Holdings logic
     */
    public function __construct(
        SessionSettings $ss,
        Holds $holds,
        RecordLinker $recordLinker,
        HoldingsLogic $holdingsLogic
    ) {
        $this->sessionSettings = $ss;
        $this->holds = $holds;
        $this->recordLinker = $recordLinker;
        $this->holdingsLogic = $holdingsLogic;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     * @throws \Exception
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites(); // avoid session write timing bug
        $id = $params->fromPost('id', $params->fromQuery('id', null));
        $childrenId = $params->fromPost('childrenId', $params->fromQuery('childrenId', null));
        $source = explode('.', $id)[0];
        $holding = $this->holds->getHoldings($id);
        $copy = [];
        $labels = [
            HoldingsLogic::STATUS_NOT_AVAILABLE => 'danger',
            HoldingsLogic::STATUS_AVAILABLE => 'success',
            HoldingsLogic::STATUS_TEMPORARY_NOT_AVAILABLE => 'warning',
            HoldingsLogic::STATUS_UNKNOWN => 'default',
            HoldingsLogic::STATUS_UNDECIDABLE => '',
        ];
        // ungroup holdings and set link
        foreach ($holding['holdings'] as $location => $hold) {
            foreach ($hold['items'] as $item) {
                if (isset($item['link'])) {
                    $link = $item['link'];
                    if ($childrenId != null && is_array($link)) {
                        $link['record'] = $childrenId;
                    }
                    $item['link']
                        = $this->recordLinker->getRequestUrl($link);
                }
                if (isset($item['status'])) {
                    $holdingStatus = $this->holdingsLogic->getAvailabilityByStatus(
                        $item['status']
                    );
                    $item['label'] = $labels[$holdingStatus] ?? 'default';
                    $status = $this->translateWithSource(
                        $source,
                        $item['status'],
                        'HoldingStatus'
                    );
                    $item['status'] = $status;
                }
                if (isset($item['linkText'])) {
                    $linkText = $item['linkText'];
                    if (isset($item['link'])) {
                        [$link, $anchor] = explode('#', $item['link']);
                        $item['link'] = $link . '&linkText=' . $linkText . '#' . $anchor;
                    }
                    $linkText = $this->translateWithSource(
                        $source,
                        $item['linkText'],
                        'HoldingLinkText'
                    );
                    $item['linkText'] = $linkText;
                }
                array_push($copy, $item);
            }
        }
        $response = [
            'status' => 'OK',
            'holding' => $copy,
        ];
        return $this->formatResponse($response, 200);
    }

    /**
     * Translate with ILS source and domain
     *
     * @param string $source ILS driver source identifier
     * @param string $text   Text to translation
     * @param string $domain Translation domain
     *
     * @return string
     */
    protected function translateWithSource(
        string $source,
        string $text,
        string $domain
    ): string {
        $translated = $this->translateString(
            $source . '_' . $text,
            [],
            $text,
            $domain
        );
        if ($translated == $text) {
            $translated = $this->translateString(
                $text,
                [],
                $text,
                $domain
            );
        }
        return $translated;
    }
}
