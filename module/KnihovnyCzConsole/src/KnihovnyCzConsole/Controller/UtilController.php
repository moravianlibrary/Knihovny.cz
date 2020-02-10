<?php

/**
 * Class UtilController
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
 * @package  KnihovnyCzConsole
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCzConsole;

class UtilController extends \VuFindConsole\Controller\UtilController
{

    /**
     * Harvest ebooks from Solr
     *
     * @return void
     */
    public function harvestEbooksAction()
    {
        // TODO: Which kind of widget/ContentBlock we use? DB based or list based?
        $widgetName = 'eknihy_ke_stazeni';
        $ebooks     = $this->getEbooks();

        if (empty($ebooks)) {
            die(Console::writeLine('Nothing to import.'));
        }

        $widget = $this->getTable('Widget')->getWidgetByName($widgetName);

        // Remove all data first
        $this->getTable('WidgetContent')->truncateWidgetContent($widget);

        foreach ($ebooks as $ebook) {
            $widgetContent = (new WidgetContent());
            $widgetContent->setWidgetId($widget->getId());
            $widgetContent->setValue(trim($ebook['id']));
            $widgetContent->setPreferredValue(0);

            $this->getTable('WidgetContent')->addWidgetContent($widgetContent);
        }

        Console::writeLine('Added ' . number_format(count($ebooks), 0, ',', ' ') . ' records.');
    }

    /**
     * Get ebooks from Solr
     *
     * @return array
     */
    // TODO: This should be refactored to a service
    private function getEbooks()
    {
        // TODO: Should be passed by DI
        $configLoader = $this->getServiceLocator()->get('VuFind\Config');

        $solrUrl = $configLoader->get('config')->Index->url;
        $solrCore = $configLoader->get('config')->Index->default_core;

        $url  = "$solrUrl/$solrCore/select?";
        $url .= "q=id:mkpe.*";

        $url .= "&fl=id";
        $url .= "&wt=json";
        $url .= "&indent=true";
        $url .= "&start=0";
        $url .= '&rows=' . $configLoader->get('config')->Index->harvest_ebooks_limit;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $html = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($html, true);

        return $json['response']['docs'];
    }

    /**
     * Remove expired users
     *
     * @return void
     */
    public function expireusersAction()
    {
        $this->consoleOpts->addRules(
            [
                'h|help' => 'Get help',
            ]
        );

        if ($this->consoleOpts->getOption('h')
            || $this->consoleOpts->getOption('help')
        ) {
            Console::writeLine('Expire old users in the database.');
            Console::writeLine('');
            Console::writeLine(
                'Optional parameter: days from last login, at least 730 (= 2 years);'
            );
            Console::writeLine(
                'by default, searches more than 730 days old will be removed.'
            );
            return $this->getFailureResponse();
        }

        return $this->expire(
            'User',
            '%%count%% inactive user accounts deleted.',
            'No inactive user accounts to delete.',
            730
        );
    }
}