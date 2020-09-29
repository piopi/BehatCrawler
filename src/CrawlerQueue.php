<?php


namespace Behat\Crawler;


use Psr\Http\Message\UriInterface;

class CrawlerQueue
{

    /**
     * All known URLs, indexed by URL string.
     *
     * @var CrawlUrl[]
     */
    protected $urls = [];

    /**
     * Pending URLs, indexed by URL string.
     *
     * @var CrawlUrl[]
     */
    protected $pendingUrls = [];

    public function add(CrawlUrl $url): CrawlerQueue
    {
        $urlString = (string) $url->url;

        if (! isset($this->urls[$urlString])) {
            $url->setId($urlString);
            $this->urls[$urlString] = $url;
            $this->urls[$urlString]->setDepth($url->getDepth());
            $this->pendingUrls[$urlString] = $url;
            $this->pendingUrls[$urlString]->setDepth($url->getDepth());

        }

        return $this;
    }

    public function hasPendingUrls(): bool
    {
        return (bool) $this->pendingUrls;
    }

    public function getUrlById($id): CrawlUrl
    {
        if (! isset($this->urls[$id])) {
            throw new \Exception("Crawl url {$id} not found in collection.");
        }

        return $this->urls[$id];
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $url): bool
    {
        $url = (string) $url->url;

        if (isset($this->pendingUrls[$url])) {
            return false;
        }

        if (isset($this->urls[$url])) {
            return true;
        }

        return false;
    }

    public function markAsProcessed(CrawlUrl $crawlUrl)
    {
        $url = (string) $crawlUrl->url;

        unset($this->pendingUrls[$url]);
    }

    /**
     * @param CrawlUrl|UriInterface $crawlUrl
     *
     * @return bool
     */
    public function has($crawlUrl): bool
    {
        if ($crawlUrl instanceof CrawlUrl) {
            $url = (string) $crawlUrl->url;
        } elseif ($crawlUrl instanceof UriInterface) {
            $url = (string) $crawlUrl;
        } else {
            throw new \Exception("Invalid Type exception");
        }

        return isset($this->urls[$url]);
    }

    public function getFirstPendingUrl(): ?CrawlUrl
    {
        foreach ($this->pendingUrls as $pendingUrl) {
            return $pendingUrl;
        }

        return null;
    }

    /**
     * Count number of urls in the Total array
     * @return int
     */
    public function count():int{
        return count($this->urls);
    }
}