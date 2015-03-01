function Comment()
{
    /**
     * Allow add comments
     * @var boolean
     */
    var allowAddComments = false;

    /**
     * Allow approve comments
     * @var boolean
     */
    var allowApproveComments = false;

    /**
     * Allow disapprove comments
     * @var boolean
     */
    var allowDisapproveComments = false;

    /**
     * Allow delete comments
     * @var boolean
     */
    var allowDeleteComments = false;

    /**
     * Allow delete own comments
     * @var boolean
     */
    var allowDeleteOwnComments = false;

    /**
     * Base url
     * @var string
     */
    var baseUrl;

    /**
     * Access denied message
     * @var string
     */
    var accessDeniedMessage;

    /**
     * Scroll speed
     * @var integer
     */
    var scrollSpeed = 800;

    //-- protected functions --//

    /**
     * Show notification
     *
     * @param string message
     * @return void
     */
    var showNotification = function(message)
    {
        $notification = $("#comment-notification-wrapper");
        $notification.find("#comment-notification").html(message);

        $notification.modal("show");
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

        // show links only for disapproved comments
        $wrapper.find(".disapproved .media-body a.approve-comment").filter(function(){
            return $(this).parents(".media:first").hasClass("disapproved");
        }).css("display", "inline").bind("click", function(e){
            e.preventDefault();

            var $parent = $(this).parents(".media:first");
            var actionUrl = baseUrl + "&widget_action=approve_comment&widget_comment_id=" + $parent.attr("comment-id");
            var $link = $(this);

            // approve comment
            ajaxQuery($("#comments-list-wrapper"), actionUrl, function(data) {
                if (data) {
                    data = $.parseJSON(data);

                    // permission denied for approving comments
                    if (data === false) {
                        removeApproveElements();
                        showNotification(accessDeniedMessage);
                    }
                    else {
                        if (data.status == "success") {
                            $link.hide();
                            $parent.removeClass("disapproved").addClass("approved");

                            // re init disapprove links
                            if (allowDisapproveComments) {
                                initDisapproveLinks();
                            }
                        }

                        if (data.message) {
                            showNotification(data.message);
                        }
                    }
                }
                else {
                    removeAllActionsElements();
                    showNotification(accessDeniedMessage);
                }                    
            }, "post", "", false);
        });
    }

    /**
     * Init disapprove links
     *
     * @return void
     */
    var initDisapproveLinks = function()
    {
        var $wrapper = $("#global-comments-wrapper #comments-list-wrapper");

        // hide all disapprove links
        $wrapper.find(".comment-actions-wrapper a.disapprove-comment").off().hide();

        // show links only for approved comments
        $wrapper.find(".approved .media-body a.disapprove-comment").filter(function() {
            return $(this).parents(".media:first").hasClass("approved");
        }).css("display", "inline").bind("click", function(e){
            e.preventDefault();

            var $parent = $(this).parents(".media:first");
            var actionUrl = baseUrl + "&widget_action=disapprove_comment&widget_comment_id=" + $parent.attr("comment-id");
            var $link = $(this);

            // disapprove comment
            ajaxQuery($("#comments-list-wrapper"), actionUrl, function(data) {
                if (data) {
                    data = $.parseJSON(data);

                    // permission denied for disapproving comments
                    if (data === false) {
                        removeDisapproveElements();
                        showNotification(accessDeniedMessage);
                    }
                    else {
                        if (data.status == "success") {
                            $link.hide();
                            $parent.removeClass("approved").addClass("disapproved");

                            // re init approve links
                            if (allowApproveComments) {
                                initApproveLinks();
                            }
                        }

                        if (data.message) {
                            showNotification(data.message);
                        }
                    }
                }
                else {
                    removeAllActionsElements();
                    showNotification(accessDeniedMessage);
                }                    
            }, "post", "", false);
        });
    }

    /**
     * Get last comment id
     *
     * @return inetger
     */
    var getLastCommentId = function()
    {
        var $lastComment = $("#global-comments-wrapper #comments-list-wrapper").find(".media:last");
        return $lastComment.length ? $lastComment.attr("comment-id") : 0;
    }

    /**
     * Init delete links
     *
     * @return void
     */
    var initDeleteLinks = function()
    {
        var $wrapper = $("#global-comments-wrapper #comments-list-wrapper");

        // hide all delete links
        $wrapper.find(".comment-actions-wrapper a.delete-comment").off().hide();

        // show links only for delete comments
        $wrapper.find(".media-body a.delete-comment").filter(function() {
            return allowDeleteComments || (allowDeleteOwnComments && $(this).parents(".media:first").hasClass("own-comment"));
        }).css("display", "inline").bind("click", function(e){
            e.preventDefault();

            var $parent = $(this).parents(".media:first");
            var actionUrl = baseUrl + "&widget_action=delete_comment&widget_comment_id=" + $parent.attr("comment-id");
            var $link = $(this);

            // delete comment
            ajaxQuery($("#comments-list-wrapper"), actionUrl, function(data) {
                if (data) {
                    data = $.parseJSON(data);

                    // permission denied for disapproving comments
                    if (data === false) {
                        removeDeleteElements();
                        showNotification(accessDeniedMessage);
                    }
                    else {
                        if (data.status == "success") {
                            $parent.slideUp(function(){
                                $(this).remove();
    
                                // check the comments exists
                                if (!$("#global-comments-wrapper #comments-list-wrapper .media:first").length) {
                                    // refresh page
                                    location.reload();
                                }
                            });
                        }

                        if (data.message) {
                            showNotification(data.message);
                        }
                    }
                }
                else {
                    removeAllActionsElements();
                    showNotification(accessDeniedMessage);
                }                    
            }, "post", "", false);
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
     * Remove disapprove elements
     *
     * @return void
     */
    var removeDisapproveElements = function()
    {
        $("#global-comments-wrapper .comment-actions-wrapper a.disapprove-comment").remove();

        if (allowDisapproveComments) {
            allowDisapproveComments = false;
        }
    }

    /**
     * Remove delete elements
     *
     * @return void
     */
    var removeDeleteElements = function()
    {
        $("#global-comments-wrapper .comment-actions-wrapper a.delete-comment").remove();

        if (allowDeleteComments || allowDeleteOwnComments) {
            allowDeleteComments = false;
            allowDeleteOwnComments = false;
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
     * Remove comments empty wrapper
     *
     * @return void
     */
    var removeEmptyCommentsWrapper = function()
    {
        $("#global-comments-wrapper #comments-empty-wrapper").remove();
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
        removeDisapproveElements();
        removeDeleteElements();
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
                        showNotification(accessDeniedMessage);
                    }
                    else {
                        // add received comment
                        if (data.comment) {
                            addComments(data.comment, true);
                        }

                        if (data.message) {
                            showNotification(data.message);
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
                    showNotification(accessDeniedMessage);
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
            var paginatorUrl = baseUrl + "&widget_action=get_comments&widget_last_comment=" + getLastCommentId();

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
                    showNotification(accessDeniedMessage);
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
        removeEmptyCommentsWrapper();
        $commentsList = $("#global-comments-wrapper #comments-list-wrapper");
        $.each(comments, function(key, value) {
            // add the comment
            var $comment = $(value.comment);

            if (typeof isOwnReply != "undefined" && isOwnReply) {
                $comment.css({"visibility" : "hidden", "height" : "1px"});

                value.parent_id
                    ? $commentsList.find(".media[comment-id='" + value.parent_id + "'] " + ".comment-replies" + ":first").append($comment)
                    : $commentsList.prepend($comment);
 
                var topOffset = $comment.offset().top - parseInt($comment.css("marginTop"));
                $comment.hide().css({"visibility" : "visible", "height" : "auto"});

                // scroll to the added own comment
                setTimeout(function(){
                    $('html, body').animate({
                        scrollTop: topOffset
                    }, scrollSpeed, function(){
                        // show the comment
                        $comment.slideDown();
                    })
                }, 50);
            }
            else {
                value.parent_id
                    ? $commentsList.find(".media[comment-id='" + value.parent_id + "'] " + ".comment-replies" + ":first").append($comment)
                    : $commentsList.append($comment);
            }
        });

        // re init all  reply links
        !allowAddComments
            ? removeReplyElements()
            : initReplyLinks();

        // re init all approve links
        !allowApproveComments
            ? removeApproveElements()
            : initApproveLinks();

        // re init all disapprove links
        !allowDisapproveComments
            ? removeDisapproveElements()
            : initDisapproveLinks();

        // re init all delete links
        !allowDeleteComments && !allowDeleteOwnComments
            ? removeDeleteElements()
            : initDeleteLinks();
    }

    //-- public functions --//

    /**
     * Set access denied message
     *
     * @param string message
     * @return object
     */
    this.setAccessDeniedMessage = function(message)
    {
        accessDeniedMessage = message;
        return this;
    }

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
     * Allow disapprove comments
     *
     * @param boolean allowed
     * @return object
     */
    this.allowDisapproveComments = function(allowed)
    {
        allowDisapproveComments = allowed;
        return this;
    }

    /**
     * Allow delete comments
     *
     * @param boolean allowed
     * @return object
     */
    this.allowDeleteComments = function(allowed)
    {
        allowDeleteComments = allowed;
        return this;
    }

    /**
     * Allow delete own comments
     *
     * @param boolean allowed
     * @return object
     */
    this.allowDeleteOwnComments = function(allowed)
    {
        allowDeleteOwnComments = allowed;
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

        // init disapprove elements
        if (!allowDisapproveComments) {
            removeDisapproveElements();
        }
        else {
            initDisapproveLinks();
        }

        // init delete elements
        if (!allowDeleteComments && !allowDeleteOwnComments) {
            removeDeleteElements();
        }
        else {
            initDeleteLinks();
        }

        // hide the empty comments wrappers
        if ($("#global-comments-wrapper #comments-list-wrapper .media:first").length) {
            removeEmptyCommentsWrapper();
        }
    }
}