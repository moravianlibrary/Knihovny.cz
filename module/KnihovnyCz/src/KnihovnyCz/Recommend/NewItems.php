<?php

declare(strict_types=1);

namespace KnihovnyCz\Recommend;

use DateInterval;
use DatePeriod;
use DateTime;
use IntlDateFormatter;

/**
 * Class NewItems
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Recommend
 * @author   Václav Rosecký <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class NewItems implements \VuFind\Recommend\RecommendInterface
{
    protected const DATE_FORMAT_FOR_DISPLAY = 'LLLL yyyy';

    protected const START_DATE = 'first day of this month';

    protected const INTERVAL = '-1 month';

    protected const SOLR_DATE_FORMAT = 'Y-m-d\T00:00:00.000\Z';

    /**
     * Is new items active?
     *
     * @var bool
     */
    protected $active = false;

    /**
     * Translator
     *
     * @var \Laminas\Mvc\I18n\Translator
     */
    protected $translator = null;

    /**
     * Date field for new items
     *
     * @var string
     */
    protected $dateField = 'local_acq_date';

    /**
     * URL parameter name to activate recommendation
     *
     * @var string
     */
    protected $activatingParameter = 'newItems';

    /**
     * Number of intervals
     *
     * @var string
     */
    protected $intervals = 12;

    /**
     * Limit to only selected period, not to the future
     */
    protected $limitToInterval = false;

    /**
     * Selected date range filter
     *
     * @var string|null
     */
    protected $selectedDateRangeFilter = null;

    /**
     * List of date ranges
     *
     * @var array
     */
    protected $dateRanges = [];

    /**
     * NewItems constructor.
     *
     * @param \Laminas\Mvc\I18n\Translator $translator translator
     */
    public function __construct(\Laminas\Mvc\I18n\Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Store the configuration of the recommendation module.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        $options = ($settings) ? explode(':', $settings) : [];
        if (count($options) >= 1 && !empty($options[0])) {
            $this->dateField = $options[0];
        }
        if (count($options) >= 2 && !empty($options[1])) {
            $this->activatingParameter = $options[1];
        }
        if (count($options) >= 3 && !empty($options[2])) {
            $this->intervals = (int)$options[2];
        }
        if (count($options) >= 4 && !empty($options[3])) {
            $this->limitToInterval = $options[3] == 'true';
        }
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
     * Called before the Search Results object performs its main search
     * (specifically, in response to \VuFind\Search\SearchRunner::EVENT_CONFIGURED).
     * This method is responsible for setting search parameters needed by the
     * recommendation module and for reading any existing search parameters that may
     * be needed.
     *
     * @param \VuFind\Search\Base\Params $params  Search parameter object
     * @param \Laminas\Stdlib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    public function init($params, $request)
    {
        $months = $request->get($this->activatingParameter);
        $this->active = $months ?? false;
        $filters = $params->getRawFilters();
        $hasFilter = false;
        foreach ($filters as $key => $value) {
            if ($key == $this->dateField) {
                $this->selectedDateRangeFilter = $this->createFilter($value[0]);
                $this->active = true;
                $hasFilter = true;
            }
        }
        $this->initDateRanges();
        if ($this->active && !$hasFilter) {
            $this->selectedDateRangeFilter = $this->createDataRangeFilter($months);
            $params->addFilter($this->selectedDateRangeFilter);
        }
    }

    /**
     * Called after the Search Results object has performed its main search.  This
     * may be used to extract necessary information from the Search Results object
     * or to perform completely unrelated processing.
     *
     * @param \VuFind\Search\Base\Results $results Search results object
     *
     * @return void
     */
    public function process($results)
    {
    }

    /**
     * Get date ranges
     *
     * @return array
     */
    public function getDateRanges()
    {
        return $this->dateRanges;
    }

    /**
     * Get solr field for filtering by date
     *
     * @return string
     */
    public function getDateField()
    {
        return $this->dateField;
    }

    /**
     * Get selected date range filter
     *
     * @return string|null
     */
    public function getSelectedDateRangeFilter(): ?string
    {
        return $this->selectedDateRangeFilter;
    }

    /**
     * Return data range filter for given number of months back
     *
     * @param string $months number of months back
     *
     * @return array|mixed
     */
    protected function createDataRangeFilter(string $months): string
    {
        // placeholder value to avoid no results at the beginning of the month
        if ($months == 'true') {
            $dayInMonth = intval(date('d'));
            $months = ($dayInMonth >= 5) ? 1 : 2;
        }
        $index = intval($months);
        if ($index <= 1) {
            $index = 1;
        }
        $max = count($this->dateRanges);
        if ($index >= $max) {
            $index = $max;
        }
        $ranges = array_values($this->dateRanges);
        return $ranges[$index - 1];
    }

    /**
     * Initialize the data ranges
     *
     * @return void
     */
    protected function initDateRanges(): void
    {
        $formatter = new IntlDateFormatter(
            $this->translator->getLocale(),
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL
        );
        $formatter->setPattern(self::DATE_FORMAT_FOR_DISPLAY);
        $begin = new DateTime(self::START_DATE);
        $interval = DateInterval::createFromDateString(self::INTERVAL);
        $period = new DatePeriod($begin, $interval, $this->intervals);
        $previous = null;
        foreach ($period as $date) {
            $range = $this->createRange($date, $previous);
            $label = $formatter->format($date);
            $this->dateRanges[$label] = $this->createFilter($range);
            if ($this->limitToInterval) {
                $previous = $date;
            }
        }
    }

    /**
     * Create filter
     *
     * @param string $range range
     *
     * @return string
     */
    protected function createFilter($range)
    {
        return $this->dateField . ':' . $range;
    }

    /**
     * Create solr range
     *
     * @param DateTime $from from
     * @param DateTime $to   to
     *
     * @return string
     */
    protected function createRange($from, $to)
    {
        $from = $from->format(self::SOLR_DATE_FORMAT);
        $to = ($to == null) ? '*' : $to->format(self::SOLR_DATE_FORMAT);
        return "[$from TO $to]";
    }
}
