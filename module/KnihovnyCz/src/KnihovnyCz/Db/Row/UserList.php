<?php

/**
 * Class UserList
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2022.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Db\Row
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Db\Row;

use VuFind\Exception\ListPermission as ListPermissionException;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;

/**
 * Class UserList
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Db\Row
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UserList extends \VuFind\Db\Row\UserList implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * List ids used in configuration
     *
     * @var array
     */
    protected array $usedLists = [];

    /**
     * Create slug identifier
     *
     * @return string
     */
    public function getSlug(): string
    {
        $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');
        $title = $transliterator->transliterate($this['title']);
        $title = str_replace(' ', '-', $title);
        return $this['id'] . '-' . urlencode($title);
    }

    /**
     * Set used lists
     *
     * @param array $usedLists Used lists ids
     *
     * @return void
     */
    public function setUsedLists(array $usedLists): void
    {
        $this->usedLists = $usedLists;
    }

    /**
     * Destroy the list.
     *
     * @param \VuFind\Db\Row\User|bool $user  Logged-in user (false if none)
     * @param bool                     $force Should we force the delete without
     * checking permissions?
     *
     * @return int The number of rows deleted.
     */
    public function delete($user = false, $force = false)
    {
        if (in_array($this->id, $this->usedLists)) {
            throw new ListPermissionException(
                $this->translator->translate('list_in_configuration')
            );
        }
        return parent::delete($user, $force);
    }
}
