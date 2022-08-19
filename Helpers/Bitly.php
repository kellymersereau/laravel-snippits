<?php
    
    namespace App;
    
    use Illuminate\Support\Facades\Log;
    
    class Bitly {
        public $accessToken;
        public $apiEndpoint;
        
        public function __construct()
        {
            $this->accessToken   = env('BITLY_ACCESS_TOKEN');
            $this->apiEndpoint   = env('BITLY_API_ENDPOINT');
        }
        
        public function shorten_url($url)
        {
            $shortenEndpoint = $this->apiEndpoint.'v3/shorten?access_token='.$this->accessToken.'&longurl='.$url;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_URL,$shortenEndpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);
            $r = curl_getinfo($ch);
            curl_close($ch);
            
            
            if(json_decode($result)->status_code == 500){
                Log::error('Log message', ['error' => 'Bitly shorten_url curl call failed! - Error code:'.json_decode($result)->status_code.' -  Error Message: '.json_decode($result)->status_txt.' - URL sent to Bitly that caused failure: '.$url]);
            }
            
            return $result;
        }
    }