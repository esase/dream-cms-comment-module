<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
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
     * @return \Zend\Paginator\Paginator
     */
    public function getComments($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
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
                    'registered_nickname' => 'nick_name',
                    'registered_email' => 'email'
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