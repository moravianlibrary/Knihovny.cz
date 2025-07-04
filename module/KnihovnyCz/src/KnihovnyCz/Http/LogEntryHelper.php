<?php

namespace KnihovnyCz\Http;

/**
 * Class LogEntryHelper
 *
 * @category VuFind
 * @package  KnihovnyCz\Service
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class LogEntryHelper
{
    /**
     * Filter headers
     *
     * @param array $reqHeaders         Requested headers
     * @param array $reqPrefixedHeaders Requested prefixed headers
     * @param array $headers            Headers to filter
     *
     * @return array
     */
    public static function filterHeaders($reqHeaders, $reqPrefixedHeaders, $headers)
    {
        $result = [];
        foreach ($reqHeaders as $header) {
            $value = $headers[$header] ?? null;
            if ($value == null) {
                continue;
            }
            if (is_string($value)) {
                $result[$header] = $value;
            } elseif (is_array($value)) {
                $result[$header] = implode(',', $value);
            }
        }
        foreach ($reqPrefixedHeaders as $header) {
            foreach ($headers as $key => $value) {
                if (str_starts_with($header, $key)) {
                    if (is_string($value)) {
                        $result[$header] = $value;
                    } elseif (is_array($value)) {
                        $result[$header] = implode(',', $value);
                    }
                }
            }
        }
        return $result;
    }
}
