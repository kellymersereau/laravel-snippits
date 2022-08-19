<?php
    
    namespace App\Models;
    
    use Illuminate\Database\Eloquent\Model;
    
    class GroupNumberCookies extends Model
    {
        var $CookieObject;
        
        var $medium;
        var $content;
        var $keyword;
        var $source;
        var $campaign;
        
        var $display            =   ["display"];
        var $p2p                =   ["peer"];
        var $ehr                =   ["ehr"];
        var $salesforceEmail    =   ["slsfrcemail"];
        var $veevaEmail         =   ["veevemail"];
        var $printGroup         =   ["print"];
        var $video              =   ["video"];
        
        var $groupMapping       = [
            "Display"           =>  "5260",
            "P2P"               =>  "5263",
            "EHR"               =>  "5264",
            "Salesforce Email"  =>  "5265",
            "Veeva Email"       =>  "5266",
            "Print"             =>  "5267",
            "Direct"            =>  "5268",
            "Organic"           =>  "5268",
            "Video"             =>  "5269",
            "Referral"          =>  "5270",
            "Other"             =>  "5271"
        ];
        
        function __construct(){
            $this->CookieObject = new \stdClass();
            $this->CookieObject->session_cookie_name = "Test";
            $this->CookieObject->host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
            $this->CookieObject->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
            $this->CookieObject->query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
            $this->visitorSource($this->CookieObject->query_string,$this->CookieObject->referer,$this->CookieObject->host);
            return $this->CookieObject;
        }
        
        /**
         * @var object $organic_sources referers
         */
        protected $organic_sources = array(
            'google'                 => array(''),
            'daum.net/'              => array('q='),
            'eniro.se/'              => array('search_word=', 'hitta:'),
            'naver.com/'             => array('query='),
            'yahoo.com/'             => array('='),
            'bing.com/'              => array('='),
            'msn.com/'               => array('q='),
            'bing.com/'              => array('q='),
            'aol.com/'               => array('query=', 'encquery='),
            'lycos.com/'             => array('query='),
            'ask.com/'               => array('q='),
            'altavista.com/'         => array('q='),
            'search.netscape.com/'   => array('query='),
            'cnn.com/SEARCH/'        => array('query='),
            'about.com/'             => array('terms='),
            'mamma.com/'             => array('query='),
            'alltheweb.com/'         => array('q='),
            'voila.fr/'              => array('rdata='),
            'search.virgilio.it/'    => array('qs='),
            'baidu.com/'             => array('wd='),
            'alice.com/'             => array('qs='),
            'yandex.com/'            => array('text='),
            'najdi.org.mk/'          => array('q='),
            'aol.com/'               => array('q='),
            'mamma.com/'             => array('query='),
            'seznam.cz/'             => array('q='),
            'search.com/'            => array('q='),
            'wp.pl/'                 => array('szukai='),
            'online.onetcenter.org/' => array('qt='),
            'szukacz.pl/'            => array('q='),
            'yam.com/'               => array('k='),
            'pchome.com/'            => array('q='),
            'kvasir.no/'             => array('q='),
            'sesam.no/'              => array('q='),
            'ozu.es/'                => array('q='),
            'terra.com/'             => array('query='),
            'mynet.com/'             => array('q='),
            'ekolay.net/'            => array('q='),
            'dogpile.com/'           => array('='),
            'rambler.ru/'            => array('words=')
        );
        
        public function isTrafficOrganic($referer){
            //Go through the organic sources
            foreach ($this->organic_sources as $searchEngine => $queries) {
                //If referer is part of the search engine
                if (strpos($referer, $searchEngine) !== false) {
                    return true;
                }
            }
            return false;
        }
        
        public function mediumToGroup($medium) {
            $groupName = "Other";
            $m = strtolower($medium);
            // Paid, display, social (for order)
            if(in_array($m, $this->display)) $groupName = "Display";
            if(in_array($m, $this->p2p)) $groupName = "P2P";
            if(in_array($m, $this->ehr)) $groupName = "EHR";
            if(in_array($m, $this->salesforceEmail)) $groupName = "Salesforce Email";
            if(in_array($m, $this->veevaEmail)) $groupName = "Veeva Email";
            if(in_array($m, $this->printGroup)) $groupName = "Print";
            if(in_array($m, $this->video)) $groupName = "Video";
            return $groupName;
        }
        
        public function visitorSource($query_string, $referer, $host)
        {
            // Initial state for $groupName
            $groupName = "Direct";
            
            // parse to individual parameters
            parse_str($query_string, $parsed_query_string);
            
            // Check for referer
            if ($referer) {
                $urlParts = parse_url($referer);
                if(@$urlParts["host"] !== $host){
                    $groupName = 'Referral';
                }
                // Check if Organic search
                if ($this->isTrafficOrganic($referer)) {
                    $groupName = "Organic";
                }
            }
            
            // Check for utm_id - check last because this is the most important
            if(array_key_exists('utm_id', $parsed_query_string)) {
                $this->utm_id = htmlentities($parsed_query_string['utm_id']);
            }
            
            // Check for utm_medium - check next to last because this is the most important
            if(array_key_exists('utm_medium', $parsed_query_string)) {
                $this->medium = htmlentities($parsed_query_string['utm_medium']);
                $groupName = $this->mediumToGroup($this->medium);
            }
            
            // Check for utm_src - so we can create a cookie
            if(array_key_exists('utm_source', $parsed_query_string)) {
                $this->source = htmlentities($parsed_query_string['utm_source']);
            }
            
            // Check for utm_campaign - so we can create a cookie
            if(array_key_exists('utm_campaign', $parsed_query_string)) {
                $this->campaign = htmlentities($parsed_query_string['utm_campaign']);
            }
            
            // Check for utm_term - so we can create a cookie
            if(array_key_exists('utm_term', $parsed_query_string)) {
                $this->keyword = htmlentities($parsed_query_string['utm_term']);
            }
            
            // Check for utm_content - so we can create a cookie
            if(array_key_exists('utm_content', $parsed_query_string)) {
                $this->content = htmlentities($parsed_query_string['utm_content']);
            }
            // If google Add Network it will have a gclid parameter, that means it's Paid Search
            if(array_key_exists('gclid', $parsed_query_string)) {
//            $groupName = "Paid Search";
                $groupName = "Other";
            }
            
            $this->CookieObject->groupName = $groupName;
            $this->CookieObject->groupNumber = $this->groupMapping[$groupName];
            $this->CookieObject->id = $this->utm_id;
            $this->CookieObject->medium = $this->medium;
            $this->CookieObject->source = $this->source;
            $this->CookieObject->campaign = $this->campaign;
            $this->CookieObject->term = $this->term;
            $this->CookieObject->content = $this->content;
            $this->CookieObject->referer = $this->referer;
        }
    }