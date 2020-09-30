# BehatCrawler

The BehatCrawler is [Behat](https://github.com/Behat/Behat),[MinkExtension](https://github.com/Behat/MinkExtension) and [Selenium2Driver](https://github.com/minkphp/MinkSelenium2Driver) extension that crawl a given URL and execute user-defined function in each Crawled Page. 

Multiple options for crawling are available, see [available options](#available).

## Installation

```shell
composer require piopi/behatcrawler
```

## Usage

First start by importing the extension, to your Feature Context (or any of your Context):

```php
use Behat\Crawler\Crawler;
```

Create your Crawler object with the default configuration:

**The crawler is only compatible at this time with [Selenium2Driver](https://github.com/minkphp/MinkSelenium2Driver)**

```Php
//$crawler=New Crawler(BehatSession);
$crawler= New Crawler($this->getSession());
```

Or with custom settings (as an array), see the following table for all the available options:

```php
$crawler= New Crawler($this->getSession(),["internalLinksOnly"=>true,"HTMLOnly"=>true,'MaxCrawl'=>20]);
```

#### Available options: (More functionalities coming soon) <a name="available"></a>

| Option            | Description                                                  | Default Value |
| ----------------- | ------------------------------------------------------------ | ------------- |
| Depth             | Maximum depth that can be crawled from url                   | 0 (unlimited) |
| MaxCrawl          | Maximum number of crawl                                      | 0 (unlimited) |
| HTMLOnly          | Will only crawl html/xhtml pages                             | true          |
| internalLinksOnly | Will crawl internal links only (links with same Domaine name as the initial url) | true          |
| waitForCrawl      | Will wait for the crawler to finish crawling before throwing any exception originating from the user defined functions. (Compile a list of all exception founds with their respective location) | false         |

**Option can either be set in the constructor or with the appropriate getters/setters:**

```Php
 $crawler= New Crawler($this->getSession(),["MaxCrawl"=>10]);
//or
$crawler->setMaximumCrawl(10);
```

#### Start Crawling

After creating and setting up the crawler, you can start crawling by passing your function as an argument:

Please refer to the PHP [Callables documentation](https://www.php.net/manual/en/language.types.callable.php) for more details.

**Examples**:

> Closure::fromCallable is used to pass by parameter private function

```php
//function 1 is a private function
$crawler->startCrawling(Closure::fromCallable([$this, 'function1']));
//function 2 is a public class function
$crawler->startCrawling([$this, 'function1']);
```

For functions with one or more arguments, they can be passed as the following:

```Php
$crawler->startCrawling(Closure::fromCallable([$this, 'function3']),[arg1]);
$crawler->startCrawling(Closure::fromCallable([$this, 'function4']),[arg1,arg2]);
```

### Usage Example

```php
use Behat\Crawler\Crawler;
//Crawler with different settings
$crawler= New Crawler($this->getSession(),["internalLinksOnly"=>true,"HTMLOnly"=>true,'MaxCrawl'=>20,"waitForCrawl"=>true]);
//Function without arguments
$crawler->startCrawling(Closure::fromCallable([$this, 'function1'])); //Will start crawling
//Function with one or more argument
$crawler->startCrawling(Closure::fromCallable([$this, 'function2']),[arg1,arg2]);

```

**In a Behat step function:**

```Php
   /**
     * @Given /^I crawl the website with a maximum of (\d+) level$/
     */
    public function iCrawlTheWebsiteWithAMaximumOfLevel($arg1)
    {
        $crawler= New Crawler($this->getSession(),["Depth"=>$arg1]);
        $crawler->startCrawling([$this, 'test']);
    }
```

### Copyright

Copyright (c) 2020 Mostapha El Sabah elsabah.mostapha@gmail.com

## Maintainers

Mostapha El Sabah [Piopi](https://github.com/piopi)

