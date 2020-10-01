<?php


namespace Behat\Crawler;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Session;
use GuzzleHttp\Psr7\Uri;
use WebDriver\Exception\NoAlertOpenError;

/**
 * Class Crawler
 * @package Behat\Crawler
 */
class Crawler
{
    /** @var Session */
    private $session;
    /** @var CrawlerQueue */
    private CrawlerQueue $crawlQueue;
    /**
     * @var int
     */
    private int $crawledUrlCount = 0;
    private $maximumCrawl=0;
    private Uri $baseUrl;
    private $maximumDepth=0;
    private $htmlOnly=true;
    private $internalOnly=true;
    private $waitForCrawl=true;


    /**
     * Crawler constructor.
     */
    public function __construct(Session $session, $parameters= [])
    {

        $this->crawlQueue = new CrawlerQueue();
        $this->session = $session;
        if(key_exists('Depth',$parameters)){
            $this->maximumDepth=$parameters["Depth"];
        }
        if(key_exists('MaxCrawl',$parameters)){
            $this->maximumCrawl=$parameters["MaxCrawl"];
        }
        if(key_exists('HTMLOnly',$parameters)){
            $this->htmlOnly=$parameters["HTMLOnly"];
        }
        if(key_exists('internalLinksOnly',$parameters)){
            $this->internalOnly=$parameters["internalLinksOnly"];
        }
        if(key_exists('waitForCrawl',$parameters)){
            $this->waitForCrawl=$parameters["waitForCrawl"];
        }
        $this->baseUrl= new Uri($session->getCurrentUrl());
        //$this->robotsTxt = $this->createRobotsTxt($session->getCurrentUrl());
    }
    public function retrieveLinks(CrawlUrl $crawlUrl ){
        //Get the page's HTML source using file_get_contents.
        $js = <<<JS
    (function myFunction() {
        var urls = [];
        for(var i = document.links.length; i --> 0;)
        if( !'$this->internalOnly' || document.links[i].hostname === location.hostname && !document.links[i].href.includes('#')){
            urls.push(document.links[i].href);
            }
	    return urls;
     
  })();
JS;
        $links = $this->session->getDriver()->evaluateScript($js);
        //Extract the links from the HTML.
        //$links = $htmlDom->getElementsByTagName('a');
        //Array that will contain our extracted links.
        $extractedLinks = array();

        //Loop through the DOMNodeList.
        //We can do this because the DOMNodeList object is traversable.
        foreach($links as $linkHref){


            //If the link is empty, skip it and don't
            //add it to our $extractedLinks array
            if(strlen(trim($linkHref)) == 0){
                continue;
            }
            //Skip if it is a hashtag / anchor link.
            if(str_contains($linkHref, '#')){
                continue;
            }elseif($linkHref[0] =='/'){
                $linkHref=$crawlUrl->url->getScheme()."://".$crawlUrl->url->getHost().$linkHref;
            }

            //Add the link to our $extractedLinks array.
            if(str_contains($linkHref,"http")){
                $extractedLink=new Uri($linkHref);
                if($extractedLink->getHost()===$crawlUrl->url->getHost()){
                    $extractedLinks[] = $linkHref;
                }elseif (!$this->internalOnly){
                    $extractedLinks[] = $linkHref;
                }
                else{
                    unset($extractedLink);
                }
            }
        }
        return $extractedLinks;
    }


    public function startCrawling($function,$funcParameters=[]){

            $crawlUrl = CrawlUrl::create($this->baseUrl, null, 0);
            $this->addToCrawlQueue($crawlUrl);
            $this->startCrawlingQueue($function, $funcParameters);

    }
    public function addToCrawlQueue(CrawlUrl $crawlUrl): Crawler
    {
        if ($this->getCrawlQueue()->has($crawlUrl->url)) {
            return $this;
        }

        $this->crawlQueue->add($crawlUrl);
        return $this;
    }
    protected function getSession(): Session
    {
        return $this->session;
    }

    /**
     * @return CrawlerQueue
     */
    public function getCrawlQueue(): CrawlerQueue
    {
        return $this->crawlQueue;
    }

    private function startCrawlingQueue($function,$funcParameters=[])
    {
        while ($crawlUrl = $this->crawlQueue->getFirstPendingUrl()) {
            $isHtml=false;
            try {
                stream_context_set_default( [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);
                $request =get_headers((string) $crawlUrl->url, 1);
                if(is_array($request)) { //If the page is not reachable $request is not an array
                    if (array_key_exists("Content-Type", $request) || array_key_exists("content-type", $request)) { //check if the Content-type exist in the response header
                        $type = array_key_exists("Content-Type", $request) ? $request["Content-Type"] : $request["content-type"]; //some html response headers have the key in lowercase
                        if (is_array($type)) { //In some responses Content-type is an array
                            foreach ($type as $elem) {
                                $isHtml = str_contains($elem, 'html');
                            }
                        } else {
                            $isHtml = str_contains($type, 'html');
                        }
                    }
                }else{
                    $request=['HTTP/1.1 404 Not Found']; //if no request was found
                }
            } catch (\Throwable $e) {
                $isHtml=false; //Might need to be rewritten to retry to reload Page if something bad happened
            }
            if ($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl)) {
                continue;
            }elseif(($this->htmlOnly && !$isHtml) || substr($request[0], 9, 3)!='200' ){
                $this->crawlQueue->markAsProcessed($crawlUrl);
                continue;
            }
            elseif (($crawlUrl->getDepth() > $this->maximumDepth && $this->maximumDepth!=0) || ($this->maximumCrawl!=0 && $this->maximumCrawl<=$this->crawledUrlCount)){
                break;
            }
            else {

                $this->visitUrl((string)$crawlUrl->url);
                echo "\nCurrent Url:".(string)$crawlUrl->url."\n";
                ob_flush();
                try {
                    if (empty($funcParameters)) {
                        call_user_func($function);
                    } else {
                        call_user_func_array($function, $funcParameters);
                    }
                }catch (\Throwable $e){
                    if($this->waitForCrawl) {
                        echo "\n" . $e->getMessage() . "\n";
                        ob_flush();
                    }else{
                        throw $e;
                    }
                }
                $this->crawledUrlCount++;
                $urls = $this->retrieveLinks($crawlUrl);
                if (!empty($urls)) {
                    foreach ($urls as $url) {
                        $this->addToCrawlQueue(CrawlUrl::create(new Uri($url), $crawlUrl->url, $crawlUrl->getDepth() + 1));
                    }
                }
                //echo $this->crawlQueue->count()."\n";
                //ob_flush();
                $this->crawlQueue->markAsProcessed($crawlUrl);
            }


        }


    }

    private function visitUrl(string $url){
        $this->getSession()->visit($url);
        $driver = $this->getSession()->getDriver();
        if ($driver instanceof Selenium2Driver) {
            for ($i = 0; $i < 10; $i++) {
                try {
                    $driver->getWebDriverSession()->accept_alert();
                    break;
                }
                catch (NoAlertOpenError $e) {

                }
            }
        }
        $this->getSession()->wait(0, "document.readyState === 'complete'");
    }
    /**
     * @param int|mixed $maximumCrawl
     */
    public function setMaximumCrawl($maximumCrawl): void
    {
        $this->maximumCrawl = $maximumCrawl;
    }

    /**
     * @param int|mixed $maximumDepth
     */
    public function setMaximumDepth($maximumDepth): void
    {
        $this->maximumDepth = $maximumDepth;
    }

    /**
     * @param bool|mixed $htmlOnly
     */
    public function setHtmlOnly($htmlOnly): void
    {
        $this->htmlOnly = $htmlOnly;
    }

    /**
     * @param bool $internalOnly
     */
    public function setInternalOnly(bool $internalOnly): void
    {
        $this->internalOnly = $internalOnly;
    }


}