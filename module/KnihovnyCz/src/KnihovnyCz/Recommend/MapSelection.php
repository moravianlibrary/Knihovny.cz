<?php

declare(strict_types=1);

namespace KnihovnyCz\Recommend;

use KnihovnyCz\Geo\Parser;
use VuFindSearch\Service;

/**
 * Class MapSelection
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Recommend
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class MapSelection extends \VuFind\Recommend\MapSelection
{
    public const BOOST_PATTERN = '/Intersects\((ENVELOPE\([0-9 \\-\\.,]+\))\)/';

    public const ENVELOPE_PATTERN = '/ENVELOPE\((.*),(.*),(.*),(.*)\)/';

    public const POLYGON_PATTERN = '/POLYGON\(\((.*),(.*),(.*),(.*),(.*)\)\)/';

    /**
     * Geo parser
     *
     * @var Parser
     */
    protected $parser;

    /**
     * Is map selection active?
     *
     * @var bool
     */
    protected $active = false;

    /**
     * Boost query
     *
     * @var bool
     */
    protected $boost = true;

    /**
     * The geo solr field where coordinates are stored
     *
     * @var string
     */
    protected $geoFieldStr = null;

    /**
     * The solr field used for searching by scale
     *
     * @var string
     */
    protected $mapScaleField = 'scale_int_facet_mv';

    /**
     * Selected map scale - min and max value in array
     *
     * @var array
     */
    protected $selectedMapScale = [1, 1000000000];

    /**
     * Constructor
     *
     * @param Service $ss                  Search service
     * @param array   $basemapOptions      Basemap Options
     * @param array   $mapSelectionOptions Map Options
     * @param Parser  $parser              Geo parser
     */
    public function __construct(
        $ss,
        $basemapOptions,
        $mapSelectionOptions,
        $parser
    ) {
        parent::__construct($ss, $basemapOptions, $mapSelectionOptions);
        $this->geoFieldStr = $this->geoField . '_str';
        $this->parser = $parser;
    }

    /**
     * Fetch details from search service
     *
     * @return array
     */
    public function fetchDataFromSearchService()
    {
        return [];
    }

    /**
     * Enable recommendations?
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * SetConfig
     *
     * Store the configuration of the recommendation module.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        $options = explode(':', $settings);
        if (count($options) >= 1) {
            $this->boost = $options[0] == 'true';
        }
        if (count($options) >= 2) {
            $this->geoFieldStr = $options[1];
        }
    }

    /**
     * Called at the end of the Search Params objects' initFromRequest() method.
     * This method is responsible for setting search parameters needed by the
     * recommendation module and for reading any existing search parameters that may
     * be needed.
     *
     * @param \VuFind\Search\Base\Params $params  Search parameter object
     * @param \Laminas\StdLib\Parameters $request Parameter object representing
     *                                            user request.
     *
     * @return void
     */
    public function init($params, $request)
    {
        parent::init($params, $request);
        $this->active = $request->get('geographicSearch') ?? false;
        $from = $request->get($this->mapScaleField . 'from') ?? null;
        $to = $request->get($this->mapScaleField . 'to') ?? null;
        if (($filter = $this->getRangeFilter($from, $to)) != null) {
            $params->addFilter($filter);
            $this->active = true;
        }
        $filters = $params->getRawFilters();
        foreach ($filters as $key => $value) {
            if ($key == $this->geoField) {
                $this->active = true;
                if ($this->boost) {
                    $match = [];
                    if (preg_match(self::BOOST_PATTERN, $value[0], $match)) {
                        $value = $match[1];
                        $field = $this->geoFieldStr;
                        $params->addBoostFunction("geo_overlap('$value', $field)");
                    }
                }
            } elseif ($key == $this->mapScaleField) {
                $this->active = true;
            }
        }
    }

    /**
     * Process search result record coordinate values
     * for Leaflet mapping platform.
     *
     * @return array
     */
    public function getMapResultCoordinates()
    {
        $results = [];
        $rawCoords = $this->getSearchResultCoordinates();
        foreach ($rawCoords as $idCoords) {
            foreach ($idCoords[1] as $coord) {
                $recCoords = [];
                $recId = $idCoords[0];
                $title = $idCoords[2];
                // convert title to UTF-8
                $title = mb_convert_encoding($title, 'UTF-8');
                if (preg_match(self::ENVELOPE_PATTERN, $coord, $match)) {
                    $floats = array_map('floatval', $match);
                    $recCoords = [$floats[1], $floats[2], $floats[3], $floats[4]];
                } elseif (preg_match(self::POLYGON_PATTERN, $coord, $match)) {
                    $p0 = explode(' ', trim($match[1]));
                    $p1 = explode(' ', trim($match[2]));
                    $p2 = explode(' ', trim($match[3]));
                    $recCoords = array_map(
                        'floatval',
                        [$p0[0], $p1[0],
                        $p0[1], $p2[1]]
                    );
                }
                $results[] = [$recId, $title, $recCoords[0],
                    $recCoords[1], $recCoords[2], $recCoords[3],
                ];
            }
        }
        return $results;
    }

    /**
     * Return Solr field to use for search by map scale
     *
     * @return string
     */
    public function getMapScaleField()
    {
        return $this->mapScaleField;
    }

    /**
     * Return selected map scale
     *
     * @return array
     */
    public function getSelectedMapScale()
    {
        return $this->selectedMapScale;
    }

    /**
     * Parse range query and return filter to apply
     *
     * @param string $from from
     * @param string $to   to
     *
     * @return null|string filter to apply
     */
    protected function getRangeFilter($from, $to)
    {
        $result = $this->parser->normalizeRange($from, $to);
        if ($result != null) {
            $this->selectedMapScale = $result;
            $min = $result[0];
            $max = $result[1];
            return "scale_int_facet_mv:[$min TO $max]";
        }
    }
}
