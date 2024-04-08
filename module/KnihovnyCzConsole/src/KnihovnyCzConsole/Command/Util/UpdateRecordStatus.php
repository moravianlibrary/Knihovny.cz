<?php

/**
 * Class UpdateRecordStatus
 *
 * PHP version 7
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
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */

namespace KnihovnyCzConsole\Command\Util;

use Laminas\Db\Adapter\Adapter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateRecordStatus
 *
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Vaclav Rosecky <vaclav.rosecky@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class UpdateRecordStatus extends \Symfony\Component\Console\Command\Command
{
    protected const UPDATE_TOTALS_CMD = <<<EOF
        DELETE FROM import_record_status_totals WHERE source = ':source';

        LOAD DATA LOCAL INFILE ':file' INTO TABLE import_record_status_totals
          FIELDS TERMINATED BY ','
          LINES TERMINATED BY '\n'
          (@local_record_id, absent_total, present_total)
          SET source = ':source',
            record_id = CONCAT(':source', '.', @local_record_id)
        ;

        UPDATE record_status target
          LEFT JOIN import_record_status_totals source ON source.record_id = target.record_id
        SET target.absent_total = COALESCE(source.absent_total, 0),
            target.present_total = COALESCE(source.present_total, 0)
        WHERE target.record_id LIKE ':source.%'
          AND ( -- jen změny
            target.absent_total != COALESCE(source.absent_total, 0) OR
            target.present_total != COALESCE(source.present_total, 0)
        );

        INSERT INTO record_status(record_id, absent_total, absent_on_loan, present_total, present_on_loan)
        SELECT source.record_id, source.absent_total, 0 absent_on_loan, source.present_total, 0 present_on_loan
        FROM import_record_status_totals source
        WHERE NOT EXISTS(
          SELECT 1 FROM record_status target
          WHERE target.record_id = source.record_id
        );
        EOF;

    protected const UPDATE_LOANS_CMD = <<<EOF
        DELETE FROM import_record_status_loans WHERE source = ':source';

        LOAD DATA LOCAL INFILE ':file' INTO TABLE import_record_status_loans
          FIELDS TERMINATED BY ','
          LINES TERMINATED BY '\n'
          (@local_record_id, absent_on_loan, present_on_loan)
          SET source = ':source',
            record_id = CONCAT(':source', '.', @local_record_id)
        ;

        UPDATE record_status target
        INNER JOIN import_record_status_loans source ON source.source = ':source'
          AND source.record_id = target.record_id
        SET target.absent_on_loan = source.absent_on_loan,
            target.present_on_loan = source.present_on_loan,
            last_update = :last_update
        ;

        UPDATE record_status
        SET absent_on_loan = 0, present_on_loan = 0
        WHERE (absent_on_loan != 0 OR present_on_loan != 0)
          AND record_id LIKE ':source.%'
          AND last_update != :last_update
        ;
        EOF;

    protected const SUPPORTED_TYPES = ['loans', 'totals'];

    /**
     * Help description for the command.
     *
     * @var string
     */
    protected string $commandDescription = 'Update record status';

    /**
     * The name of the command (the part after "public/index.php")
     *
     * @var string
     */
    protected static $defaultName = 'util/update_record_status';

    /**
     * Database adapter
     *
     * @var \Laminas\Db\Adapter\Adapter
     */
    protected Adapter $adapter;

    /**
     * Record status configuration
     *
     * @var array
     */
    protected array $recordStatus;

    /**
     * Output interface
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected OutputInterface $output;

    /**
     * Constructor
     *
     * @param Adapter     $adapter Database adapter
     * @param array       $config  Configuration
     * @param string|null $name    The name of the command; passing null means it
     *                             must be set in configure()
     */
    public function __construct(Adapter $adapter, array $config, string $name = null)
    {
        parent::__construct($name);
        $this->adapter = $adapter;
        $this->recordStatus = $config;
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription($this->commandDescription)
            ->addOption(
                'type',
                null,
                InputOption::VALUE_REQUIRED,
                'Type of import - totals or loans'
            )
            ->addOption(
                'config',
                null,
                InputOption::VALUE_OPTIONAL,
                'Use configuration from recordstatus.ini instead of parameters'
            )
            ->addOption(
                'source',
                null,
                InputOption::VALUE_OPTIONAL,
                'Source - shortcut (e.g. mzk), required when file option is used'
            )
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                'File to import in CSV format, only if option config is missing or false'
            );
    }

    /**
     * Run the command.
     *
     * @param InputInterface  $input  Input object
     * @param OutputInterface $output Output object
     *
     * @return int 0 for success
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $optionType = $input->getOption('type');
        if ($optionType == null) {
            throw new \Exception('Option type is missing');
        }
        if (!in_array($optionType, self::SUPPORTED_TYPES)) {
            throw new \Exception('Option type can be either loans or totals, got: ' . $optionType);
        }
        $useConfig = $input->getOption('config') == true;
        $optionFile = $input->getOption('file');
        if ($useConfig && $optionFile != null) {
            throw new \Exception('Invalid options, both config and file specified');
        }
        $optionSource = $input->getOption('source');
        if (!$useConfig) {
            if ($optionSource == null) {
                throw new \Exception('Option source is missing');
            }
            if ($optionFile == null) {
                throw new \Exception('Option file is missing');
            }
            $this->process($optionSource, $optionFile, $optionType);
            return 0;
        }
        if (empty($this->recordStatus)) {
            throw new \Exception('Configuration is missing, nothing to do');
        }
        $config = $this->recordStatus;
        if ($optionSource != null) {
            $config = $config[$optionSource] ?? null;
            if ($config == null) {
                throw new \Exception('Empty configuration for source ' . $optionSource);
            }
            $config = [ $optionSource => $config ];
        }
        foreach ($config as $source => $data) {
            $optionFile = $data[$optionType] ?? null;
            if ($optionFile == null) {
                throw new \Exception("Missing configuration for source $source and type $optionType");
            }
            $this->process($source, $optionFile, $optionType);
        }
        return 0;
    }

    /**
     * Process import file
     *
     * @param string $source source
     * @param string $file   imported file
     * @param string $type   type of import
     *
     * @return void
     * @throws \Exception
     */
    protected function process($source, $file, $type)
    {
        $download = str_starts_with($file, 'https://')
            || str_starts_with($file, 'http://');
        if ($download) {
            try {
                $file = $this->download($file);
            } catch (\Exception $ex) {
                $this->output->writeln('Download failed, retrying in 3 seconds');
                sleep(3);
                $file = $this->download($file);
            }
        }
        $connection = $this->adapter->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            $this->validateCsv($file);
            $script = null;
            if ($type == 'loans') {
                $script = self::UPDATE_LOANS_CMD;
            } elseif ($type == 'totals') {
                $script = self::UPDATE_TOTALS_CMD;
            } else {
                throw new \Exception("Unsupported type: $type");
            }
            $startTime = $this->getTime();
            $this->output->writeln('Executing update in DB');
            $commands = explode(';', $script);
            $lastUpdate = time();
            foreach ($commands as $command) {
                if (empty($command)) {
                    continue;
                }
                $command = str_replace([':source', ':file', ':last_update'], [$source, $file, $lastUpdate], $command);
                $this->output->writeln('Executing: ' . $command);
                $connection->execute($command);
            }
            $this->output->writeln('DB update finished, elapsed time (in seconds): '
                . round($this->getTime() - $startTime, 2));
            $connection->commit();
        } catch (\Exception $ex) {
            $connection->rollback();
            throw $ex;
        } finally {
            if ($download) {
                unlink($file);
            }
        }
    }

    /**
     * Download file
     *
     * @param string $url URL with CSV file
     *
     * @return string   path to downloaded file
     * @throws \Exception
     */
    protected function download(string $url): string
    {
        $startTime = $this->getTime();
        $this->output->writeln('About to download: ' . $url);
        $tmp = tempnam(sys_get_temp_dir(), 'import_');
        if (!$tmp) {
            throw new \Exception("Temporary file can't be created");
        }
        $handle = fopen($tmp, 'w+');
        if (!$handle) {
            throw new \Exception("Temporary file can't be open for writing: " . $tmp);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FILE, $handle);
        if (curl_exec($ch) === false) {
            $error = curl_error($ch);
            unlink($tmp);
            throw new \Exception("HTTP download of URL '$url' with CSV failed: $error");
        }
        curl_close($ch);
        fclose($handle);
        $this->output->writeln('Download finished (in seconds), elapsed time: '
            . round($this->getTime() - $startTime, 2));
        return $tmp;
    }

    /**
     * Validate CSV
     *
     * @param $file file to validate
     *
     * @return void
     * @throws \Exception
     */
    protected function validateCsv($file): void
    {
        $startTime = $this->getTime();
        $this->output->writeln('CSV validation started');
        if (!file_exists($file)) {
            throw new \Exception('File not found: ' . $file);
        }
        if (($csv = fopen($file, 'r')) === false) {
            throw new \Exception("File can't be opened for reading: " . $file);
        }
        $line = 0;
        while (($data = fgetcsv($csv, null, ',')) !== false) {
            if (count($data) != 3) {
                throw new \Exception('Invalid number of fields (expected three) on line: ' . $line);
            }
            if (empty($data[0])) {
                throw new \Exception('First field with record id is empty on line: ' . $line);
            }
            if (!is_numeric($data[1])) {
                throw new \Exception('Second field with absent loans/totals is not numeric on line: ' . $line);
            }
            if (!is_numeric($data[2])) {
                throw new \Exception('Third field with present loans/totals is not numeric on line: ' . $line);
            }
            $line++;
        }
        fclose($csv);
        $this->output->writeln('CSV validation finished, elapsed time (in seconds): '
            . round($this->getTime() - $startTime, 2));
    }

    /**
     * Get the current microtime, formatted to a number.
     *
     * @return float
     */
    protected function getTime()
    {
        $time = explode(' ', microtime());
        return $time[1] + $time[0];
    }
}
