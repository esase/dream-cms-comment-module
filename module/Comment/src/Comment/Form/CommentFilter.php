<?php
namespace Comment\Form;

use Comment\Model\CommentAdministration as CommentAdministrationModel;
use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;

class CommentFilter extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'filter';

    /**
     * Form method
     * @var string
     */
    protected $method = 'get';

    /**
     * List of not validated elements
     * @var array
     */
    protected $notValidatedElements = ['submit'];

    /**
     * Model
     * @var object
     */
    protected $model;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'name' => [
            'name' => 'name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Commenter name'
        ],
        'email' => [
            'name' => 'email',
            'type' => ApplicationCustomFormBuilder::FIELD_EMAIL,
            'label' => 'Email'
        ],
        'ip' => [
            'name' => 'ip',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'IP'
        ],
        'status' => [
            'name' => 'status',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Active',
            'values' => [
               'active' => 'Yes',
               'not_active' => 'No'
            ]
        ],
        'date_start' => [
            'name' => 'date_start',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Date start'
        ],
        'date_end' => [
            'name' => 'date_end',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Date end'
        ],
        'comment' => [
            'name' => 'comment',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Comment'
        ],
        'page_id' => [
            'name' => 'page_id',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Page name'
        ],
        'page_slug' => [
            'name' => 'page_slug',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Page slug'
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search'
        ]
    ];

    /**
     * Get form instance
     *
     * @return object
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {

            // get list of comments pages
            $this->formElements['page_id']['values'] = $this->model->getCommentsPages();

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
 
    /**
     * Set a model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(CommentAdministrationModel $model)
    {
        $this->model = $model;
        return $this;
    }
}