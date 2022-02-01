<?php
declare(strict_types=1);

/**
 * Class MarcReader
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2021.
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
 * @category Kniohvny.cz
 * @package  KnihovnyCz\Marc
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Marc;

/**
 * Class MarcReader
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Marc
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class MarcReader extends \VuFind\Marc\MarcReader
{
    /**
     * Set MARC record data
     *
     * @param string|array $data MARC record in MARCXML or ISO2709 format, or an
     * associative array with 'leader' and 'fields' in the internal format
     *
     * @throws \Exception
     * @return void
     */
    public function setData($data): void
    {
        try {
            parent::setData($data);
        } catch (\Exception $e) {
            $invalidRecord = str_starts_with(
                $e->getMessage(),
                'Invalid MARC record (end of field not found)'
            );
            if ($invalidRecord) {
                $this->leader = str_repeat(' ', 24);
                $this->fields = [];
            } else {
                throw $e;
            }
        }
    }
}
