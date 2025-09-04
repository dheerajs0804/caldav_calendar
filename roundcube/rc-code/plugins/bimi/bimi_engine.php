<?php

/**
 * @license GNU GPLv3+
 * @author Craig Andrews <candrews@integralblue.com>
 */
class bimi_engine
{
    //private string $email;
    private $email;
    //private string $binary;
    private $binary;

    private $expiry_time = 3600 ;

    static private $debug ;

    const MIME_TYPE = 'image/svg+xml';
    const CACHE_MISS_VALUE = 'NOT FOUND';

    /**
     * Class constructor
     *
     * @param string $email   email address
     */
    public function __construct($email,$debug)
    {
	self::$debug = $debug ;
	
	/*if(self::$debug === null) {
                self::$debug = $this->rc->config->get('bimi_debug');
        }*/

        $this->email  = $email;
        $this->retrieve();
    }

    /**
     * Returns image mimetype
     */
    public function getMimetype()
    {
        return self::MIME_TYPE;
    }

    /**
     * Returns the image in binary form
     */
    public function getBinary()
    {
        return $this->binary;
    }

    /**
     * Sends the image to the browser
     */
    public function sendOutput()
    {
	self::debug_log("Start sendOutput.");

        if ($this->binary) {
	    self::debug_log("Binary output available : ".$this->binary);

            $rcmail = rcmail::get_instance();
            $rcmail->output->future_expire_header(10 * 60);

            header('Content-Type: ' . self::MIME_TYPE);
            header('Content-Size: ' . strlen($this->binary));
            echo $this->binary;

            return true;
        }
	else
	{
		self::debug_log("Binary output not available");
	}

        return false;
    }

    /**
     * BIMI retriever
     */
    private function retrieve()
    {
        $domain = explode("@",$this->email);
	if(sizeof($domain) >= 2){
            $domain = $domain[sizeof($domain)-1];
	    $this->binary = $this->cache_get_bimi_image($domain);
	}
	else {
	    $this->binary = null;
	}
    }

    public function retrieve_bimi_url(): string
    {
	self::debug_log("Start retrieve_bimi_url.");

        $domain = explode("@",$this->email);
	self::debug_log("Email split to array.".$domain);

        if(sizeof($domain) >= 2){
            $domain = $domain[sizeof($domain)-1];
	    self::debug_log("Domain=".$domain);
	
            $bimi_url  = $this->cache_get_bimi_url1($domain);
        }
        else {
	    self::debug_log("Domain not found in email. Setting bimi url to null");		
            $bimi_url = null;
        }

	self::debug_log("Returning bimi url = ".$bimi_url);	
	return $bimi_url ;
    }

    /**
     * Using the cache, given a domain, returns the BIMI image. The image is always SVG XML. Returns null if no image could be retrieved.
     */
    private function cache_get_bimi_url1(string $domain): string
    {
        self::debug_log("Start cache_get_bimi_url1.");


	// Generate a cache key for the data you want to store
	$key = md5($domain);
        self::debug_log("Cache key for domain=".$key);

	// Check if the cache file exists and is not expired
	$cache_file = '/var/www/html/roundcubemail/plugins/bimi/cache/' . $key . '.cache';
        self::debug_log("Cache file for domain=".$cache_file);

	if (file_exists($cache_file) ) {
		
	    $current_time=time();
	    self::debug_log("Current time=".$current_time);

	    $cache_file_mtime = filemtime($cache_file) ;
            self::debug_log("cache_file_mtime=".$cache_file_mtime);

	    $time_difference = $current_time - $cache_file_mtime ;
	    self::debug_log("time_difference=".$time_difference);
	    
	    self::debug_log("expiry_time=".$this->expiry_time);
		
	    if( $time_difference < $this->expiry_time  ) {
	    	// If the cache file exists and is not expired, retrieve the data from it
	    	$data = file_get_contents($cache_file);
            	self::debug_log("Cache data=".$data);
	    }
	}

	//if url not found in cache, get it from dns
	if ( empty($data) ) {
	    // If the cache file does not exist or is expired, generate the data and store it in the cache
	    $data = $this->get_bimi_url($domain);
            self::debug_log("URL from dns=".$data);
	    
	    if( !empty($data) ) {
	    	if( file_put_contents($cache_file, $data) == false ) {
			$error_message = error_get_last()['message'];
			self::debug_log("Failed to write to file. Error=".$error_message);
	    	} else {
			self::debug_log("Wrote to cache file successfully.");
	    	}
	    } else {
		self::debug_log("Empty url. So not caching.");
	    }
	}


        return $data;
    }


    /**
     * Using the cache, given a domain, returns the BIMI image. The image is always SVG XML. Returns null if no image could be retrieved.
     */
    private function cache_get_bimi_url(string $domain): string
    {
	self::debug_log("Start cache_get_bimi_url.");

        $rcmail = rcmail::get_instance();
        $cache = $rcmail->get_cache_shared('bimi');
	self::debug_log("Cache object=".$cache);

        if ($cache && $cached_data=$cache->get($domain)) {
	    self::debug_log("Cache object found and domain entry found in cache");
	
            if($cached_data==self::CACHE_MISS_VALUE) {
	        self::debug_log("Cache value is cache miss value");
                return null;
            }
            else {
		self::debug_log("Bimi url found in cache = ".$cached_data);
                return $cached_data;
            }
        }
        else {
	    self::debug_log("Domain entry not found in cache");
	
            $data = $this->get_bimi_url($domain);
	    self::debug_log("Got bimi url from dns=".$data);
	
            if($data == null) {
		self::debug_log("Found null bimi url");
                $cached_data=self::CACHE_MISS_VALUE;
            }
	    else	
	    {
		self::debug_log("Found bimi url=".$data.". Setting to cache var");
		$cached_data=$data;
	    }

            if ($cache) {
		self::debug_log("Setting bimi url in cache with domain key");
                $cache->set($domain, $cached_data);
            }
            
	    return $data;
        }
    }


    /**
     * Using the cache, given a domain, returns the BIMI image. The image is always SVG XML. Returns null if no image could be retrieved.
     */
    private function cache_get_bimi_image(string $domain): string
    {
        $rcmail = rcmail::get_instance();
	$cache = $rcmail->get_cache_shared('bimi');
	if ($cache && $cached_data=$cache->get($domain)) {
	    if($cached_data==self::CACHE_MISS_VALUE) {
	        return null;
            }
	    else {
	        return $cached_data;
	    }
        }
	else {
            $data = $this->get_bimi_image($domain);
            if($data == null) {
	        $cached_data=self::CACHE_MISS_VALUE;
	    }
	    if ($cache) {
	        $cache->set($domain, $cached_data);
            }
	    return $data;
	}
    }

    /**
     * Given a domain, returns the BIMI image. The image is always SVG XML. Returns null if no image could be retrieved.
     */
    private function get_bimi_image(string $domain): string
    {
        if($bimi_url = $this->get_bimi_url($domain)) {

		echo "bimi url = ".$bimi_url ;
	    $rcmail = rcmail::get_instance();
	    //$client = $rcmail->get_http_client();

	    //$client = new \GuzzleHttp\Client(); 	

	   /* $res = $client->request('GET', $bimi_url);
	    if ( $res->getStatusCode() == 200 && $res->hasHeader('Content-Type') && strcasecmp($res->getHeader('Content-Type')[0], self::MIME_TYPE) == 0) {
		$svg = $res->getBody()->getContents();
		$svg = rcmail_attachment_handler::svg_filter($svg);
		return $svg;
	    }*/

	    // Initialize a CURL session.
	    $ch = curl_init();

	    // Return Page contents.
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	   //grab URL and pass it to the variable.
           curl_setopt($ch, CURLOPT_URL, $bimi_url);
  	
	   $result = curl_exec($ch);	

		echo "bimi svg = ".$result ;
		
	   return $result ; 	
	}
	//return null;
	return "";
    }

    /**
     * Given a domain, returns the BIMI URL or null if there no such domain or the domain doesn't have a BIMI record.
     */
    private function get_bimi_url(string $domain): string
    {
        $bimi_record = dns_get_record("default._bimi.".$domain, DNS_TXT);
	if($bimi_record && sizeof($bimi_record) >= 1 && array_key_exists('txt', $bimi_record[0])){
	    $bimi_record_value = $bimi_record[0]['txt'];
	    if(preg_match('@v=BIMI1(?:;|$)@i', $bimi_record_value, $svg) && preg_match('@l=(https://.+?)(?:;|$)@', $bimi_record_value, $matches)){
	        $bimi_url = $matches[1];
		return $bimi_url;
	    }
	}
	//return null;
	return "";
    }

    static private function debug_log($message) {

        if(self::$debug === true) {
            rcmail::console(__CLASS__.': '.$message);
        }
    }
}
