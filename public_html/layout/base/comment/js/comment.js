/**
 * Comment
 *
 * @var object translationsList
 */
function Comment(translationsList)
{
    /**
     * Translations
     * @var object
     */
    var translations = translationsList;

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
     * Allow edit comments
     * @var boolean
     */
    var allowEditComments = false;

    /**
     * Allow edit own comments
     * @var boolean
     */
    var allowEditOwnComments = false;

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
     * Comments structure
     * @var array
     */
    var commentsStructure = [];

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
                $(this).removeClass("active-reply").parents(".reply-link-wrapper:first").find(".comment-reply-form-wrapper").remove();
            }
            else {
                var commentFormUrl = baseUrl + "&widget_action=get_form";
                var $link = $(this);

                // get comment reply form
                ajaxQuery($(this).parents("#comments-list-wrapper"), commentFormUrl, function(data) {
                    if (data) {
                        data = $.parseJSON(data);
                        
                        // close a previously opened reply form
                        $("#global-comments-wrapper #comments-list-wrapper .comment-reply-form-wrapper").remove();
                        $("#global-comments-wrapper #comments-list-wrapper .reply-link-wrapper a").removeClass("active-reply");
                        $("#global-comments-wrapper #comments-list-wrapper .comment-actions-wrapper a.edit-comment").removeClass("active-edit");

                        $link.addClass("active-reply").parents(".reply-link-wrapper:first").append(data.form);
                        initReplyForms();
                    }
                    else {
                        removeAllActionsElements();
                        showNotification(accessDeniedMessage);
                    }
                }, "get", "", false);
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
            ajaxQuery($("#global-comments-wrapper #comments-list-wrapper"), actionUrl, function(data) {
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
            ajaxQuery($("#global-comments-wrapper #comments-list-wrapper"), actionUrl, function(data) {
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
     * Init delete links
     *
     * @return void
     */
    var initDeleteLinks = function()
    {
        var $wrapper = $("#global-comments-wrapper #comments-list-wrapper");

        // hide all delete links
        $wrapper.find(".comment-actions-wrapper a.delete-comment").off().hide();

        // show links only for allowed comments
        $wrapper.find(".media-body a.delete-comment").filter(function() {
            return allowDeleteComments || (allowDeleteOwnComments && $(this).parents(".media:first").hasClass("own-comment"));
        }).css("display", "inline").bind("click", function(e){
            e.preventDefault();
            var $link = $(this);

            // show a confirm popup window
            showConfirmPopup(translations.confirm_yes, translations.confirm_no, $(this), function(){
                var $parent = $link.parents(".media:first");
                var actionUrl = baseUrl + "&widget_action=delete_comment&widget_comment_id=" + $parent.attr("comment-id");

                // delete comment
                ajaxQuery($("#global-comments-wrapper #comments-list-wrapper"), actionUrl, function(data) {
                    if (data) {
                        data = $.parseJSON(data);

                        // permission denied for disapproving comments
                        if (data === false) {
                            removeDeleteElements();
                            showNotification(accessDeniedMessage);
                        }
                        else {
                            if (data.status == "success") {
                                deleteComment($parent);
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
        });
    }

    /**
     * Delete comment
     *
     * @param object $comment
     * @return void
     */
    var deleteComment = function($comment)
    {
        if (removeCommentFromMemory($comment.attr("comment-id"))) {
            $comment.slideUp(function(){
                $(this).remove();
    
                // check the comments exists
                if (!$("#global-comments-wrapper #comments-list-wrapper .media:first").length) {
                    // refresh page
                    removePaginator();
                    location.reload();
                }
            });
        }
    }

    /**
     * Init edit links
     *
     * @return void
     */
    var initEditLinks = function()
    {
        var $wrapper = $("#global-comments-wrapper #comments-list-wrapper");

        // hide all edit links
        $wrapper.find(".comment-actions-wrapper a.edit-comment").off().hide();

        // show links only for allowed comments
        $wrapper.find(".media-body a.edit-comment").filter(function() {
            return allowEditComments || (allowEditOwnComments && $(this).parents(".media:first").hasClass("own-comment"));
        }).css("display", "inline").bind("click", function(e){
            e.preventDefault();

            if ($(this).hasClass("active-edit")) {
                // delete the edit form
                $(this).removeClass("active-edit").
                    parents(".media-body:first").find(".reply-link-wrapper:first .comment-reply-form-wrapper").remove();
            }
            else {
                var $commentParent =  $(this).parents(".media:first");
                var $link = $(this);
                var commentFormUrl = baseUrl +
                        "&widget_action=get_form&widget_comment_id=" + $commentParent.attr("comment-id");

                // get comment form
                ajaxQuery($("#global-comments-wrapper #comments-list-wrapper"), commentFormUrl, function(data) {
                    if (data) {
                        data = $.parseJSON(data);

                        // show a notification message
                        if (data.message) {
                            showNotification(data.message);
                        }

                        if (data.form) {
                            // close a previously opened edit and reply forms
                            $("#global-comments-wrapper #comments-list-wrapper .comment-reply-form-wrapper").remove();
                            $("#global-comments-wrapper #comments-list-wrapper .reply-link-wrapper a").removeClass("active-reply");
                            $("#global-comments-wrapper #comments-list-wrapper .comment-actions-wrapper a.edit-comment").removeClass("active-edit");

                            // show the edit form
                            $link.addClass("active-edit");
                            var $formWrapper = $(data.form);
                            $formWrapper.find("form:first").addClass("edit-mode");

                            $commentParent.find(".reply-link-wrapper:first").append($formWrapper);
                            initReplyForms();
                        }
                    }
                    else {
                        removeAllActionsElements();
                        showNotification(accessDeniedMessage);
                    }
                }, "get", "", false);
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
        $globalWrapper.find(".reply-link-wrapper .reply-comment").remove();

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
     * Remove edit elements
     *
     * @return void
     */
    var removeEditElements = function()
    {
        var $globalWrapper = $("#global-comments-wrapper");

        $globalWrapper.find(".comment-actions-wrapper a.edit-comment").remove();
        $globalWrapper.find("#comments-list-wrapper .comment-reply-form-wrapper").remove();

        if (allowEditComments || allowEditOwnComments) {
            allowEditComments = false;
            allowEditOwnComments = false;
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
        removeEditElements();
    }

    /**
     * Init reply|edit form
     *
     * @return void
     */
    var initReplyForms = function()
    {
        $("#global-comments-wrapper").find("form").off().on("submit", function(e) {
            e.preventDefault();

            var $form = $(this);
            var editMode = $form.hasClass("edit-mode");
            var formUrl = !editMode ? baseUrl + "&widget_action=add_comment" : baseUrl + "&widget_action=edit_comment";
            var commentId = $form.parents(".media:first").attr("comment-id");

            if (typeof commentId != "undefined") {
                formUrl += "&widget_comment_id=" + commentId;
            }

            // send a form data
            ajaxQuery($("#global-comments-wrapper #comments-list-wrapper"), formUrl, function(data) {
                if (data) {
                    data = $.parseJSON(data);

                    // permission denied for adding comments
                    if (data === false) {
                        !editMode ? removeReplyElements() : removeEditElements();
                        showNotification(accessDeniedMessage);
                    }
                    else {
                        // add received comment
                        if (!editMode) {
                            if (data.comment) {
                                addComments(data.comment, true);
                            }
                        }
                        else {
                            // edit mode
                            var $commentWrapper = $("#global-comments-wrapper #comments-list-wrapper").find(".media[comment-id='" + commentId + "']:first");
 
                            if (data.comment) {
                                // replace edited comment
                                $commentWrapper.find(".comment-text:first").html(data.comment);
 
                                // replace a guest name
                                if (data.guest_name) {
                                    $commentWrapper.find(".comment-guest-name:first").text(data.guest_name);
                                }
                            }
                            else {
                                deleteComment($commentWrapper);
                            }
                        }

                        // show a notification message
                        if (data.message) {
                            showNotification(data.message);
                        }

                        var $formParents = $form.parents(".reply-link-wrapper:first");

                        // remove the reply form
                        if ($formParents.length && data.status == "success") {                           
                            editMode
                                ? $form.parents(".media-body:first").find(".comment-actions-wrapper:first a.edit-comment").removeClass("active-edit")
                                : $formParents.find("a").removeClass("active-reply");

                            $formParents.find(".comment-reply-form-wrapper").remove();
                            return;
                        }

                        // get updated form content
                        var $formContent = $(data.form);

                        // clear form values
                        if (data.status == "success") {
                            $formContent.find(".primary-data").val("");
                        }

                        if (editMode) {
                            $formContent.find("form").addClass("edit-mode");
                        }

                        // reload the current form
                        $form.parent().replaceWith($formContent);
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
            var paginatorUrl = baseUrl + "&widget_action=get_comments&widget_last_comment=" + getLastCommentFromMemory();

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
     * Init comments more
     */
    var initCommentsMore = function()
    {
        $("#global-comments-wrapper #comments-list-wrapper .media a.comment-more").off().bind("click", function(e) {
            e.preventDefault();

            // show a hidden part of comment
            $(this).parent().find(".comment-text-hidden").show();
            $(this).remove();
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
                    ? $commentsList.find(".media[comment-id='" + value.parent_id + "']:first " + ".comment-replies" + ":first").prepend($comment)
                    : $commentsList.prepend($comment);
 
                $comment.hide().css({"visibility" : "visible", "height" : "auto"}).slideDown();
                saveCommentInMemory(value.id, value.parent_id);
            }
            else {
                value.parent_id
                    ? $commentsList.find(".media[comment-id='" + value.parent_id + "']:first " + ".comment-replies" + ":first").append($comment)
                    : $commentsList.append($comment);

                saveCommentInMemory(value.id, value.parent_id, "bottom");
            }
        });

        initCommentsMore();

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

        // re init all edit links
        !allowEditComments && !allowEditOwnComments
            ? removeEditElements()
            : initEditLinks();
    }

    /**
     * Remove comment from memory
     *
     * @param integer id
     * @param object children
     * @return boolean
     */
    var removeCommentFromMemory = function(id, children)
    {
        var deleteResult = false;
        var comments = children ? children : commentsStructure;

        $.each(comments, function(index, comment) {
            var commentChildren = comment.children;

            // we've found the needed comment
            if (comment.id == id) {
                comments.splice(index, 1);

                // break the iteration
                deleteResult = true;
                return false;
            }

            // search into children
            if (commentChildren.length) {
                deleteResult = removeCommentFromMemory(id, commentChildren);

                if (deleteResult) {
                    // break the iteration
                    return false;
                }
            }
        });

        return deleteResult;
    }

    /**
     * Save comment in memory
     *
     * @param integer id
     * @param integer parentId
     * @param string topLevelDirection (top|bottom)
     * @param object children
     * @return boolean
     */
    var saveCommentInMemory = function(id, parentId, topLevelDirection, children)
    {
        var addResult = false;

        // recursive search
        if (parentId > 0) {
            $.each((children ? children : commentsStructure), function(index, comment) {
                var children = comment.children;

                // we've found needed comment
                if (comment.id == parentId) {
                    // add comment to the end
                    children.splice(children.length, 0, {
                        'id': id,
                        'children': []
                    });

                    // break the iteration
                    addResult = true;
                    return false;
                }

                // search into children
                if (children.length) {
                    addResult = saveCommentInMemory(id, parentId, topLevelDirection, children);

                    if (addResult) {
                        // break the iteration
                        return false;
                    }
                }
            });
 
            return addResult;
        }
 
        // add comment to the top
        if (!topLevelDirection || topLevelDirection === "top") {
            commentsStructure.splice(0, 0, {
                'id': id,
                'children': []
            });
        }
        else {
            // add comment to the bottom
            commentsStructure.splice(commentsStructure.length, 0, {
                'id': id,
                'children': []
            });
        }

        return true;
    }

    /**
     * Get comments object copy
     *
     * @param object children
     * @return object
     */
    var getCommentObjectCopy = function(children)
    {
        var commentsStructureCopy;

        if (!children) {
            commentsStructureCopy = commentsStructure.constructor();
            for (var attr in commentsStructure) {
                if (commentsStructure.hasOwnProperty(attr)) {
                    commentsStructureCopy[attr] = commentsStructure[attr];
                }
            }

            return commentsStructureCopy;
        }

        commentsStructureCopy = children.constructor();
        for (var attr in children) {
            if (children.hasOwnProperty(attr)) {
                commentsStructureCopy[attr] = children[attr];
            }
        }

        return commentsStructureCopy;
    }

    /**
     * Get last comment from memory
     *
     * @param object children
     * @return integer
     */
    var getLastCommentFromMemory = function(children)
    {
        var lastComment = getCommentObjectCopy(children);
        lastComment = lastComment.pop();

        if (lastComment) {
            // check for children
            if (!lastComment.children.length) {
                return lastComment.id;
            }

            // process children
            return getLastCommentFromMemory(lastComment.children);
        }
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
     * Allow edit comments
     *
     * @param boolean allowed
     * @return object
     */
    this.allowEditComments = function(allowed)
    {
        allowEditComments = allowed;
        return this;
    }

    /**
     * Allow edit own comments
     *
     * @param boolean allowed
     * @return object
     */
    this.allowEditOwnComments = function(allowed)
    {
        allowEditOwnComments = allowed;
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
        initCommentsMore();

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

        // init edit elements
        if (!allowEditComments && !allowEditOwnComments) {
            removeEditElements();
        }
        else {
            initEditLinks();
        }

        // build comments structure in a memory
        $("#global-comments-wrapper #comments-list-wrapper .media").each(function(key, comment) {
            saveCommentInMemory($(comment).
                    attr("comment-id"), $(comment).attr("comment-parent"), "bottom");
        });

        // hide the empty comments wrappers
        if (commentsStructure.length) {
            removeEmptyCommentsWrapper();
        }
    }
}