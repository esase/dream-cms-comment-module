<?php
namespace Comment\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;

class Comment extends ApplicationAbstractCustomForm 
{
    /**
     * Comment max string length
     */
    const COMMENT_MAX_LENGTH = 65535;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'comment';

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'comment' => [
            'name' => 'comment',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT_AREA,
            'label' => 'Comment',
            'required' => true,
            'max_length' => self::COMMENT_MAX_LENGTH
        ],
        'captcha' => [
            'name' => 'captcha',
            'type' => ApplicationCustomFormBuilder::FIELD_CAPTCHA
        ],
        'csrf' => [
            'name' => 'csrf',
            'type' => ApplicationCustomFormBuilder::FIELD_CSRF
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
        ]
    ];
}