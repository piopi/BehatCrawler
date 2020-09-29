<?php


namespace Behat\Crawler;


use Psr\Http\Message\UriInterface;

class CrawlUrl
{

    /** @var UriInterface */
    public $url;

    /** @var UriInterface */
    public $foundOnUrl;

    /** @var mixed */
    protected $id;
    /** @var int */

    protected $depth;


    public static function create(UriInterface $url, ?UriInterface $foundOnUrl = null, $depth=-1,$id = null)
    {
        $static = new static($url, $foundOnUrl);

        if ($id !== null) {
            $static->setId($id);

        }
        if ($depth !== null) {
        $static->setDepth($depth);}

        return $static;
    }

    protected function __construct(UriInterface $url, $foundOnUrl = null)
    {
        $this->url = $url;
        $this->foundOnUrl = $foundOnUrl;
    }

    /**
     * @return mixed|null
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @param int $depth
     */
    public function setDepth(int $depth): void
    {
        $this->depth = $depth;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

}