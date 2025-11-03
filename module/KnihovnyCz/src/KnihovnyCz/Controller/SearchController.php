<?php

declare(strict_types=1);

namespace KnihovnyCz\Controller;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Stdlib\ResponseInterface as Response;
use Laminas\View\Model\ViewModel;
use VuFind\Search\Factory\UrlQueryHelperFactory;

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
    public function dispatch(Request $request, ?Response $response = null)
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

        $searches = $this->getConfig('searches')->toArray() ?? [];

        $groupId = 0;
        $advancedSearchesChanged = false;
        $params = $this->params()->fromQuery();
        $newGroupId = 0;

        while (is_array($this->params()->fromQuery('type' . $groupId))) {
            $types = $this->params()->fromQuery('type' . $groupId);
            $lookfors = $this->params()->fromQuery('lookfor' . $groupId);
            $bool = $this->params()->fromQuery('bool' . $groupId);

            unset($params['type' . $groupId]);
            unset($params['lookfor' . $groupId]);
            unset($params['bool' . $groupId]);

            $validTypes = [];
            $validLookfors = [];

            foreach ($types as $searchTypeIndex => $searchTypeName) {
                if (array_key_exists($searchTypeName, $searches['Advanced_Searches'])) {
                    $validTypes[] = $searchTypeName;
                    $validLookfors[] = $lookfors[$searchTypeIndex];
                }
            }

            if (!empty($validTypes)) {
                $params['type' . $newGroupId] = $validTypes;
                $params['lookfor' . $newGroupId] = $validLookfors;
                $params['bool' . $newGroupId] = $bool;

                $newGroupId++;
            }

            $advancedSearchesChanged |= $types != $validTypes;
            $groupId++;
        }

        if ($advancedSearchesChanged) {
            $params['advanced_searches_changed'] = 1;

            return $this->redirect()->toUrl($this->url()->fromRoute() . '?' . http_build_query($params));
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
     * Edit search memory action.
     *
     * @return mixed
     */
    /**
     * Edit search memory action.
     *
     * @return mixed
     */
    public function editmemoryAction()
    {
        // Get the user's referer, with the home page as a fallback; we'll
        // redirect here after the work is done.
        $from = $this->getRequest()->getServer()->get('HTTP_REFERER') ?? '';
        if (empty($from) || !$this->isLocalUrl($from)) {
            $from = $this->url()->fromRoute('home');
        }
        $search = $this->getSearchMemory()->getCurrentSearch();
        if (!isset($search)) {
            return $this->redirect()->toUrl($from);
        }
        $params = $search->getParams();

        $removeAllFilters = $this->params()->fromQuery('removeAllFilters');
        $removeFacet = $this->params()->fromQuery('removeFacet');
        $removeFilter = $this->params()->fromQuery('removeFilter');

        $factory = $this->serviceLocator->get(UrlQueryHelperFactory::class);
        $initialParams = $factory->fromParams($params);
        if ($removeAllFilters) {
            $defaultFilters = $params->getOptions()->getDefaultFilters();
            $query = $initialParams->removeAllFilters();
            foreach ($defaultFilters as $filter) {
                $query = $query->addFilter($filter);
            }
        } elseif ($removeFacet) {
            $query = $initialParams->removeFacet(
                $removeFacet['field'] ?? '',
                $removeFacet['value'] ?? '',
                $removeFacet['operator'] ?? 'AND'
            );
        } elseif ($removeFilter) {
            $query = $initialParams->removeFilter($removeFilter);
        } else {
            $query = null;
        }
        $searchClassId = $params->getSearchClassId();
        $runner = $this->serviceLocator->get(\VuFind\Search\SearchRunner::class);
        $results = $runner->run($query->getParamArray(), $searchClassId);
        $sessManager = $this->serviceLocator->get(SessionManager::class);
        $sessId = $sessManager->getId();
        $result = $this->serviceLocator->get(\VuFind\Search\SearchNormalizer::class)->saveNormalizedSearch(
            $results,
            $sessId,
            $this->getUser()?->getId()
        );
        $newUrl = preg_replace('/sid=[\d]+/', 'sid=' . $result->id, $from);
        return $this->redirect()->toUrl($newUrl);
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
        $view->setVariable('isAdvancedSearchesChanged', $this->params()->fromQuery('advanced_searches_changed'));
        return $view;
    }

    /**
     * Disable link to page with the last results in pagination if the limit on
     * number of results is exceeded.
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
