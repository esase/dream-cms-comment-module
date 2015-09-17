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
     *
     * @var string
     */
    protected $formName = 'comment';

    /**
     * Captcha enabled status
     *
     * @var boolean
     */
    protected $captchaEnabled = true;

    /**
     * Validate spam ip
     *
     * var boolean
     */
    protected $validateSpamIp = true;

    /**
     * Guest mode
     *
     * @var boolean
     */
    protected $guestMode = true;

    /**
     * Model
     *
     * @var \Comment\Model\CommentBase
     */
    protected $model;

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
     * @return \Application\Form\ApplicationCustomFormBuilder
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
            if ($this->validateSpamIp) {
                $this->formElements['comment']['validators'] = [
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'validateSpamIp'],
                            'message' => 'Your IP address blocked'
                        ]
                    ]
                ];
            }

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Enable captcha
     *
     * @param boolean $enable
     * @return \Comment\Form\Comment
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
     * @return \Comment\Form\Comment
     */
    public function setGuestMode($enable)
    {
        $this->guestMode = $enable;

        return $this;
    }

    /**
     * Set a model
     *
     * @param \Comment\Model\CommentBase $model
     * @return \Comment\Form\Comment
     */
    public function setModel(CommentBaseModel $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Enable spam validation
     *
     * @param boolean $enable
     * @return \Comment\Form\Comment
     */
    public function enableSpamValidation($enable)
    {
        $this->validateSpamIp = $enable;

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