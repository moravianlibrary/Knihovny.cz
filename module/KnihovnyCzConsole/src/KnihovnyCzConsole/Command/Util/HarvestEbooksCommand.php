<?php

/**
 * Class HarvestCommand
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
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCzConsole\Command\Util;

use KnihovnyCz\Db\Table\Widget;
use KnihovnyCz\Db\Table\WidgetContent;
use Laminas\Config\Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VuFind\Db\Table\PluginManager as TableManager;

class HarvestEbooksCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'util/harvest_ebooks';

    /**
     * Application configuration
     *
     * @var Config
     */
    protected $config;

    /**
     * Widget table
     *
     * @var Widget
     */
    protected $widgetTable;

    /**
     * Widget content table
     *
     * @var WidgetContent
     */
    protected $widgetContentTable;

    /**
     * Constructor
     *
     * @param Config       $config       Main VuFind config
     * @param TableManager $tableManager Widget table gateway
     * @param string       $name         Command name
     */
    public function __construct(
        Config $config, TableManager $tableManager, string $name = null
    ) {
        $this->config = $config;
        $this->widgetTable = $tableManager->get(Widget::class);
        $this->widgetContentTable = $tableManager->get(WidgetContent::class);
        parent::__construct($name);
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Harvest ebooks')
            ->setHelp('Harvest ebooks from Solr and insert them into widget');
    }

    /**
     * Run the command.
     *
     * @param InputInterface  $input  Input object
     * @param OutputInterface $output Output object
     *
     * @return int 0 for success
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO: Which kind of widget/ContentBlock we use? DB based or list based?
        $widgetName = $this->config->Index->harvest_ebooks_widget_name;
        $ebooks     = $this->getEbooks();

        if (empty($ebooks)) {
            $output->writeln('Nothing to import.');
            return 1;
        }

        $widget = $this->widgetTable->select([
            'name' => $widgetName
        ]);
        $widget = $widget->current();

        // Remove all data first
        $widgetContent = $this->widgetContentTable->delete([
            'widget_id' => $widget['id'],
        ]);

        foreach ($ebooks as $ebook) {
            $this->widgetContentTable->insert([
                'widget_id' => $widget['id'],
                'value' => trim($ebook['id']),
                'preferred_value' => 0,
            ]);
        }

        $output->writeln('Added ' . number_format(count($ebooks), 0, ',', ' ') . ' records.');
        return 0;
    }

    /**
     * Get ebooks from Solr
     *
     * @return array
     */
    // TODO: This should be refactored to a service
    private function getEbooks()
    {
        $solrUrl = $this->config->Index->url;
        $solrCore = $this->config->Index->default_core;

        $params = [
            'q' => 'id:mkpe.*',
            'fl' => 'id',
            'wt' => 'json',
            'start' => 0,
            'rows' => $this->config->Index->harvest_ebooks_limit,
        ];
        $url  = "$solrUrl/$solrCore/select?" . http_build_query($params);

        $client = curl_init($url);
        if ($client === false) {
            throw new \Exception('Cannot initialize cURL session');
        }
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_BINARYTRANSFER, true);
        $response = curl_exec($client);
        curl_close($client);

        if ($response === false) {
            $errno = curl_errno($client);
            $error = curl_error($client);
            throw new \Exception('Cannot connect to Solr index: Error ' . $errno . ' - ' . $error);
        }
        $json = json_decode((string)$response, true);

        return $json['response']['docs'];
    }
}
