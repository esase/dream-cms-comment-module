function Comment(options)
{
    /**
     * Base url
     * @var string
     */
    var baseUrl = options['base_url'];

    /**
     * Global comments wrapper
     * @var string
     */
    var globalCommentsWrapper = "#global-comments-wrapper";

    /**
     * Action denied wrapper
     * @var string
     */
    var actionDeniedWrapper = "#comments-action-denied";

    /**
     * Reply comment form wrapper
     * @var string
     */
    var replyFormWrapper = ".comment-reply-form-wrapper";

    /**
     * Replies wrapper
     * @var string
     */
    var repliesWrapper = '.comment-replies';

    /**
     * Comments list wrapper
     * @var string
     */
    var commentsListWrapper = "#comments-list-wrapper";

    /**
     * Reply link wrapper
     * @var string
     */
    var replylinkWrapper = ".reply-link-wrapper";

    /**
     * Paginator wrapper
     * @var string
     */
    var paginatorWrapper = "#comments-paginator-wrapper";

    /**
     * Last comment id
     * @var integer
     */
    var lastCommentId;

    /**
     * Show action denied
     *
     * @return void
     */
    var showActionDenied = function()
    {
        $(globalCommentsWrapper + " > " + actionDeniedWrapper).show();
    }

    /**
     * Clone reply form
     *
     * @return object
     */
    var cloneReplyForm = function()
    {
        var $replyForm = $(globalCommentsWrapper + " > " + replyFormWrapper).clone(true);

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
        $(globalCommentsWrapper + " " + commentsListWrapper + " " + replylinkWrapper + " a").off().bind("click", function(e){
            e.preventDefault();

            if ($(this).hasClass("active-reply")) {
                // delete the reply form
                $(this).removeClass("active-reply").parents(replylinkWrapper).find(replyFormWrapper).remove();
            }
            else {
                // close a previously opened reply form
                $(globalCommentsWrapper + " " + commentsListWrapper + " " + replyFormWrapper).remove();
                $(globalCommentsWrapper + " " + commentsListWrapper + " " + replylinkWrapper + " a").removeClass("active-reply");

                $(this).addClass("active-reply").parents(replylinkWrapper).append(cloneReplyForm()).html();
            }
        });
    }

    /**
     * Close reply form
     *
     * @param object $form
     * @return void
     */
    var closeReplyForms = function($form)
    {
        var $globalWrapper = $($form).parents(globalCommentsWrapper);
        $globalWrapper.find(replyFormWrapper).remove();
        $globalWrapper.find(replylinkWrapper).remove();
    }

    /**
     * Init reply forms
     *
     * @return void
     */
    var initReplyForms = function()
    {
        $(globalCommentsWrapper).find("form").off().on("submit", function(e) {
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

                    // permission denied
                    if (data === false) {
                        closeReplyForms($form);
                        showActionDenied();
                    }
                    else {
                        // add received comment
                        if (data.comment) {
                            addComments(data.comment, true);
                        }

                        var $formParents = $($form).parents(replylinkWrapper + ":first");

                        // remove the reply form
                        if ($formParents.length && data.status == "success") {
                            $formParents.find("a").removeClass("active-reply");
                            $formParents.find(replyFormWrapper).remove();

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
                    closeReplyForms($form);
                    showActionDenied();
                }
            }, 'post', $(this).serialize(), false);
        });
    }

    /**
     * Init paginator
     */
    var initPaginator = function()
    {
        $(globalCommentsWrapper).find(paginatorWrapper).off().bind("click", function(e) {
            e.preventDefault();
            var paginatorUrl = baseUrl + "&widget_action=get_comments&widget_last_comment=" + lastCommentId;

            // get next comments
            ajaxQuery($(commentsListWrapper), paginatorUrl, function(data) {
                if (data) {
                    data = $.parseJSON(data);

                    // append comments
                    if (typeof data.comments != "undefined" && data.comments) {
                        addComments(data.comments);
                    }

                    // remove the pagination wrapper we have reached the end
                    if (typeof data.comments == "undefined" || !data.comments || !data.show_paginator) {
                        $(paginatorWrapper).remove();
                    }
                }
                else {
                    $(paginatorWrapper).remove();
                    showActionDenied();
                }
            }, 'get', '', false);
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
        $commentsList = $(commentsListWrapper);

        $.each(comments, function(key, value) {
            // add the comment
            if (typeof isOwnReply != "undefined" && isOwnReply) {
                if (value.parent_id) {
                    $commentsList.find(".media[comment-id='" + value.parent_id + "'] " + repliesWrapper + ":first").prepend(value.comment);

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
                    ? $commentsList.find(".media[comment-id='" + value.parent_id + "'] " + repliesWrapper + ":first").append(value.comment)
                    : $commentsList.append(value.comment);
            }
        });

        initReplyLinks();
    }

    /**
     * Init access denied
     */
    var initAccessDenied = function()
    {
        $(globalCommentsWrapper + " > " + actionDeniedWrapper).find(".close").bind("click", function(){
            $(this).parent().hide();
        });
    }

    /**
     * Init 
     */
    this.init = function()
    {
        initReplyLinks();
        initReplyForms();
        initPaginator();
        initAccessDenied();

        // get a last comment id
        $lastComment = $(globalCommentsWrapper).find(".media:last");
        lastCommentId = $lastComment.length
            ? $lastComment.attr("comment-id")
            : '';
    }
}