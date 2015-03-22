<?php
namespace Comment\Model;

use Comment\Model\CommentNestedSet as CommentNestedSetModel;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Application\Service\ApplicationSetting as SettingService;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Db\Sql\Predicate\Like as LikePredicate;
use Zend\Db\ResultSet\ResultSet;

class CommentAdministration extends CommentBase
{
    /**
     * Get comments pages
     *
     * @return array
     */
    public function getCommentsPages()
    {
        $select = $this->select();
        $select->from(['a' => 'comment_list'])
            ->columns([
                'page_id'
            ])
            ->join(
                ['b' => 'page_structure'],
                'a.page_id = b.id',
                [
                    'title',
                ]
            )
            ->join(
                ['c' => 'page_system'],
                'b.system_page = c.id',
                [
                    'system_title' => 'title',
                ],
                'left'
            )
            ->group('a.page_id')
            ->order('b.title, c.title')
            ->where([
                'a.language' => $this->getCurrentLanguage()
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        $pages = [];

        // process pages
        foreach ($resultSet as $page) {
            $pages[$page->page_id] = $page->title
                ? $page->title
                : $page->system_title;
        }

        return $pages;
    }

    /**
     * Get comments
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string comment
     *      string status
     *      string ip
     *      string email
     *      string name
     *      integer date_start
     *      integer date_end
     *      string page_slug
     *      integer page_id
     * @return object Zend\Paginator\Paginator
     */
    public function getComments($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = array())
    {
        $orderFields = [
            'id',
            'active',
            'ip',
            'created'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'created';

        $select = $this->select();
        $select->from(['a' => 'comment_list'])
            ->columns([
                'id',
                'guest_id',
                'name',
                'user_id',
                'email',
                'ip',
                'active',
                'created',
                'comment',
                'slug'
            ])
            ->join(
                ['b' => 'page_structure'],
                'a.page_id = b.id',
                [
                    'page_slug' => 'slug'
                ]
            )
            ->join(
                ['c' => 'user_list'],
                'a.user_id = c.user_id',
                [
                    'registred_nickname' => 'nick_name',
                    'registred_email' => 'email'
                ],
                'left'
            )
            ->order($orderBy . ' ' . $orderType)
            ->where([
                'a.language' => $this->getCurrentLanguage()
            ]);

        // filter by comment
        if (!empty($filters['comment'])) {
            $select->where([
                new LikePredicate('a.comment', '%' . $filters['comment'] . '%')
            ]);
        }

        // filter by status
        if (!empty($filters['status'])) {
            $select->where([
                'a.active' => $filters['status'] == 'active'
                    ? CommentNestedSetModel::COMMENT_STATUS_ACTIVE
                    : CommentNestedSetModel::COMMENT_STATUS_NOT_ACTIVE
            ]);
        }

        // filter by page id
        if (!empty($filters['page_id'])) {
            $select->where([
                'a.page_id' => $filters['page_id']
            ]);
        }

        // filter by page slug
        if (!empty($filters['page_slug'])) {
            $select->where([
                'a.slug' => $filters['page_slug']
            ]);
        }

        // filter by ip
        if (!empty($filters['ip'])) {
            $select->where([
                'a.ip' => inet_pton($filters['ip'])
            ]);
        }

        // filter by email
        if (!empty($filters['email'])) {
            $select->where
                ->nest
                    ->equalTo('a.email', $filters['email'])
                    ->or
                    ->equalTo('c.email', $filters['email'])
                ->unnest;
        }

        // filter by name
        if (!empty($filters['name'])) {
            $select->where
                ->nest
                    ->equalTo('a.name', $filters['name'])
                    ->or
                    ->equalTo('c.nick_name', $filters['name'])
                ->unnest;
        }

        // filter by date start
        if (!empty($filters['date_start'])) {
            $select->where->greaterThanOrEqualTo('a.created', $filters['date_start']);
        }

        // filter by date end
        if (!empty($filters['date_end'])) {
            $select->where->lessThanOrEqualTo('a.created', $filters['date_end']);
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }
}