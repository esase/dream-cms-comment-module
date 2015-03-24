<?php
namespace Comment\View\Widget;

use Page\View\Widget\PageAbstractWidget;
use Localization\Service\Localization as LocalizationService;

abstract class AbstractCommentWidget extends PageAbstractWidget
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Comment\Model\CommentWidget');
        }

        return $this->model;
    }

    /**
     * Get current language
     * 
     * @return string
     */
    protected function getCurrentLanguage()
    {
        return LocalizationService::getCurrentLocalization()['language'];
    }
}