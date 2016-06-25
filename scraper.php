<?php
#PHP based webscraper for CNN news articles, Written by Caleb Banzhaf. Last modified: Fri June 24th 7:27pm
#Usage: Input a URL to the file like: "scraper.php?url=http://www.cnn.com/somenewsarticle.html"

#declare the basic content scraper functionality
class BaseScraper
{
	public $spaner;
	public $docSave;
	function __construct($a1, $a2)
	{
		#Use the Curl extension to query CNN and get back a page of news
		$url     = $_GET["url"];
		$ch      = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$html = curl_exec($ch);
		curl_close($ch);
		
		#Create a DOM parser object
		$dom = new DOMDocument();
		
		#Parse the HTML from CNN.
		#The @ before the method call suppresses any warnings that
		#loadHTML might throw because of invalid HTML in the page.
		@$dom->loadHTML($html);
		$finder = new DomXPath($dom);
		$classname     = $a1;
		$type          = $a2;
		$this->spaner  = $finder->query("//*[contains(@$type, '$classname')]");
		$this->docSave = new DOMDocument('1.0');
	}
}

#Extend the content scraper for grabbing the heading
class heading extends BaseScraper
{
	function __construct()
	{
		#Pass the relevant html indentifiers to the parent class
		parent::__construct('pg-headline', 'class');
		$domNode = $this->docSave->importNode($this->spaner->item(0), true);
		$this->docSave->appendChild($domNode);
		$heading = strip_tags($this->docSave->saveHTML());
		#final output for the heading
		echo "Heading: " . $heading . "\r\n\r\n";
	}
}

#Extend the content scraper for grabbing the image
class image extends BaseScraper
{
	function __construct()
	{
		#Pass the relevant html indentifiers to the parent class
		parent::__construct('media__image', 'class');
		$image = $this->spaner->item(0)->getAttribute("data-src-large");
		#Only output if there is an image for the article
		if ($image != null) {
			#final output for the image
			echo "Image: " . $image . "\r\n";
		}
	}
}

#Extend the content scraper for grabbing the body
class body extends BaseScraper
{
	function __construct()
	{
		#Pass the relevant html indentifiers to the parent class
		parent::__construct('articleBody', 'itemprop');
		$domNode = $this->docSave->importNode($this->spaner->item(0), true);
		$this->docSave->appendChild($domNode);
		#remove any inline scripts, styles and rich content, then strip out the remaining content's html tags
		$body = strip_tags(preg_replace('/(<script[^>]*>.+?<\/script>|<style[^>]*>.+?<\/style>|<h4 class="video__end-slate__terti.+?<\/h4>|<a href="\/videos.+?<\/a>|<div class="js-media__video.+?<\/div>|<h3 class="cd__headline-title".+?<\/h3>|<div class="read-more-button".+?<\/div>|<div class="video__end-slate__secondary".+?<\/div>|<div class="media__caption el__storyelement__title".+?<\/div>|<div class="video__end-slate__engage__wrapper".+?<\/div>)/s', '', $this->docSave->saveHTML()));
		#final output for the body
		echo "Body: " . $body;
	}
}

#Remove all illegal characters from a url
$url = filter_var($_GET["url"], FILTER_SANITIZE_URL);

#The @ before the method call suppresses any warnings that may be thrown as they are handled later
@$headers = get_headers($url);

#Check if user supplied a URL and if it points to CNN
if ($url == null or strpos($url, 'cnn.com') == false or strpos($url, 'html') == false or strpos($headers[0],"200") == false) {
	echo "Invalid URL, No input URL, or URL not a CNN article";
} else {
	
	$ScrapedHeading = new heading();
	$ScrapedImage   = new image();
	$ScrapedBody    = new body();

}
?>