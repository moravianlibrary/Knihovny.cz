<?php

namespace KnihovnyCzConsole\Command\Util;

use GuzzleHttp\Exception\GuzzleException;
use KnihovnyCz\Service\GuzzleHttpService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VuFind\I18n\ExtendedIniNormalizer;

/**
 * Class GenerateSiglaTranslationsCommand
 *
 * Generates library name translation files (Sigla cs.ini, en.ini) from the Knihovny.cz public API.
 *
 * @category VuFind
 * @package  KnihovnyCzConsole
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
#[AsCommand(
    name: 'util/generate_sigla_translations',
    description: 'Generate library name translation files from the Knihovny.cz API'
)]
class GenerateSiglaTranslationsCommand extends Command
{
    /**
     * Default API base URL.
     *
     * @var string
     */
    protected const DEFAULT_API_URL = 'https://www.knihovny.cz/api/v1';

    /**
     * Page size used when querying the API.
     *
     * @var int
     */
    protected const PAGE_LIMIT = 1000;

    /**
     * Extra town stems used when the inflected form does not share a usable prefix with the nominative.
     *
     * @var array<string, string[]>
     */
    protected const TOWN_EXTRA_STEMS = [
        'Stařeč' => ['Starč'],
    ];

    /**
     * Constructor
     *
     * @param GuzzleHttpService     $httpService HTTP service
     * @param string                $repoRoot    Repository root directory
     * @param ExtendedIniNormalizer $normalizer  INI normalizer
     * @param string|null           $name        Command name
     */
    public function __construct(
        protected GuzzleHttpService $httpService,
        protected string $repoRoot,
        protected ExtendedIniNormalizer $normalizer,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setHelp(
            'Fetches all libraries from the Knihovny.cz API and writes translation files for library names'
            . ' (cs.ini, en.ini). For public libraries (sigla[2] == "G") the town is appended to the name when'
            . ' it is not already present.'
        )->addOption(
            'api-url',
            null,
            InputOption::VALUE_REQUIRED,
            'Use a different API URL',
            self::DEFAULT_API_URL
        )->addOption(
            'skc',
            null,
            InputOption::VALUE_NONE,
            'Only include libraries participating in Souborný katalog ČR (Caslin)'
        )->addOption(
            'cs-file',
            null,
            InputOption::VALUE_REQUIRED,
            'Path to the Czech output file (default: local/base/languages/Sigla/cs.ini)'
        )->addOption(
            'en-file',
            null,
            InputOption::VALUE_REQUIRED,
            'Path to the English output file (default: local/base/languages/Sigla/en.ini)'
        );
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
        $apiUrl = rtrim((string)$input->getOption('api-url'), '/');
        $skc = (bool)$input->getOption('skc');
        $defaultDir = $this->repoRoot . '/local/base/languages/Sigla';
        $csFile = $input->getOption('cs-file') ?: $defaultDir . '/cs.ini';
        $enFile = $input->getOption('en-file') ?: $defaultDir . '/en.ini';
        $stderr = ($output instanceof ConsoleOutputInterface) ? $output->getErrorOutput() : $output;
        foreach ([$csFile, $enFile] as $file) {
            if (!is_dir(dirname($file))) {
                $stderr->writeln('Error: Directory does not exist: ' . dirname($file));
                return 1;
            }
        }

        $output->writeln('Fetching data from API: ' . $apiUrl);
        try {
            $records = $this->fetchAllRecords($apiUrl, $skc, $output);
        } catch (\RuntimeException $e) {
            $stderr->writeln('Error: ' . $e->getMessage());
            return 1;
        }
        if ($records === []) {
            $output->writeln('API returned 0 records — nothing to generate.');
            return 0;
        }

        $processed = [];
        foreach ($records as $record) {
            $row = $this->processRecord($record);
            if ($row !== null) {
                $processed['cs'][$row['sigla']] = $row['cs'];
                $processed['en'][$row['sigla']] = $row['en'];
            }
        }

        $output->writeln('Generating output files...');
        file_put_contents($csFile, $this->normalizer->formatAsString($processed['cs']));
        file_put_contents($enFile, $this->normalizer->formatAsString($processed['en']));

        $output->writeln('Done! Generated ' . max(count($processed['cs']), count($processed['en'])) . ' records.');
        $output->writeln('  ' . $csFile);
        $output->writeln('  ' . $enFile);

        return 0;
    }

    /**
     * Fetch all library records from the API, paging through results.
     *
     * @param string          $apiUrl Base API URL
     * @param bool            $skc    Whether to filter to SKC libraries only
     * @param OutputInterface $output Error/progress output
     *
     * @return array<int, array<string, mixed>> Records or null when API
     *         returned zero results (caller should exit successfully).
     *
     * @throws \RuntimeException On API/transport errors.
     */
    protected function fetchAllRecords(string $apiUrl, bool $skc, OutputInterface $output): array
    {
        $client = $this->httpService->createClient();
        $records = [];
        $page = 1;
        $totalPages = 1;
        $query = [
            'limit' => self::PAGE_LIMIT,
            'field' => ['sigla', 'title', 'town', 'alternativeTitles'],
            'filter' => $skc ? ['portal_facet_mv:"SKC_YES"'] : [],
        ];
        while ($page <= $totalPages) {
            $query['page'] = $page;
            try {
                $response = $client->request('GET', $apiUrl . '/libraries/search', ['query' => $query]);
            } catch (GuzzleException $e) {
                throw new \RuntimeException('API request failed: ' . $e->getMessage(), 0, $e);
            }

            $data = json_decode((string)$response->getBody(), true);
            if (!is_array($data)) {
                throw new \RuntimeException('API returned invalid JSON.');
            }
            $status = $data['status'] ?? null;
            if ($status !== 'OK') {
                $message = $data['statusMessage'] ?? 'unknown error';
                throw new \RuntimeException('API error: ' . $message);
            }

            if ($page === 1) {
                $resultCount = (int)($data['resultCount'] ?? 0);
                $totalPages = (int)ceil($resultCount / self::PAGE_LIMIT);
            }
            array_push($records, ...($data['records'] ?? []));
            $output->write("\rProcessed: " . count($records) . ' records');
            $page++;
        }
        $output->writeln('');

        return $records;
    }

    /**
     * Process a single API record into a sigla/cs/en triple.
     * Returns null when the record does not have the required fields.
     *
     * @param array<string, mixed> $record Record from the API
     *
     * @return array{sigla: string, cs: string, en: string}|null
     */
    protected function processRecord(array $record): ?array
    {
        $sigla = $record['sigla'] ?? null;
        $title = $record['title'] ?? null;
        if (!is_string($sigla) || $sigla === '' || !is_string($title) || $title === '') {
            return null;
        }
        $trim = fn (string $s): string => mb_trim($s, " \t\n\r\0\v\f\u{00A0}\u{FEFF}", 'UTF-8');
        $altTitles = (array)$record['alternativeTitles'] ?? [];
        $titleCs = $trim($title);
        $titleEn = $trim((string)($altTitles[0] ?? $title));
        $town = $trim((string)($record['town'] ?? ''));
        $isPublic = mb_substr($sigla, 2, 1) === 'G';
        if ($isPublic && $town !== '') {
            foreach (['titleCs', 'titleEn'] as $titleVar) {
                if (!$this->townInTitle($town, $$titleVar, $titleVar === 'titleEn')) {
                    $$titleVar .= ' (' . $town . ')';
                }
            }
        }

        return [
            'sigla' => $sigla,
            'cs' => $titleCs,
            'en' => $titleEn,
        ];
    }

    /**
     * Compute a town-word stem: length <= 3 -> whole word, length == 4 -> first 2 chars,
     * otherwise -> first max(length - 2, 3) characters.
     *
     * @param string $word Single word (UTF-8)
     *
     * @return string
     */
    protected function wordStem(string $word): string
    {
        $len = mb_strlen($word, 'UTF-8');
        return match (true) {
            $len <= 3 => $word,
            $len === 4 => mb_substr($word, 0, 2, 'UTF-8'),
            default => mb_substr($word, 0, max($len - 2, 3), 'UTF-8'),
        };
    }

    /**
     * Decide whether the (normalized) town name appears in the title. Czech titles use the town verbatim;
     * English titles also strip diacritics.
     *
     * @param string $town            Original town name
     * @param string $title           Title to test
     * @param bool   $stripDiacritics Whether to strip diacritics
     *
     * @return bool
     */
    protected function townInTitle(string $town, string $title, bool $stripDiacritics): bool
    {
        $normalize = function (string $s) use ($stripDiacritics): string {
            if ($stripDiacritics) {
                $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            }
            return strtolower($s);
        };

        $titleWords = array_values(
            array_filter(explode(' ', $normalize($title)))
        );

        $townNormalised = $normalize($town);
        $townWords = array_values(
            array_filter(explode(' ', str_replace('-', ' ', $townNormalised)))
        );

        $stems = [];
        foreach ($townWords as $word) {
            $stems[] = $this->wordStem($word);
        }
        foreach (self::TOWN_EXTRA_STEMS[$town] ?? [] as $extra) {
            $stems[] = $normalize($extra);
        }
        array_filter($stems);

        foreach ($stems as $stem) {
            foreach ($titleWords as $word) {
                if (str_starts_with($word, $stem)) {
                    return true;
                }
            }
        }
        return false;
    }
}
