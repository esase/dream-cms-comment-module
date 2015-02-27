function Comment()
{
    /**
     * Allow add comments
     * @var boolean
     */
    var allowAddComments = true;

    /**
     * Allow approve comments
     * @var boolean
     */
    var allowApproveComments = true;

    /**
     * Base url
     * @var string
     */
    var baseUrl;

    /**
     * Last comment id
     * @var integer
     */
    var lastCommentId;

    //-- protected functions --//

    /**
     * Show action denied
     *
     * @return void
     */
    var showActionDenied = function()
    {
        $("#global-comments-wrapper > #comments-action-denied").show();
    }

    /**
     * Clone reply form
     *
     * @return object
     */
    var cloneReplyForm = function()
    {
        var $replyForm = $("#global-comments-wrapper > .comment-reply-form-wrapper").clone(true);

        // remove all erros and unset entered values
        $replyForm.find("ul").remove();
        $replyForm.find(".primary-data").val("");
        $replyForm.find(".alert").remove();

        return $replyForm;
    }

    /**
     * Init reply links
     *
     * @return void
     */
    var initReplyLinks = function()
    {
        // listen for all reply links clicks
        $("#global-comments-wrapper #comments-list-wrapper .reply-link-wrapper a").off().bind("click", function(e){
            e.preventDefault();

            if ($(this).hasClass("active-reply")) {
                // delete the reply form
                $(this).removeClass("active-reply").parents(".reply-link-wrapper").find(".comment-reply-form-wrapper").remove();
            }
            else {
                // close a previously opened reply form
                $("#global-comments-wrapper #comments-list-wrapper .comment-reply-form-wrapper").remove();
                $("#global-comments-wrapper #comments-list-wrapper .reply-link-wrapper a").removeClass("active-reply");

                $(this).addClass("active-reply").parents(".reply-link-wrapper").append(cloneReplyForm()).html();
            }
        });
    }

    /**
     * Init approve links
     *
     * @return void
     */
    var initApproveLinks = function()
    {
        var $wrapper = $("#global-comments-wrapper #comments-list-wrapper");

        // hide all approve links
        $wrapper.find(".comment-actions-wrapper a.approve-comment").off().hide();

        // show only links for disapproved comments
        $wrapper.find(".disapproved .media-body a.approve-comment").filter(function(){
            return $(this).parents(".media:first").hasClass("disapproved");
        }).css("display", "block").bind("click", function(e){
            e.preventDefault();

            var $parent = $(this).parents(".media:first");

            if ($parent.hasClass("disapproved")) {
                var actionUrl = baseUrl + "&widget_action=approve_comment&widget_comment_id=" + $parent.attr("comment-id");
                var $link = $(this);

                // approve comment
                ajaxQuery($("#comments-list-wrapper"), actionUrl, function(data) {
                    if (data) {
                        data = $.parseJSON(data);

                        // permission denied for approving comments
                        if (data === false) {
                            removeApproveElements();
                            showActionDenied();
                        }
                        else {
                            $link.hide();
                            $parent.removeClass("disapproved");
                        }
                    }
                    else {
                        removeAllActionsElements();
                        showActionDenied();
                    }                    
                }, "post", "", false);
            }
        });
    }

    /**
     * Remove reply elements
     *
     * @return void
     */
    var removeReplyElements = function()
    {
        var $globalWrapper = $("#global-comments-wrapper");
        $globalWrapper.find(".comment-reply-form-wrapper").remove();
        $globalWrapper.find(".reply-link-wrapper").remove();

        if (allowAddComments) {
            allowAddComments = false;
        }
    }

    /**
     * Remove approve elements
     *
     * @return void
     */
    var removeApproveElements = function()
    {
        $("#global-comments-wrapper .comment-actions-wrapper a.approve-comment").remove();

        if (allowApproveComments) {
            allowApproveComments = false;
        }
    }

    /**
     * Remove paginator
     *
     * @return void
     */
    var removePaginator = function()
    {
        $("#comments-paginator-wrapper").remove();
    }

    /**
     * Remove all actions elements
     *
     * @return void
     */
    var removeAllActionsElements = function()
    {
        removePaginator();
        removeReplyElements();
        removeApproveElements();
    }

    /**
     * Init reply forms
     *
     * @return void
     */
    var initReplyForms = function()
    {
        $("#global-comments-wrapper").find("form").off().on("submit", function(e) {
            e.preventDefault();
            var $form = this;
            var formUrl = baseUrl + "&widget_action=add_comment";

            // collect extra params
            var replyId = $(this).parents(".media:first").attr("comment-id");

            if (typeof replyId != "undefined") {
                formUrl += "&widget_reply_id=" + replyId;
            }

            // send a form data
            ajaxQuery($($form).parent(), formUrl, function(data) {
                if (data) {
                    data = $.parseJSON(data);

                    // permission denied for adding comments
                    if (data === false) {
                        removeReplyElements();
                        showActionDenied();
                    }
                    else {
                        // add received comment
                        if (data.comment) {
                            addComments(data.comment, true);
                        }

                        var $formParents = $($form).parents(".reply-link-wrapper" + ":first");

                        // remove the reply form
                        if ($formParents.length && data.status == "success") {
                            $formParents.find("a").removeClass("active-reply");
                            $formParents.find(".comment-reply-form-wrapper").remove();

                            return;
                        }

                        // get updated form content
                        var $formContent = $(data.form);

                        // clear form values
                        if (data.status == "success") {
                            $formContent.find(".primary-data").val("");
                        }

                        // reload the current form
                        $($form).replaceWith($formContent);
                        initReplyForms();
                    }
                }
                else {
                    removeAllActionsElements();
                    showActionDenied();
                }
            }, "post", $(this).serialize(), false);
        });
    }

    /**
     * Init paginator
     */
    var initPaginator = function()
    {
        $("#global-comments-wrapper #comments-paginator-wrapper").off().bind("click", function(e) {
            e.preventDefault();
            var paginatorUrl = baseUrl + "&widget_action=get_comments&widget_last_comment=" + lastCommentId;

            // get next comments
            ajaxQuery($("#comments-list-wrapper"), paginatorUrl, function(data) {
                // append received comments
                if (data) {
                    data = $.parseJSON(data);

                    if (typeof data.comments != "undefined" && data.comments) {
                        addComments(data.comments);
                    }

                    // remove the pagination wrapper we have reached the end
                    if (typeof data.comments == "undefined" || !data.comments || !data.show_paginator) {
                        removePaginator();
                    }
                }
                else {
                    removeAllActionsElements();
                    showActionDenied();
                }
            }, "get", "", false);
        });
    }

    /**
     * Add comments
     *
     * @param object comments
     * @param boolean isOwnReply
     * @return void
     */
    var addComments = function(comments, isOwnReply)
    {
        $commentsList = $("#comments-list-wrapper");

        $.each(comments, function(key, value) {
            // add the comment
            if (typeof isOwnReply != "undefined" && isOwnReply) {
                if (value.parent_id) {
                    $commentsList.find(".media[comment-id='" + value.parent_id + "'] " + ".comment-replies" + ":first").prepend(value.comment);

                    // remember the last added comment
                    lastCommentId = value.id;
                }
                else {
                    $commentsList.prepend(value.comment);
                }
            }
            else {
                // remember the last added comment
                lastCommentId = value.id;

                value.parent_id
                    ? $commentsList.find(".media[comment-id='" + value.parent_id + "'] " + ".comment-replies" + ":first").append(value.comment)
                    : $commentsList.append(value.comment);
            }
        });

        // re init all  reply links
        !allowAddComments ? removeReplyElements() : initReplyLinks();

        // re init all approvelinks
        !allowApproveComments ? removeApproveElements() : initApproveLinks();
    }

    /**
     * Init access denied
     */
    var initAccessDenied = function()
    {
        $("#global-comments-wrapper  > #comments-action-denied").find(".close").bind("click", function(){
            $(this).parent().hide();
        });
    }

    //-- public functions --//

    /**
     * Set base url
     *
     * @param string url
     * @return object
     */
    this.setBaseUrl = function(url)
    {
        baseUrl = url;
        return this;
    }

    /**
     * Allow add comments
     *
     * @param boolean allowed
     * @return object
     */
    this.allowAddComments = function(allowed)
    {
        allowAddComments = allowed;
        return this;
    }

    /**
     * Allow approve comments
     *
     * @param boolean allowed
     * @return object
     */
    this.allowApproveComments = function(allowed)
    {
        allowApproveComments = allowed;
        return this;
    }

    /**
     * Init
     *
     * @return void
     */
    this.init = function()
    {
        initPaginator();
        initAccessDenied();

        // init reply elements
        if (!allowAddComments) {
            removeReplyElements();
        }
        else {
            initReplyLinks();
            initReplyForms();
        }

        // init approve elements
        if (!allowApproveComments) {
            removeApproveElements();
        }
        else {
            initApproveLinks();
        }

        // get a last comment id
        $lastComment = $("#global-comments-wrapper").find(".media:last");
        lastCommentId = $lastComment.length
            ? $lastComment.attr("comment-id")
            : '';
    }
}