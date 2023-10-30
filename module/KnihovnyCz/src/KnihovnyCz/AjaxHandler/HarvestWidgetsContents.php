<?php

declare(strict_types=1);

namespace KnihovnyCz\AjaxHandler;

use KnihovnyCz\Db\Table\UserList;
use Laminas\Db\Sql\Select;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\Db\Table\UserResource;

/**
 * Class HarvestWidgetsContents
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\AjaxHandler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class HarvestWidgetsContents extends AbstractBase
{
    /**
     * User list table
     *
     * @var UserList
     */
    protected UserList $userList;

    /**
     * User resource table
     *
     * @var UserResource
     */
    protected UserResource $resource;

    /**
     * Constructor
     *
     * @param UserList     $userLists User lists table
     * @param UserResource $resource  User resource table
     */
    public function __construct(UserList $userLists, UserResource $resource)
    {
        $this->userList = $userLists;
        $this->resource = $resource;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     * @throws \Exception
     */
    public function handleRequest(Params $params)
    {
        $data = [];

        /* Inspiration lists from user defined lists */
        $lists = $this->userList->getInspirationLists();
        foreach ($lists as $list) {
            $listId = $list['id'];
            $resources = $this->resource->select(
                function (Select $select) use ($listId) {
                    $select->where->equalTo('list_id', $listId);
                    $select->columns(['id']);
                    $select->join(
                        'resource',
                        'resource.id = user_resource.resource_id',
                        ['record_id']
                    );
                }
            );

            $data[] = [
                'list' => $list->getSlug(),
                'items' => array_column($resources->toArray(), 'record_id'),
            ];
        }
        return $this->formatResponse($data);
    }
}
