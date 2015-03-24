<?php
namespace Comment;

use Zend\ModuleManager\ModuleManagerInterface;
use Zend\Db\TableGateway\TableGateway;

class Module
{
    /**
     * Service locator
     * @var object
     */
    public $serviceLocator;

    /**
     * Init
     *
     * @param object $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get service manager
        $this->serviceLocator = $moduleManager->getEvent()->getParam('ServiceManager');
    }

    /**
     * Return autoloader config array
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php',
            ],
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }

    /**
     * Return service config array
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'Comment\Model\CommentNestedSet' => function() {
                    return new Model\CommentNestedSet(new
                            TableGateway('comment_list', $this->serviceLocator->get('Zend\Db\Adapter\Adapter')));
                }
            ]
        ];
    }

    /**
     * Init view helpers
     */
    public function getViewHelperConfig()
    {
        return [
            'invokables' => [
                'commentProcessComment' => 'Comment\View\Helper\CommentProcessComment',
                'commentProcessAdminComment' => 'Comment\View\Helper\CommentProcessAdminComment',
                'commentCommenterName' => 'Comment\View\Helper\CommentCommenterName',
                'commentCommenterEmail' => 'Comment\View\Helper\CommentCommenterEmail',
                'commentWidget' => 'Comment\View\Widget\CommentWidget',
                'commentLastUserCommentsWidget' => 'Comment\View\Widget\CommentLastUserCommentsWidget',
                'commentLastCommentsWidget' => 'Comment\View\Widget\CommentLastCommentsWidget'
            ]
        ];
    }

    /**
     * Return path to config file
     *
     * @return boolean
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}