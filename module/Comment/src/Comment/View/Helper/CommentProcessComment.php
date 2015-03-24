<?php
namespace Comment\View\Helper;

use Zend\View\Helper\AbstractHelper;

class CommentProcessComment extends AbstractHelper
{
    /**
     * Process comment
     *
     * @param sting $comment
     * @return string
     */
    public function __invoke($comment)
    {
        return nl2br($this->processLinks(strip_tags($comment)));
    }

    /**
     * Process links
     * 
     * @param string $comment
     * @return string
     */
    protected function processLinks($comment)
    {
        $regexUrl = '/(?P<urls>(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/';

        $urls = [];
        preg_match_all($regexUrl, $comment, $urls);

        if(!empty($urls['urls'])) {
            foreach ($urls['urls'] as $url) {
                $comment = str_replace($url, $this->getView()->
                        partial('comment/widget/_comment_url', ['url' => $url]), $comment);
            }
        }

        return $comment;
    }
}