$(document).ready(function() {
    $("#user-last-comments .media a.comment-more, #last-comments .media a.comment-more").bind("click", function(e) {
        e.preventDefault();

        // show a hidden part of comment
        $(this).parent().find(".comment-text-hidden").show();
        $(this).remove();
    });
});