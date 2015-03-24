<?php
namespace Comment\Controller;

use Localization\Service\Localization as LocalizationService;
use Application\Controller\ApplicationAbstractAdministrationController;
use Zend\View\Model\ViewModel;

class CommentAdministrationController extends ApplicationAbstractAdministrationController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get current language
     *
     * @return string
     */
    protected function getCurrentLanguage()
    {
        return LocalizationService::getCurrentLocalization()['language'];
    }

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Comment\Model\CommentAdministration');
        }

        return $this->model;
    }

    /**
     * Settings
     */
    public function settingsAction()
    {
        return new ViewModel([
            'settings_form' => parent::settingsForm('comment', 'comments-administration', 'settings')
        ]);
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('comments-administration', 'list');
    }

    /**
     * List of comments
     */
    public function listAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Comment\Form\CommentFilter')
            ->setModel($this->getModel());

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getComments($this->getPage(),
                $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }

    /**
     * View comment details
     */
    public function ajaxViewCommentAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $commentId = $this->params()->fromQuery('id', -1);

        return new ViewModel([
            'data' => $this->getModel()->
                    getCommentModel()->getCommentInfo($commentId, null, null, $this->getCurrentLanguage())
        ]);
    }

    /**
     * Delete selected comments
     */
    public function deleteCommentsAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($commentsIds = $request->getPost('comments', null))) {
                // delete selected comments
                $deleteResult = false;
                $deletedCount = 0;

                foreach ($commentsIds as $commentId) {
                    // get a comment's info
                    if (false !== ($commentInfo = $this->getModel()->
                            getCommentModel()->getCommentInfo($commentId, null, null, $this->getCurrentLanguage()))) {

                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Access Denied'));

                            break;
                        }

                        // delete the comment
                        if (true !== ($deleteResult = $this->
                                getModel()->getCommentModel()->deleteComment($commentInfo))) {

                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                    : $this->getTranslator()->translate('Error occurred')));

                            break;
                        }

                        $deletedCount++;
                    }
                }

                if (true === $deleteResult) {
                    $message = $deletedCount > 1
                        ? 'Selected comments have been deleted'
                        : 'The selected comment has been deleted';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('comments-administration', 'list', [], true);
    }

    /**
     * Approve selected comments
     */
    public function approveCommentsAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($commentsIds = $request->getPost('comments', null))) {
                // approve selected comments
                $approveResult = false;
                $approvedCount = 0;

                foreach ($commentsIds as $commentId) {
                    // get a comment's info
                    if (false !== ($commentInfo = $this->getModel()->
                            getCommentModel()->getCommentInfo($commentId, null, null, $this->getCurrentLanguage()))) {

                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Access Denied'));

                            break;
                        }

                        // approve the comment
                        if (true !== ($approveResult = 
                                $this->getModel()->getCommentModel()->approveComment($commentInfo))) {

                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate($approveResult));

                            break;
                        }

                        $approvedCount++;
                    }
                }

                if (true === $approveResult) {
                    $message = $approvedCount > 1
                        ? 'Selected comments have been approved'
                        : 'The selected comment has been approved';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('comments-administration', 'list', [], true);
    }

    /**
     * Disapprove selected comments
     */
    public function disapproveCommentsAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($commentsIds = $request->getPost('comments', null))) {
                // disapprove selected comments
                $disapproveResult = false;
                $disapprovedCount = 0;

                foreach ($commentsIds as $commentId) {
                    // get a comment's info
                    if (false !== ($commentInfo = $this->getModel()->
                            getCommentModel()->getCommentInfo($commentId, null, null, $this->getCurrentLanguage()))) {

                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Access Denied'));

                            break;
                        }

                        // disapprove the comment
                        if (true !== ($disapproveResult = 
                                $this->getModel()->getCommentModel()->disapproveComment($commentInfo))) {

                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate($disapproveResult));

                            break;
                        }

                        $disapprovedCount++;
                    }
                }

                if (true === $disapproveResult) {
                    $message = $disapprovedCount > 1
                        ? 'Selected comments have been disapproved'
                        : 'The selected comment has been disapproved';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('comments-administration', 'list', [], true);
    }

    /**
     * Edit comment action
     */
    public function editCommentAction()
    {
        // get the comment info
        if (false === ($comment = $this->getModel()->
                getCommentModel()->getCommentInfo($this->getSlug(), null, null, $this->getCurrentLanguage()))) {

            return $this->redirectTo('comments-administration', 'list');
        }

        // get the comment form
        $commentForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Comment\Form\Comment')
            ->enableCaptcha(false)
            ->setGuestMode(false)
            ->enableSpamValidation(false);
 
        // fill the form with default values
        $commentForm->getForm()->setData($comment);
        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $commentForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($commentForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // collect basic data
                $basicData = [
                    'active' => $comment['active'],
                    'comment' => $commentForm->getForm()->getData()['comment']
                ];

                // edit the comment
                $result = $this->getModel()->getCommentModel()->editComment($comment, $basicData);

                if (is_array($result)) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Comment has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('comments-administration', 'edit-comment', [
                    'slug' => $comment['id']
                ]);
            }
        }

        return new ViewModel([
            'comment_form' => $commentForm->getForm(),
            'comment' => $comment
        ]);
    }
}