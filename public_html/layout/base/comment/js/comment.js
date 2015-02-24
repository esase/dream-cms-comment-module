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
     * Reply comment wrapper
     * @var string
     */
    var replyWrapper = ".comment-reply-wrapper";

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
     * Clone reply form
     *
     * @return object
     */
    var cloneReplyForm = function()
    {
        var $replyForm = $(globalCommentsWrapper + " > " + replyWrapper).clone(true);

        // remove all erros and unset entered values
        $replyForm.find("ul").remove();
        $replyForm.find(":input:not(input[type=submit],input[name='csrf'],input[name='captcha[id]'])").val("");
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
                $(this).removeClass("active-reply").parents(replylinkWrapper).find(replyWrapper).remove();
            }
            else {
                // close a previously opened reply form
                $(globalCommentsWrapper + " " + commentsListWrapper + " " + replyWrapper).remove();
                $(globalCommentsWrapper + " " + commentsListWrapper + " " + replylinkWrapper + " a").removeClass("active-reply");

                $(this).addClass("active-reply").parents(replylinkWrapper).append(cloneReplyForm()).html();
            }
        });
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
            var formUrl = baseUrl + "&action=add_comment";

            // collect extra params
            var replyId = $(this).parents(".media:first").attr("comment-id");

            if (typeof replyId != "undefined") {
                formUrl += "&reply_id=" + replyId;
            }

            // send a form data
            ajaxQuery($($form).parent(), formUrl, function(data) {
                data = $.parseJSON(data);

                // permission denied
                if (data === false) {
                    // TODO: SHOW AN EXCEPTION
                    // remove all opened reply wrappers
                    var $globalWrapper = $($form).parents(globalCommentsWrapper);
                    $globalWrapper.find(replyWrapper).remove();
                    $globalWrapper.find(replylinkWrapper).remove();
                }
                else {
                    var $formParents = $($form).parents(replylinkWrapper + ":first");

                    // remove the reply form
                    if ($formParents.length && data.status == "success") {
                        $formParents.find("a").removeClass("active-reply");
                        $formParents.find(replyWrapper).remove();
                        return;
                    }

                    // reload the current form
                    $($form).replaceWith(data.form);
                    initReplyForms();
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
            var lastCommentId = $(globalCommentsWrapper).find(".media:last").attr("comment-id");
            var paginatorUrl = baseUrl + "&action=get_comments&last_comment=" + lastCommentId;

            // get next comments
            ajaxQuery($(commentsListWrapper), paginatorUrl, function(data) {
                alert(data);
            }, 'get', '', false);
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
    }
}