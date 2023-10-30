<?php

namespace KnihovnyCz\Search\EDS\Backend;

/**
 * EBSCO EDS API JSON parser
 *
 * @category EBSCO
 * @package  EBSCO
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class JsonListener extends \JsonStreamingParser\Listener\InMemoryListener
{
    protected const MAX_LENGTH = 65536;

    protected $longFields = [ 'Value' ];

    /**
     * Process value
     *
     * @param mixed $value value
     *
     * @return void
     */
    public function value($value): void
    {
        $key = end($this->keys);
        if (
            is_string($value) && strlen($value) > self::MAX_LENGTH
            && in_array($key, $this->longFields)
        ) {
            $value = substr($value, 0, self::MAX_LENGTH);
        }
        $this->insertValue($value);
    }
}
