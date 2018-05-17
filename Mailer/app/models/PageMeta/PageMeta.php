<?php

namespace Remp\MailerModule\PageMeta;

class PageMeta
{
    private $transport;

    private $content;

    public function __construct(TransportInterface $transport, ContentInterface $content)
    {
        $this->transport = $transport;
        $this->content = $content;
    }

    /**
     * @param $url
     * @return Meta|boolean
     */
    public function getPageMeta($url)
    {
        $content = $this->transport->getContent($url);
        if (!$content) {
            return false;
        }

        return $this->content->parseMeta($content);
    }
}
