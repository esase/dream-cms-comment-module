function Comment(options)
{
    /**
     * Add comment url
     * @var string
     */
    var addCommentUrl = options['add_comment'];

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

        // replace all ids
        $replyForm.find("[id]").each(function() {
            this.id = this.id + "-clone";
        });

        // replace labels
        $replyForm.find("label").each(function() {
            $(this).attr("for",  $(this).attr("for") + "-clone");
        });

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
     * Init reply form
     *
     * @return void
     */
    var initReplyForm = function()
    {
        $(globalCommentsWrapper).find("form").off().on("submit", function(e) {
            e.preventDefault();

            // send a form data
            $.post(addCommentUrl, $(this).serialize(), function(data) {
            });
        });
    }

    /**
     * Init 
     */
    this.init = function()
    {
        initReplyLinks();
        initReplyForm();
    }
}