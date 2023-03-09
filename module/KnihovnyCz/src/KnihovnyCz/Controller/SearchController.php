<?php
declare(strict_types=1);

/**
 * Class SearchController
 *
 * PHP version 7
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
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
namespace KnihovnyCz\Controller;

use Laminas\Http\Header\ContentSecurityPolicy;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Stdlib\ResponseInterface as Response;
use Laminas\View\Model\ViewModel;

/**
 * Class SearchController
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Controller
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class SearchController extends \VuFind\Controller\SearchController
{
    use \VuFind\I18n\Translator\LanguageInitializerTrait;
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service manager
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(ServiceLocatorInterface $sm)
    {
        parent::__construct($sm);
        $this->setTranslator(
            $sm->get(\Laminas\I18n\Translator\TranslatorInterface::class)
        );
    }

    /**
     * Dispatch a request
     *
     * @param Request       $request  Http request
     * @param null|Response $response Http response
     *
     * @return Response|mixed
     */
    public function dispatch(Request $request, Response $response = null)
    {
        $type = $this->params()->fromQuery('type0');
        if (is_array($type) ? in_array('Libraries', $type) : $type == 'Libraries') {
            $type = 'AllLibraries';
            $lookfor = $this->params()->fromQuery('lookfor0');
            $lookfor = is_array($lookfor) ? $lookfor[0] : $lookfor;
            $limit = 20;
            return $this->redirect()->toRoute(
                'search2-results',
                [],
                ['query' => compact('type', 'lookfor', 'limit')]
            );
        }
        return parent::dispatch($request, $response);
    }

    /**
     * Home action
     *
     * @return mixed
     */
    public function homeAction()
    {
        $view = parent::homeAction();
        $view->setVariable('hideFilters', true);
        return $view;
    }

    /**
     * Show embedded search for use in HTML iframe
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Stdlib\ResponseInterface
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function embeddedAction(): ViewModel|Response
    {
        if ($this->params()->fromRoute('action') === 'embedded') {
            return $this->redirect()->toRoute('search-embedded');
        }

        $this->disableSessionWrites();
        $headers = $this->getResponse()->getHeaders();
        $cspHeader = $headers->get('Content-Security-Policy');
        if ($cspHeader === false) {
            $cspHeader = new ContentSecurityPolicy();
            $headers->addHeader($cspHeader);
        }
        if (is_iterable($cspHeader)) {
            $cspHeader = $cspHeader->current();
        }
        $cspHeader->setDirective('frame-ancestors', ['*']);
        $view = $this->createViewModel();
        $view->setTemplate('search/embedded');
        $view->setTerminal(true);
        // Use separate parameter for language so we are not interfering with
        // language for portal
        $lang = $this->params()->fromQuery('lang', null);
        if ($lang != null) {
            $this->setLanguage($lang);
        }
        $view->setVariable('lookfor', $this->params()->fromQuery('lookfor', ''));

        $config = $this->getConfig("config");
        $databases = [
            'default' => [
                'url' => '/Search/Results',
                'type' => 'AllFields',
            ],
            'eds' => [
                'url' => '/EDS/Search',
                'type' => 'AllFields',
            ],
            'libraries' => [
                'url' => '/Libraries/Results',
                'type' => 'AllLibraries',
            ],
        ];
        $database = strtolower($this->params()->fromQuery('database', ''));
        $search = $databases[$database] ?? $databases['default'];
        $router = $this->serviceLocator->get('HttpRouter');
        $serverUrl = $this->serviceLocator->get('ViewRenderer')->plugin('serverurl');
        $baseUrl = $serverUrl($router->assemble([], ['name' => 'home']));
        $view->setVariables(
            [
                'link' => rtrim($baseUrl, '/') . $search['url'],
                'type' => $search['type'],
                'baseUrl' => $baseUrl,
                'title' => $config->Embedded->title ?? 'logo_title',
                'theme' => $config->Site->theme ?? '',
                'position' => $this->params()->fromQuery('position', 'left'),
                'language' => $lang,
            ]
        );
        return $view;
    }

    /**
     * Set up the translator language.
     *
     * @param string $userLang User language
     *
     * @return void
     */
    protected function setLanguage($userLang)
    {
        // Start with default language setting; override with user language
        // preference if set and valid. Default to English if configuration
        // is missing.
        $localeSettings = $this->serviceLocator
            ->get(\VuFind\I18n\Locale\LocaleSettings::class);
        $translator = $this->serviceLocator
            ->get(\VuFind\I18n\Locale\LocaleSettings::class);
        $language = $localeSettings->getDefaultLocale();
        $allLanguages = array_keys($localeSettings->getEnabledLocales());
        if ($userLang != '' && in_array($userLang, $allLanguages)) {
            $language = $userLang;
        }
        $this->translator->setLocale($language);
        $this->addLanguageToTranslator(
            $this->translator,
            $localeSettings,
            $language
        );
    }

    /**
     * Perform a search and send results to a results view
     *
     * @param callable $setupCallback Optional setup callback that overrides the
     * default one
     *
     * @return ViewModel
     */
    protected function getSearchResultsView($setupCallback = null)
    {
        $view = parent::getSearchResultsView($setupCallback);
        $this->disableLastInPagination($view);
        return $view;
    }

    /**
     * Disable link to page with the last results in pagination if the limit on
     * numer of results is exceeded.
     *
     * @param ViewModel|Response $view view
     *
     * @return void
     */
    protected function disableLastInPagination(ViewModel|Response $view)
    {
        if (!isset($view->results)) {
            return;
        }
        $searchConfig = $this->getConfig('searches');
        $limit = $searchConfig->Pagination->maxResultsToShowLastLink ?? 0;
        if ($limit > 0 && $view->results->getResultTotal() > $limit) {
            $paginationOptions = $view->paginationOptions ?? [];
            $paginationOptions['disableLast'] = true;
            $view->paginationOptions = $paginationOptions;
        }
    }
}
