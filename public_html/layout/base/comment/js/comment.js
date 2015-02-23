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

            // collect extra params
            var extraExtions = "&action=add_comment";
            var replyId = $(this).parents(".media:first").attr("comment-id");

            if (typeof replyId != "undefined") {
                extraExtions += "&reply_id=" + replyId;
            }

            // send a form data
            ajaxQuery($($form).parent(), baseUrl, function(data) {
                data = $.parseJSON(data);

                // permission denied
                if (data === false) {
                    // remove all opened reply wrappers
                    var $globalWrapper = $($form).parents(globalCommentsWrapper);
                    $globalWrapper.find(replyWrapper).remove();
                    $globalWrapper.find(replylinkWrapper).remove();
                }
                else {
                    // reload the current form
                    if (data.status != "success") {
                        $($form).replaceWith(data.form);
                        initReplyForms();
                    }
                    else {
                        // show a message and remove the reply form
                        var $formParents = $($form).parents(replylinkWrapper + ":first");
    
                        $formParents.find("a").removeClass("active-reply");
                        $formParents.find(replyWrapper).remove();                    
                    }
                }
            }, 'post', $(this).serialize() + extraExtions, false);
        });
    }

    /**
     * Init 
     */
    this.init = function()
    {
        initReplyLinks();
        initReplyForms();
    }
}