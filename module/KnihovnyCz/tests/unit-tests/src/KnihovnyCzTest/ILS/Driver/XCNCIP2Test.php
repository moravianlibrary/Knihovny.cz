<?php

/**
 * Class XCNCIP2Test
 *
 * PHP version 7
 *
 * Copyright (C) Moravian Library 2020.
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
 * @category VuFind
 * @package  KnihovnyCzTest\ILS\Driver
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCzTest\ILS\Driver;

use InvalidArgumentException;
use KnihovnyCz\ILS\Driver\XCNCIP2;
use Laminas\Http\Response as HttpResponse;
use PHPUnit\Framework\ExpectationFailedException;

class XCNCIP2Test extends \VuFindTest\ILS\Driver\XCNCIP2Test
{
    /**
     * ILS driver
     *
     * @var \KnihovnyCz\ILS\Driver\XCNCIP2
     */
    protected $driver;

    protected $statusesTests = [
        [
            'file' => 'lookupItemSet/ARL.xml',
            'result' => [
                'cbvk_us_cat*0645161' => [
                    [
                        'status' => 'On Loan',
                        'location' => 'Na Sadech - dospělí',
                        'callnumber' => 'C 196.250 a',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => 'cbvk_us_cat*0645161',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Čtyři Dvory',
                        'callnumber' => 'C 196.250 ČD',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => 'cbvk_us_cat*0645161',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Rožnov',
                        'callnumber' => 'C 196.250 R',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => 'cbvk_us_cat*0645161',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Vltava',
                        'callnumber' => 'C 196.250 Va',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => 'cbvk_us_cat*0645161',
                    ],
                    [
                        'status' => 'Not Available',
                        'location' => 'Výměnný fond',
                        'callnumber' => 'C 196.250 DK',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => 'cbvk_us_cat*0645161',
                    ],
                    [
                        'status' => 'Not Available',
                        'location' => 'Výměnný fond',
                        'callnumber' => 'C 196.250 DK1',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => 'cbvk_us_cat*0645161',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Suché Vrbné',
                        'callnumber' => 'C 196.250 SV',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => 'cbvk_us_cat*0645161',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Lidická',
                        'callnumber' => 'C 196.250',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => 'cbvk_us_cat*0645161',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Na Sadech - dospělí',
                        'callnumber' => 'C 196.250 a1',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => 'cbvk_us_cat*0645161',
                    ],
                    [
                        'status' => 'Available On Shelf',
                        'location' => 'Na Sadech - dospělí',
                        'callnumber' => 'C 196.250 a2',
                        'availability' => true,
                        'reserve' => 'N',
                        'id' => 'cbvk_us_cat*0645161',
                    ],
                ],
            ],
        ],
        [
            'file' => 'lookupItemSet/Clavius.xml',
            'result' =>  [
                'KN3183000000266428' => [
                    [
                        'status' => 'Available On Shelf',
                        'location' => 'Oddělení pro dospělé',
                        'callnumber' => null,
                        'availability' => true,
                        'reserve' => 'N',
                        'id' => 'KN3183000000266428',
                    ],
                    [
                        'status' => 'Available On Shelf',
                        'location' => 'Pobočka Kylešovice',
                        'callnumber' => 'KY',
                        'availability' => true,
                        'reserve' => 'N',
                        'id' => 'KN3183000000266428',
                    ],
                    [
                        'status' => 'Available On Shelf',
                        'location' => 'Kateřinky sklady',
                        'callnumber' => 'KASK',
                        'availability' => true,
                        'reserve' => 'N',
                        'id' => 'KN3183000000266428',
                    ],
                    [
                        'status' => 'Available On Shelf',
                        'location' => 'Sklad oddělení pro dospělé',
                        'callnumber' => 'SK',
                        'availability' => true,
                        'reserve' => 'N',
                        'id' => 'KN3183000000266428',
                    ],
                    [
                        'status' => 'Available On Shelf',
                        'location' => 'Pobočka Kateřinky',
                        'callnumber' => 'KA',
                        'availability' => true,
                        'reserve' => 'N',
                        'id' => 'KN3183000000266428',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Sklad oddělení pro dospělé',
                        'callnumber' => 'SK',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => 'KN3183000000266428',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Oddělení pro dospělé',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => 'KN3183000000266428',
                    ],
                    [
                        'status' => 'Available On Shelf',
                        'location' => 'Sklad oddělení pro dospělé',
                        'callnumber' => 'SK',
                        'availability' => true,
                        'reserve' => 'N',
                        'id' => 'KN3183000000266428',
                    ],
                ],
            ],
        ],
        [
            'file' => 'lookupItemSet/DaWinci.xml',
            'result' => [
                '780795' => [
                    [
                        'status' => 'Not Available',
                        'location' => 'T#@',
                        'callnumber' => '133709/KF',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '780795',
                    ],
                    [
                        'status' => 'Available On Shelf',
                        'location' => 'informační oddělení Karviná 7',
                        'callnumber' => 'N2525/17/KI',
                        'availability' => true,
                        'reserve' => 'N',
                        'id' => '780795',
                    ],
                    [
                        'status' => 'Not Available',
                        'location' => 'T#@',
                        'callnumber' => '90381/T#',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '780795',
                    ],
                    [
                        'status' => 'Not Available',
                        'location' => 'T#@',
                        'callnumber' => '170495/T#',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '780795',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'informační oddělení Karviná 7',
                        'callnumber' => 'N3071/17/KI',
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '780795',
                    ],
                ],
            ],
        ],
        [
            'file' => 'lookupItemSet/Konias.xml',
            'result' =>  [
                '2466144' => [
                    [
                        'status' => 'In Transit Between Library Locations',
                        'location' => 'Košíře',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '2466144',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Ústřední knihovna',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '2466144',
                    ],
                    [
                        'status' => 'Available on Shelf',
                        'location' => 'Smíchov',
                        'callnumber' => null,
                        'availability' => true,
                        'reserve' => 'N',
                        'id' => '2466144',
                    ],
                    [
                        'status' => 'In Transit Between Library Locations',
                        'location' => 'Břevnov',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '2466144',
                    ],
                ],
            ]
        ],
        [
            'file' => 'lookupItemSet/Tritius.xml',
            'result' =>  [
                '2354926' => [
                    [
                        'status' => 'On Loan',
                        'location' => 'Td - Ústřední půjčovna dosp.',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '2354926',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Bd - Moravské Předm., J.Masaryka dosp.',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '2354926',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Sd - Slezské Předm. dosp.',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '2354926',
                    ],
                    [
                        'status' => 'Available On Shelf',
                        'location' => 'Fd - Moravské Předm., Formánkova dosp.',
                        'callnumber' => null,
                        'availability' => true,
                        'reserve' => 'N',
                        'id' => '2354926',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Md - Malšovice dosp.',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '2354926',
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Td - Ústřední půjčovna dosp.',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '2354926',
                    ],
                ],
            ]
        ],
        [
            'file' => 'lookupItemSet/Verbis.xml',
            'result' => [
                '692155' => [
                    [
                        'status' => 'Circulation Status Undefined',
                        'location' => 'Ústřední knihovna',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '692155',
                        'use_unknown_message' => true,
                    ],
                    [
                        'status' => 'On Loan',
                        'location' => 'Ústřední knihovna',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '692155',
                    ],
                    [
                        'status' => 'Circulation Status Undefined',
                        'location' => 'Obvodní knihovna Jižní Svahy',
                        'callnumber' => null,
                        'availability' => false,
                        'reserve' => 'N',
                        'id' => '692155',
                        'use_unknown_message' => true,
                    ],
                    [
                        'status' => 'Available On Shelf',
                        'location' => 'Obvodní knihovna Díly',
                        'callnumber' => null,
                        'availability' => true,
                        'reserve' => 'N',
                        'id' => '692155',
                    ],
                ],
            ],
        ],
    ];

    protected $transactionsTests = [
        [
            'file' => [
                'lookupUser/ArlGood.xml',
            ],
            'result' => [
                [
                    'id' => '8071750247',
                    'item_agency_id' => null,
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '09-08-2019',
                    'title' => 'Daňová soustava. Díl 3a, Daně z příjmů fyzické osoby, právnické osoby',
                    'item_id' => '2681016402',
                    'renewable' => true,
                ],
                [
                    'id' => '8071130583',
                    'item_agency_id' => null,
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '09-08-2019',
                    'title' => 'Malé dějiny filozofie',
                    'item_id' => '2680126053',
                    'renewable' => true,
                ],
            ],
        ],
        [
            'file' => [
                'lookupUser/ArlBad.xml',
            ],
            'result' => [
                [
                    'id' => '8071750247',
                    'item_agency_id' => null,
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '09-08-2019',
                    'title' => 'Daňová soustava. Díl 3a, Daně z příjmů fyzické osoby, právnické osoby',
                    'item_id' => '2681016402',
                    'renewable' => false,
                ],
                [
                    'id' => '8071130583',
                    'item_agency_id' => null,
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '09-08-2019',
                    'title' => 'Malé dějiny filozofie',
                    'item_id' => '2680126053',
                    'renewable' => false,
                ],
            ],
        ],
        [
            'file' => [
                'lookupUser/ClaviusGood.xml',
                'LookupItem.xml',
                'LookupItem.xml',
            ],
            'result' => [
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => '17',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '08-23-2025',
                    'title' => 'Kdysi dávno',
                    'item_id' => '318300523523',
                    'renewable' => false,
                ],
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => '17',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '08-23-2025',
                    'title' => 'Bydlí s námi suchozemská želva',
                    'item_id' => '318300456456',
                    'renewable' => true,
                ],
            ],
        ],
        [
            'file' => [
                'lookupUser/ClaviusBad.xml',
                'LookupItem.xml',
                'LookupItem.xml',
            ],
            'result' => [
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => '17',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '11-17-2019',
                    'title' => 'Sharn, má naděje a láska',
                    'item_id' => '318300321322',
                    'renewable' => false,
                ],
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => '17',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '11-17-2019',
                    'title' => 'Smrt hazardního hráče',
                    'item_id' => '318300321326',
                    'renewable' => false,
                ],
            ],
        ],
        [
            'file' => [
                'lookupUser/DawinciGood.xml',
                'LookupItem.xml',
                'LookupItem.xml',
                'LookupItem.xml',
            ],
            'result' => [
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => 'ABA008',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '08-05-2025',
                    'title' => 'Testování softwaru /',
                    'item_id' => 'K0153221',
                    'renewable' => true,
                ],
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => 'ABA008',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '08-05-2025',
                    'title' => 'Anatomie : : praktický průvodce kreslení : [výtvarná obrazová příručka] /',
                    'item_id' => 'K0173487',
                    'renewable' => true,
                ],
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => 'ABA008',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '08-02-2025',
                    'title' => 'Lékařská knihovna : : časopis pro odborné knihovny a informační střediska ve zdravotnictví',
                    'item_id' => 'P220058',
                    'renewable' => true,
                ],
            ],
        ],
        [
            'file' => [
                'lookupUser/DawinciBad.xml',
                'LookupItem.xml',
                'LookupItem.xml',
            ],
            'result' => [
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => 'KAG001',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '08-06-2019',
                    'title' => '30 let muzea v Českém Těšíně : : Katalog výstavy /',
                    'item_id' => 'C1315/1/KI',
                    'renewable' => true,
                ],
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => 'KAG001',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '08-06-2019',
                    'title' => 'Od drobných polí k lánům /',
                    'item_id' => 'C1325/1/KI',
                    'renewable' => true,
                ],
            ],
        ],
        [
            'file' => [
                'lookupUser/TritiusGood.xml',
                'LookupItem.xml',
                'LookupItem.xml',
            ],
            'result' => [
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '08-17-2025',
                    'title' => 'Led Zeppelin : MoDERN iCoNS /',
                    'item_id' => '311800245840',
                    'renewable' => true,
                ],
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '08-17-2025',
                    'title' => 'Právo testy : testy k přijímacím zkouškám na právnické fakulty',
                    'item_id' => '311800274022',
                    'renewable' => false,
                ],
            ],
        ],
        [
            'file' => [
                'lookupUser/TritiusBad.xml',
                'LookupItem.xml',
                'LookupItem.xml',
            ],
            'result' => [
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '06-15-2020',
                    'title' => 'Tajemství Hrobaříků :',
                    'item_id' => '421170125990',
                    'renewable' => false,
                ],
                [
                    'id' => 'KN3183000000046386',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '06-15-2020',
                    'title' => 'Hrobaříci a Hrobaři /',
                    'item_id' => '421170125992',
                    'renewable' => false,
                ],
            ],
        ],
        [
            'file' => [
                'lookupUser/VerbisGood.xml',
                'LookupItem.xml',
                'LookupItem.xml',
                'LookupItem.xml',
                'LookupItem.xml',
                'LookupItem.xml',
                'LookupItem.xml',
            ],
            'result' => [
                [
                    'id' => '456951',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '05-16-2025',
                    'title' => 'Virtualizace',
                    'item_id' => '377700496260',
                    'renewable' => false,
                ],
                [
                    'id' => '408388',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '05-16-2025',
                    'title' => 'Cisco',
                    'item_id' => '377700456602',
                    'renewable' => false,
                ],
                [
                    'id' => '443201',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '08-02-2025',
                    'title' => 'Microsoft Windows SharePoint Services',
                    'item_id' => '377700482113',
                    'renewable' => false,
                ],
                [
                    'id' => '506890',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '12-27-2025',
                    'title' => 'Dieta pro posílení imunity',
                    'item_id' => '377700549872',
                    'renewable' => true,
                ],
                [
                    'id' => '361179',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '01-10-2020',
                    'title' => 'Automasáže',
                    'item_id' => '377700438707',
                    'renewable' => true,
                ],
                [
                    'id' => '506888',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '01-04-2020',
                    'title' => 'Klávesy',
                    'item_id' => '377700549895',
                    'renewable' => true,
                ],
            ],
        ],
        [
            'file' => [
                'lookupUser/VerbisBad.xml',
                'LookupItem.xml',
                'LookupItem.xml',
            ],
            'result' => [
                [
                    'id' => '456951',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '05-16-2017',
                    'title' => 'Virtualizace',
                    'item_id' => '377700496260',
                    'renewable' => false,
                ],
                [
                    'id' => '408388',
                    'item_agency_id' => 'Agency from lookup item',
                    'patronAgencyId' => 'Test agency',
                    'duedate' => '05-16-2017',
                    'title' => 'Cisco',
                    'item_id' => '377700456602',
                    'renewable' => false,
                ],
            ],
        ],
    ];

    protected $finesTests = [
        [
            'file' => 'lookupUser/ArlGood.xml',
            'result' => [
                [
                    'id' => '8071750247',
                    'duedate' => '',
                    'amount' => '-2000',
                    'balance' => '-2000',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '07-11-2019',
                ],
                [
                    'id' => '8071130583',
                    'duedate' => '',
                    'amount' => '-2000',
                    'balance' => '-2000',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '07-11-2019',
                ],
            ],
        ],
        [
            'file' => 'lookupUser/ArlBad.xml',
            'result' => [
                [
                    'id' => '8071750247',
                    'duedate' => '',
                    'amount' => '-2000',
                    'balance' => '-2000',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '07-11-2019',
                ],
                [
                    'id' => '8071130583',
                    'duedate' => '',
                    'amount' => '-2000',
                    'balance' => '-2000',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '07-11-2019',
                ],
            ],
        ],
        [
            'file' => 'lookupUser/ClaviusGood.xml',
            'result' => [],
        ],
        [
            'file' => 'lookupUser/ClaviusBad.xml',
            'result' => [
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-5700',
                    'balance' => '-5700',
                    'checkout' => '',
                    'fine' => 'Reminder Charge',
                    'createdate' => '06-24-2020',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-60000',
                    'balance' => '-60000',
                    'checkout' => '',
                    'fine' => 'Reminder Charge',
                    'createdate' => '07-21-2020',
                ],
            ],
        ],
        [
            'file' => 'lookupUser/DawinciGood.xml',
            'result' => [],
        ],
        [
            'file' => 'lookupUser/DawinciBad.xml',
            'result' => [
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-12000',
                    'balance' => '-12000',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '03-19-2020',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-500',
                    'balance' => '-500',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '07-07-2020',
                ],
            ],
        ],
        [
            'file' => 'lookupUser/TritiusGood.xml',
            'result' => [],
        ],
        [
            'file' => 'lookupUser/TritiusBad.xml',
            'result' => [
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-1000',
                    'balance' => '-1000',
                    'checkout' => '',
                    'fine' => 'Reminder Charge',
                    'createdate' => '11-04-2019',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-1000',
                    'balance' => '-1000',
                    'checkout' => '',
                    'fine' => 'Reminder Charge',
                    'createdate' => '12-12-2019',
                ],
            ],
        ],
        [
            'file' => 'lookupUser/VerbisGood.xml',
            'result' => [],
        ],
        [
            'file' => 'lookupUser/VerbisBad.xml',
            'result' => [
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '0',
                    'balance' => '0',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '11-09-2016',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '0',
                    'balance' => '0',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '11-14-2016',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '0',
                    'balance' => '0',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '11-14-2016',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '0',
                    'balance' => '0',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '11-24-2016',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '0',
                    'balance' => '0',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '12-27-2016',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-5500',
                    'balance' => '-5500',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '01-31-2017',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-3500',
                    'balance' => '-3500',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '02-28-2017',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-7000',
                    'balance' => '-7000',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '04-04-2017',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-14000',
                    'balance' => '-14000',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '05-09-2017',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-14000',
                    'balance' => '-14000',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '12-11-2017',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-500',
                    'balance' => '-500',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '05-29-2018',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-500',
                    'balance' => '-500',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '01-31-2019',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-500',
                    'balance' => '-500',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '11-27-2019',
                ],
                [
                    'id' => '',
                    'duedate' => '',
                    'amount' => '-500',
                    'balance' => '-500',
                    'checkout' => '',
                    'fine' => 'Service Charge',
                    'createdate' => '02-17-2020',
                ],
            ],
        ],
    ];

    protected $holdingTests = [
        [
            'file' => 'lookupItemSet/ARL.xml',
            'result' => [
                [
                    'status' => 'On Loan',
                    'location' => 'Na Sadech - dospělí',
                    'callnumber' => 'C 196.250 a',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '',
                    'bib_id' => 'cbvk_us_cat*0645161',
                    'item_agency_id' => '',
                    'duedate' => '09-14-2020',
                    'volume' => 'Bábovky',
                    'number' => '0645161_0001',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                    'collection_desc' => 'Na Sadech - dospělé - beletrie'
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Čtyři Dvory',
                    'callnumber' => 'C 196.250 ČD',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '',
                    'bib_id' => 'cbvk_us_cat*0645161',
                    'item_agency_id' => '',
                    'duedate' => '09-01-2020',
                    'volume' => 'Bábovky',
                    'number' => '0645161_0002',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Rožnov',
                    'callnumber' => 'C 196.250 R',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '',
                    'bib_id' => 'cbvk_us_cat*0645161',
                    'item_agency_id' => '',
                    'duedate' => '09-09-2020',
                    'volume' => 'Bábovky',
                    'number' => '0645161_0003',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Vltava',
                    'callnumber' => 'C 196.250 Va',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '',
                    'bib_id' => 'cbvk_us_cat*0645161',
                    'item_agency_id' => '',
                    'duedate' => '09-13-2020',
                    'volume' => 'Bábovky',
                    'number' => '0645161_0004',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Not Available',
                    'location' => 'Výměnný fond',
                    'callnumber' => 'C 196.250 DK',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '',
                    'bib_id' => 'cbvk_us_cat*0645161',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => 'Bábovky',
                    'number' => '0645161_0005',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Not Available',
                    'location' => 'Výměnný fond',
                    'callnumber' => 'C 196.250 DK1',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '',
                    'bib_id' => 'cbvk_us_cat*0645161',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => 'Bábovky',
                    'number' => '0645161_0006',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Suché Vrbné',
                    'callnumber' => 'C 196.250 SV',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '',
                    'bib_id' => 'cbvk_us_cat*0645161',
                    'item_agency_id' => '',
                    'duedate' => '12-17-2017',
                    'volume' => 'Bábovky',
                    'number' => '0645161_0007',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Lidická',
                    'callnumber' => 'C 196.250',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '',
                    'bib_id' => 'cbvk_us_cat*0645161',
                    'item_agency_id' => '',
                    'duedate' => '09-01-2020',
                    'volume' => 'Bábovky',
                    'number' => '0645161_0008',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                    'collection_desc' => 'Lidická - sklad'
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Na Sadech - dospělí',
                    'callnumber' => 'C 196.250 a1',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '',
                    'bib_id' => 'cbvk_us_cat*0645161',
                    'item_agency_id' => '',
                    'duedate' => '09-15-2020',
                    'volume' => 'Bábovky',
                    'number' => '0645161_0009',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                    'collection_desc' => 'Na Sadech - dospělé - beletrie'
                ],
                [
                    'status' => 'Available On Shelf',
                    'location' => 'Na Sadech - dospělí',
                    'callnumber' => 'C 196.250 a2',
                    'availability' => true,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '',
                    'bib_id' => 'cbvk_us_cat*0645161',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => 'Bábovky',
                    'number' => '0645161_0010',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Hold',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                    'collection_desc' => 'Na Sadech - dospělé - beletrie'
                ],
            ],
        ],
        [
            'file' => 'lookupItemSet/Clavius.xml',
            'result' =>  [
                [
                    'status' => 'Available On Shelf',
                    'location' => 'Oddělení pro dospělé',
                    'callnumber' => null,
                    'availability' => true,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '318300628006',
                    'bib_id' => 'KN3183000000266428',
                    'item_agency_id' => '17',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '318300628006',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Hold',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Available On Shelf',
                    'location' => 'Pobočka Kylešovice',
                    'callnumber' => 'KY',
                    'availability' => true,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '318300628008',
                    'bib_id' => 'KN3183000000266428',
                    'item_agency_id' => '17',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '318300628008',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Hold',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Available On Shelf',
                    'location' => 'Kateřinky sklady',
                    'callnumber' => 'KASK',
                    'availability' => true,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '318300628009',
                    'bib_id' => 'KN3183000000266428',
                    'item_agency_id' => '17',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '318300628009',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Hold',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Available On Shelf',
                    'location' => 'Sklad oddělení pro dospělé',
                    'callnumber' => 'SK',
                    'availability' => true,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '318300634873',
                    'bib_id' => 'KN3183000000266428',
                    'item_agency_id' => '17',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '318300634873',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Hold',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Available On Shelf',
                    'location' => 'Pobočka Kateřinky',
                    'callnumber' => 'KA',
                    'availability' => true,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '318300634874',
                    'bib_id' => 'KN3183000000266428',
                    'item_agency_id' => '17',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '318300634874',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Hold',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Sklad oddělení pro dospělé',
                    'callnumber' => 'SK',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '318300635334',
                    'bib_id' => 'KN3183000000266428',
                    'item_agency_id' => '17',
                    'duedate' => '12-08-2019',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '318300635334',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Oddělení pro dospělé',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '318300643795',
                    'bib_id' => 'KN3183000000266428',
                    'item_agency_id' => '17',
                    'duedate' => '07-26-2020',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '318300643795',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Available On Shelf',
                    'location' => 'Sklad oddělení pro dospělé',
                    'callnumber' => 'SK',
                    'availability' => true,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '318300663907',
                    'bib_id' => 'KN3183000000266428',
                    'item_agency_id' => '17',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '318300663907',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Hold',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
            ],
        ],
        [
            'file' => 'lookupItemSet/DaWinci.xml',
            'result' => [
                [
                    'status' => 'Not Available',
                    'location' => 'T#@',
                    'callnumber' => '133709/KF',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '133709/KF',
                    'bib_id' => '780795',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Available On Shelf',
                    'location' => 'informační oddělení Karviná 7',
                    'callnumber' => 'N2525/17/KI',
                    'availability' => true,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => 'N2525/17/KI',
                    'bib_id' => '780795',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Hold',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Not Available',
                    'location' => 'T#@',
                    'callnumber' => '90381/T#',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '90381/T#',
                    'bib_id' => '780795',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Not Available',
                    'location' => 'T#@',
                    'callnumber' => '170495/T#',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '170495/T#',
                    'bib_id' => '780795',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'informační oddělení Karviná 7',
                    'callnumber' => 'N3071/17/KI',
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => 'N3071/17/KI',
                    'bib_id' => '780795',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
            ],
        ],
        [
            'file' => 'lookupItemSet/Konias.xml',
            'result' =>  [
                [
                    'status' => 'In Transit Between Library Locations',
                    'location' => 'Košíře',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '3529346',
                    'bib_id' => '2466144',
                    'item_agency_id' => 'ABG001',
                    'duedate' => '12-29-1899',
                    'volume' => '',
                    'number' => '',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Ústřední knihovna',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '3529349',
                    'bib_id' => '2466144',
                    'item_agency_id' => 'ABG001',
                    'duedate' => '08-20-2020',
                    'volume' => '',
                    'number' => '',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                    'collection_desc' => 'Vypujcka',
                ],
                [
                    'status' => 'Available on Shelf',
                    'location' => 'Smíchov',
                    'callnumber' => null,
                    'availability' => true,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '3529360',
                    'bib_id' => '2466144',
                    'item_agency_id' => 'ABG001',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Hold',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                    'collection_desc' => 'Galerie A - fantasy, sci-fi a komiksy',
                ],
                [
                    'status' => 'In Transit Between Library Locations',
                    'location' => 'Břevnov',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '3529372',
                    'bib_id' => '2466144',
                    'item_agency_id' => 'ABG001',
                    'duedate' => '04-05-2020',
                    'volume' => '',
                    'number' => '',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => false,
                    'addLink' => false,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
            ]
        ],
        [
            'file' => 'lookupItemSet/Tritius.xml',
            'result' =>  [
                [
                    'status' => 'On Loan',
                    'location' => 'Td - Ústřední půjčovna dosp.',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '356600413263',
                    'bib_id' => '2354926',
                    'item_agency_id' => '',
                    'duedate' => '07-23-2020',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '356600413263',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Bd - Moravské Předm., J.Masaryka dosp.',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '356600413266',
                    'bib_id' => '2354926',
                    'item_agency_id' => '',
                    'duedate' => '08-20-2020',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '356600413266',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Sd - Slezské Předm. dosp.',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '356600413268',
                    'bib_id' => '2354926',
                    'item_agency_id' => '',
                    'duedate' => '07-27-2020',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '356600413268',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Available On Shelf',
                    'location' => 'Fd - Moravské Předm., Formánkova dosp.',
                    'callnumber' => null,
                    'availability' => true,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '356600413269',
                    'bib_id' => '2354926',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '356600413269',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Hold',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Md - Malšovice dosp.',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '356600413270',
                    'bib_id' => '2354926',
                    'item_agency_id' => '',
                    'duedate' => '08-17-2020',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '356600413270',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Td - Ústřední půjčovna dosp.',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '356600427645',
                    'bib_id' => '2354926',
                    'item_agency_id' => '',
                    'duedate' => '08-21-2020',
                    'volume' => '',
                    'number' => '',
                    'barcode' => '356600427645',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
            ]
        ],
        [
            'file' => 'lookupItemSet/Verbis.xml',
            'result' => [
                [
                    'status' => 'Circulation Status Undefined',
                    'location' => 'Ústřední knihovna',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '377700598332',
                    'bib_id' => '692155',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '918693',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => false,
                    'addLink' => false,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                    'use_unknown_message' => true,
                ],
                [
                    'status' => 'On Loan',
                    'location' => 'Ústřední knihovna',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '377700600764',
                    'bib_id' => '692155',
                    'item_agency_id' => '',
                    'duedate' => '08-14-2020',
                    'volume' => '',
                    'number' => '921125',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
                [
                    'status' => 'Circulation Status Undefined',
                    'location' => 'Obvodní knihovna Jižní Svahy',
                    'callnumber' => null,
                    'availability' => false,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '377700598331',
                    'bib_id' => '692155',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '918692',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => false,
                    'addLink' => false,
                    'holdtype' => 'Recall',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                    'use_unknown_message' => true,
                ],
                [
                    'status' => 'Available On Shelf',
                    'location' => 'Obvodní knihovna Díly',
                    'callnumber' => null,
                    'availability' => true,
                    'reserve' => 'N',
                    'id' => '123456',
                    'item_id' => '377700598960',
                    'bib_id' => '692155',
                    'item_agency_id' => '',
                    'duedate' => '',
                    'volume' => '',
                    'number' => '919321',
                    'barcode' => 'Unknown barcode',
                    'is_holdable' => true,
                    'addLink' => true,
                    'holdtype' => 'Hold',
                    'storageRetrievalRequest' => 'auto',
                    'addStorageRetrievalRequestLink' => 'true',
                    'eresource' => '',
                ],
            ],
        ],
    ];

    /**
     * Transaction history tests
     *
     * @var array[]
     */
    protected $transactionHistoryTests = [
        [
            'file' => [
                'lookupUserHistory/Verbis.xml',
                'LookupItem.xml',
                'LookupItem.xml',
                'LookupItem.xml',
                'LookupItem.xml',
                'LookupItem.xml',
            ],
            'result' => [
                'count' => 5,
                'transactions' => [
                    [
                        'id' => '2072',
                        'item_agency_id' => 'Agency from lookup item',
                        'patronAgencyId' => 'Test agency',
                        'title' => 'Ochrana dat v informačních systémech',
                        'item_id' => '377700220951',
                        'barcode' => '',
                        'dueDate' => '10-16-2015',
                    ],
                    [
                        'id' => '506890',
                        'item_agency_id' => 'Agency from lookup item',
                        'patronAgencyId' => 'Test agency',
                        'title' => 'Dieta pro posílení imunity',
                        'item_id' => '377700549872',
                        'barcode' => '',
                        'dueDate' => '05-24-2017',
                    ],
                    [
                        'id' => '15614',
                        'item_agency_id' => 'Agency from lookup item',
                        'patronAgencyId' => 'Test agency',
                        'title' => 'Selected stories',
                        'item_id' => '377700378073',
                        'barcode' => '',
                        'dueDate' => '03-13-2017',
                    ],
                    [
                        'id' => '146989',
                        'item_agency_id' => 'Agency from lookup item',
                        'patronAgencyId' => 'Test agency',
                        'title' => 'Přišel čtenář',
                        'item_id' => '910000000031',
                        'barcode' => '',
                        'dueDate' => '04-02-2018',
                    ],
                    [
                        'id' => '361179',
                        'item_agency_id' => 'Agency from lookup item',
                        'patronAgencyId' => 'Test agency',
                        'title' => 'Automasáže',
                        'item_id' => '377700438707',
                        'barcode' => '',
                        'dueDate' => '01-10-2020',
                    ],
                ],
            ],
        ],
    ];

    /**
     * Test definition for testGetMyHolds
     *
     * @var array[]
     */
    protected $holdsTests = [
        [
            'file' => 'lookupUser/ArlGood.xml',
            'result' => [
                [
                    'id' => 'cbvk_us_cat*0805981',
                    'title' => 'Veselí',
                    'item_id' => null,
                    'create' => '07-20-2020',
                    'expire' => '08-19-2025',
                    'position' => null,
                    'requestId' => 'cbvk_trx*13502426',
                    'location' => null,
                    'item_agency_id' => null,
                    'canceled' => false,
                    'available' => false,
                ],
                [
                    'id' => 'cbvk_us_cat*0699443',
                    'title' => 'Hana',
                    'item_id' => null,
                    'create' => '07-20-2020',
                    'expire' => '08-19-2025',
                    'position' => null,
                    'requestId' => 'cbvk_trx*13502428',
                    'location' => null,
                    'item_agency_id' => null,
                    'canceled' => false,
                    'available' => false,
                ],
                [
                    'id' => 'cbvk_us_cat*m0235131',
                    'title' => 'Psohlavci',
                    'item_id' => '2680443872',
                    'create' => '07-20-2020',
                    'expire' => '07-27-2025',
                    'position' => null,
                    'requestId' => 'cbvk_trx*13502448',
                    'location' => null,
                    'item_agency_id' => null,
                    'canceled' => false,
                    'available' => true,
                ],
            ],
        ],
    ];

    /**
     * Test definition for testGetMyHolds
     *
     * @var array[]
     */
    protected $itemStatusTests = [
        [
            'file' => 'LookupItem.xml',
            'result' => [
                'id' => 'KN3183000000046386',
                'item_id' => '123456',
                'location' => 'Středisko Opava VF',
                'availability' => false,
                'status' => 'On Loan',
                'callnumber' => 'Q',
            ],
        ],
    ];

    // No need to add more tests, upstream tests covers our needs
    protected $placeHoldTests = [];

    protected $placeStorageRetrievalRequestTests = [];

    protected $cancelHoldsTests = [];

    protected $cancelStorageRetrievalTests = [];

    protected $renewMyItemsTests = [];

    protected $loginTests = [];

    protected $profileTests = [];

    protected $storageRetrievalTests = [];

    protected $requestTests = [];

    /**
     * @var array
     */
    protected $notRenewableTransactionsTests = [];

    /**
     * @var array
     */
    protected $renewMyItemsWithDisabledRenewals = [];

    protected $patronBlocksTests = [];

    protected $accountBlocksTests = [];

    public function testGetStatuses()
    {
        $this->configureDriver();
        foreach ($this->statusesTests as $test) {
            $this->mockResponse($test['file']);
            $status = $this->driver->getStatuses(['Some Id']);
            $this->assertEquals($test['result'], $status);
        }
    }

    /**
     * Test getHolding
     *
     * @return void
     */
    public function testGetHolding()
    {
        $this->configureDriver();
        foreach ($this->holdingTests as $test) {
            $this->mockResponse($test['file']);
            $holdings = $this->driver->getHolding('123456');
            $this->assertEquals($test['result'], $holdings, 'Fixture file: ' . implode(', ', (array)$test['file']));
        }
    }

    public function testGetPickupLocations()
    {
        // Test reading pickup locations from NCIP responder
        $this->configureDriver([
            'Catalog' => [
                'url' => 'https://test.ncip.example',
                'consortium' => false,
                'agency' => ['Test agency'],
                'pickupLocationsFromNCIP' => true,
            ],
            'NCIP' => [],
        ]);
        $this->mockResponse('lookupAgency/Konias.xml');
        $locations = $this->driver->getPickUpLocations([]);
        $this->assertEquals([
            [
                'locationID' => 'ABG001|356',
                'locationDisplay' => 'Barrandov',
            ],
            [
                'locationID' => 'ABG001|298',
                'locationDisplay' => 'Bohnice',
            ]
        ], $locations);
    }

    /**
     * Test getMyTransactions
     *
     * @return void
     */
    public function testGetMyTransactionHistory()
    {
        $this->configureDriver();
        foreach ($this->transactionHistoryTests as $test) {
            $this->mockResponse($test['file']);
            $transactions = $this->driver->getMyTransactionHistory([
                'cat_username' => 'my_login',
                'cat_password' => 'my_password',
                'patronAgencyId' => 'Test agency',
                'id' => '111'
            ], ['page' => 1]);
            $this->assertEquals(
                $test['result'], $transactions, 'Fixture file: ' . implode(', ', (array)$test['file'])
            );
        }
    }

    /**
     * Test getStatusByItemId
     *
     * @return void
     */
    public function testGetStatusByItemId(): void
    {
        $this->configureDriver();
        foreach ($this->itemStatusTests as $test) {
            $this->mockResponse($test['file']);
            $status = $this->driver->getStatusByItemId('123456');
            $this->assertEquals(
                $test['result'], $status, 'Fixture file: ' . implode(', ', (array)$test['file'])
            );
        }
    }

    /**
     * Test getLookupUserHistoryRequest
     *
     * @return void
     */
    public function testGetLookupUserHistoryRequest()
    {
        $extras = [
            '<ns1:Ext><ns2:HistoryDesired><ns2:Page>' .
            '1' .
            '</ns2:Page></ns2:HistoryDesired></ns1:Ext>',
        ];
        $params = ['', '', 'Patron Agency', $extras, '217'];
        $this->configureDriver();
        $method = new \ReflectionMethod('\KnihovnyCz\ILS\Driver\XCNCIP2', 'getLookupUserRequest');
        $method->setAccessible(true);
        $request = $method->invokeArgs($this->driver, $params);
        $file = realpath(
            __DIR__ .
            '/../../../../../../tests/fixtures/xcncip2/request/' .
            'lookupUserHistory/Verbis.xml'
        );
        if ($file === false) {
            throw new ExpectationFailedException(
                sprintf(
                    "Fixture file '%s' could not be found",
                    'lookupUserHistory/Verbis.xml'
                )
            );
        }
        $expected = file_get_contents($file);
        $this->assertEquals($expected, $request);
    }

    /**
     * Test parsePage method
     *
     * @throws \ReflectionException
     * @return void
     */
    public function testParsePage()
    {
        $pageTests = [
            [
                'input' => 1,
                'result' => '1',
            ],
            [
                'input' => 5,
                'result' => '5',
            ],
            [
                'input' => '1',
                'result' => '1',
            ],
            [
                'input' => '5',
                'result' => '5',
            ],
            [
                'input' => '11.5',
                'result' => '11',
            ],
            [
                'input' => 'no_page',
                'result' => '1',
            ],
            [
                'input' => 0,
                'result' => '1',
            ],
            [
                'input' => -5,
                'result' => '1',
            ],
            [
                'input' => '0',
                'result' => '1',
            ],
            [
                'input' => '-5',
                'result' => '1',
            ],
            [
                'input' => false,
                'result' => '1',
            ],
        ];
        $this->configureDriver();
        $method = new \ReflectionMethod('\KnihovnyCz\ILS\Driver\XCNCIP2', 'parsePage');
        $method->setAccessible(true);
        foreach ($pageTests as $test) {
            $result = $method->invokeArgs($this->driver, [$test['input']]);
            $this->assertEquals($test['result'], $result, 'Bad result for input: ' . $test['input']);
        }
    }

    /**
     * Test method for isPatronBlocked
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testIsPatronBlocked(): void
    {
    }

    protected function loadResponse($filename)
    {
        $file = realpath(
            __DIR__ .
            '/../../../../../../tests/fixtures/xcncip2/response/' . $filename
        );
        if (!is_string($file) || !file_exists($file) || !is_readable($file)) {
            throw new InvalidArgumentException(
                sprintf('Unable to load fixture file: %s ', $file)
            );
        }
        $response = file_get_contents($file);
        if ($response === false) {
            throw new \Exception('Could not read file ' . $file);
        }
        return HttpResponse::fromString($response);
    }

    /**
     * Configure driver for test case
     *
     * @param array|null $config ILS driver configuration
     *
     * @return void
     */
    protected function configureDriver($config = null)
    {
        $this->driver = new XCNCIP2(new \VuFind\Date\Converter());
        $this->driver->setConfig($config ?? [
                'Catalog' => [
                    'url' => 'https://test.ncip.example',
                    'consortium' => false,
                    'agency' => 'Test agency',
                    'pickupLocationsFile' => 'XCNCIP2_locations.txt',
                ],
                'NCIP' => [],
            ]);
        $this->driver->init();
    }
}
