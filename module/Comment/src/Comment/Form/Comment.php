<?php
namespace Comment\Form;

use Comment\Model\CommentBase as CommentBaseModel;
use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;
use Zend\Http\PhpEnvironment\RemoteAddress;

class Comment extends ApplicationAbstractCustomForm 
{
    /**
     * Comment max string length
     */
    const COMMENT_MAX_LENGTH = 65535;

    /**
     * Name max string length
     */
    const NAME_MAX_LENGTH = 50;

    /**
     * Email max string length
     */
    const EMAIL_MAX_LENGTH = 50;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'comment';

    /**
     * Captcha enabled status
     * @va boolean
     */
    protected $captchaEnabled = true;

    /**
     * Guest mode
     * @var boolean
     */
    protected $guestMode = true;

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
        'name' => [
            'name' => 'name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Your name',
            'required' => true,
            'max_length' => self::NAME_MAX_LENGTH
        ],
        'email' => [
            'name' => 'email',
            'type' => ApplicationCustomFormBuilder::FIELD_EMAIL,
            'label' => 'Email',
            'required' => true,
            'max_length' => self::EMAIL_MAX_LENGTH
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

    /**
     * Get form instance
     *
     * @return object
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {
            // remove captcha
            if (!$this->captchaEnabled) {
                unset($this->formElements['captcha']);
            }

            if (!$this->guestMode) {
                unset($this->formElements['name']);
                unset($this->formElements['email']);
            }

            // check for spam
            $this->formElements['comment']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateSpamIp'],
                        'message' => 'Your IP address blocked'
                    ]
                ]
            ];

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Enable captcha
     *
     * @param boolean $enable
     * @return object fluent interface
     */
    public function enableCaptcha($enable)
    {
        $this->captchaEnabled = $enable;
        return $this;
    }

    /**
     * Set guest mode
     *
     * @param boolean $enable
     * @return object fluent interface
     */
    public function setGuestMode($enable)
    {
        $this->guestMode = $enable;
        return $this;
    }

    /**
     * Set a model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(CommentBaseModel $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Validate spam IP
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateSpamIp($value, array $context = [])
    {
        $remote = new RemoteAddress;
        $remote->setUseProxy(true);

        return $this->model->isSpamIp($remote->getIpAddress()) ? false : true;
    }
}