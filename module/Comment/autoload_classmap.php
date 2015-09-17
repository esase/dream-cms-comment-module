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
return [
    'Comment\Form\Comment'                               => __DIR__ . '/src/Comment/Form/Comment.php',
    'Comment\Form\CommentFilter'                         => __DIR__ . '/src/Comment/Form/CommentFilter.php',
    'Comment\Event\CommentEvent'                         => __DIR__ . '/src/Comment/Event/CommentEvent.php',
    'Comment\Model\CommentAdministration'                => __DIR__ . '/src/Comment/Model/CommentAdministration.php',
    'Comment\Model\CommentWidget'                        => __DIR__ . '/src/Comment/Model/CommentWidget.php',
    'Comment\Model\CommentBase'                          => __DIR__ . '/src/Comment/Model/CommentBase.php',
    'Comment\Model\CommentNestedSet'                     => __DIR__ . '/src/Comment/Model/CommentNestedSet.php',
    'Comment\Controller\CommentAdministrationController' => __DIR__ . '/src/Comment/Controller/CommentAdministrationController.php',
    'Comment\View\Widget\CommentLastUserCommentsWidget'  => __DIR__ . '/src/Comment/View/Widget/CommentLastUserCommentsWidget.php',
    'Comment\View\Widget\CommentWidget'                  => __DIR__ . '/src/Comment/View/Widget/CommentWidget.php',
    'Comment\View\Widget\CommentLastCommentsWidget'      => __DIR__ . '/src/Comment/View/Widget/CommentLastCommentsWidget.php',
    'Comment\View\Widget\AbstractCommentWidget'          => __DIR__ . '/src/Comment/View/Widget/AbstractCommentWidget.php',
    'Comment\View\Helper\CommentProcessAdminComment'     => __DIR__ . '/src/Comment/View/Helper/CommentProcessAdminComment.php',
    'Comment\View\Helper\CommentCommenterName'           => __DIR__ . '/src/Comment/View/Helper/CommentCommenterName.php',
    'Comment\View\Helper\CommentCommenterEmail'          => __DIR__ . '/src/Comment/View/Helper/CommentCommenterEmail.php',
    'Comment\View\Helper\CommentProcessComment'          => __DIR__ . '/src/Comment/View/Helper/CommentProcessComment.php',
    'Comment\Module'                                     => __DIR__ . '/Module.php',
    'Comment\Test\CommentBootstrap'                      => __DIR__ . '/test/Bootstrap.php'
];
