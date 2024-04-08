<?php

/**
 * Class RecordStatus
 *
 * PHP version 8
 *
 * Copyright (C) Moravian Library 2023.
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
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

declare(strict_types=1);

namespace KnihovnyCz\Db\Row;

use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\DbTableAwareInterface;

/**
 * Class RecordStatus
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Db\Row
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 *
 * @property string $record_id
 * @property int    $absent_total
 * @property int    $absent_on_loan
 * @property int    $present_total
 * @property int    $present_on_loan
 */
class RecordStatus extends RowGateway implements DbTableAwareInterface
{
    use \VuFind\Db\Table\DbTableAwareTrait;

    /**
     * Constructor
     *
     * @param \Laminas\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct(\Laminas\Db\Adapter\Adapter $adapter)
    {
        parent::__construct('record_id', 'record_status', $adapter);
    }

    /**
     * Get record status as array
     *
     * @return array
     */
    public function asArray(): array
    {
        $absentAvailable = ($this->absent_total - $this->absent_on_loan);
        if ($absentAvailable < 0) {
            $absentAvailable = 0;
        }
        $presentAvailable = ($this->present_total - $this->present_on_loan);
        if ($presentAvailable < 0) {
            $presentAvailable = 0;
        }
        $available = $absentAvailable > 0 || $presentAvailable > 0;
        return [
            'id'            => $this->record_id,
            'absent_total'  => $this->absent_total,
            'absent_avail'  => $absentAvailable,
            'present_total' => $this->present_total,
            'present_avail' => $presentAvailable,
            'available'     => $available,
        ];
    }
}
