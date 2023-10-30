<?php

namespace KnihovnyCz\Validator;

use VuFind\Validator\CsrfInterface;

/**
 * Extension of Laminas\Validator\Csrf with token counting/clearing functions added.
 *
 * @category VuFind
 * @package  Validator
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class DatabaseCsrf implements CsrfInterface
{
    /**
     * Error message if validation fails
     *
     * @const string
     */
    public const NOT_SAME = 'The form submitted did not originate from the '
        . 'expected site';

    /**
     * CSRF token table
     *
     * @var \KnihovnyCz\Db\Table\CsrfToken
     */
    protected $csrfToken;

    /**
     * Session id
     *
     * @var string
     */
    protected $sessionId;

    /**
     * Validation messages
     *
     * @var array
     */
    protected $messages = [];

    /**
     * DatabaseCsrf constructor
     *
     * @param \KnihovnyCz\Db\Table\CsrfToken $csrfToken CSRF token table
     * @param string                         $sessionId Session ID
     */
    public function __construct($csrfToken, $sessionId)
    {
        $this->csrfToken = $csrfToken;
        $this->sessionId = $sessionId;
    }

    /**
     * Retrieve CSRF token
     *
     * If no CSRF token currently exists, or should be regenerated,
     * generates one.
     *
     * @param bool $regenerate regenerate hash, default false
     *
     * @return string
     */
    public function getHash($regenerate = false)
    {
        /**
         * Token
         *
         * @var \KnihovnyCz\Db\Row\CsrfToken
         */
        $token = $this->csrfToken->createRow();
        $token->session_id = $this->sessionId;
        $token->token = bin2hex(random_bytes(16));
        $token->created = date('Y-m-d H:i:s');
        $token->save();
        return $token->token;
    }

    /**
     * Keep only the most recent N tokens. Not implemented here.
     *
     * @param int $limit Number of tokens to keep.
     *
     * @return void
     */
    public function trimTokenList($limit)
    {
    }

    /**
     * Returns true if and only if token is valid.
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param string $value  token value
     * @param bool   $delete delete token after successful validation
     *
     * @return bool
     */
    public function isValid($value, $delete = true): bool
    {
        $this->messages = [];
        $token = $this->csrfToken->findBySessionAndHash($this->sessionId, $value);
        if ($token != null) {
            if ($delete) {
                $token->delete();
            }
            return true;
        }
        $this->messages['NOT_SAME'] = self::NOT_SAME;
        return false;
    }

    /**
     * Returns an array of messages that explain why the most recent isValid()
     * call returned false. The array keys are validation failure message
     * identifiers, and the array values are the corresponding human-readable
     * message strings.
     *
     * If isValid() was never called or if the most recent isValid() call
     * returned true, then this method returns an empty array.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
