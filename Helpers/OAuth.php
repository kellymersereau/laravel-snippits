<?php
    
    namespace App;
    
    
    class OAuth
    {
        public $token;
        public $tokenURL;
        public $userApexUrl;
        public $textUsersUrl;
        public $cadenceAPIUrl;
        public $updateRecordUrl;
        public $oneOff;
        
        public function __construct(){
            $this->tokenURL = env('SF_TOKEN_URL');
            $this->userApexUrl = env('SF_USER_APEX_URL');
            $this->textUsersUrl = env('SF_TEXT_USERS_URL');
            $this->cadenceAPIUrl = env('SF_CADENCE_API_URL');
            $this->updateRecordUrl = env('SF_UPDATE_RECORD_URL');
            $this->oneOff = env('SF_ONE_OFF_URL');
            
            $tokenUrl = $this->tokenURL;
            $oauthToken = json_decode($this->rest($tokenUrl, '', 'POST', env('SF_INFO')));
            $this->token = $oauthToken->access_token;
        }
        /**
         * Generic function to make cURL request.
         * @param $url - The URL route to use.
         * @param string $oauthtoken - The oauth token.
         * @param string $type - GET, POST, PUT, DELETE, PATCH. Defaults to GET.
         * @param array $arguments - Endpoint arguments.
         * @param array $headerArray - Header contents array
         * @param array $header - Whether or not to return the headers.
         * @return mixed
         */
        public function rest(
            $url,
            $oauthtoken='',
            $type='GET',
            $arguments=array(),
            $header=false
        ){
            $headerType = array('Authorization: Bearer '.$oauthtoken,'Content-type: application/json');
            
            $type = strtoupper($type);
            
            if ($type == 'GET') {
                $url .= "?" . http_build_query($arguments);
                $headerType = array('Authorization: Bearer '.$oauthtoken);
            }
            
            $curl_request = curl_init();
            
            if ($type == 'POST') {
                if(!$this->isJson($arguments)) {
                    //  echo $arguments.'<br>';
                    $headerType = array('Authorization: Bearer '.$oauthtoken,'Content-type: application/x-www-form-urlencoded');
                }
                
                curl_setopt($curl_request, CURLOPT_POST, 1);
                if($oauthtoken == '') {
                    $headerType = array('Content-type: application/x-www-form-urlencoded');
                }
            }
            elseif ($type == 'PATCH')
            {
                curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "PATCH");
            }
            elseif ($type == "DELETE") {
                curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "DELETE");
            }
            curl_setopt($curl_request, CURLOPT_URL,$url);
            curl_setopt($curl_request, CURLOPT_VERBOSE, 1);
            curl_setopt($curl_request, CURLOPT_HEADER, $header);
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);
            curl_setopt($curl_request, CURLOPT_HTTPHEADER, $headerType);
            
            if (!empty($arguments) && $type !== 'GET')
            {
                curl_setopt($curl_request, CURLOPT_POSTFIELDS, $arguments);
            }
            
            $result = curl_exec($curl_request);
            // print_r($result);
            curl_close($curl_request);
            return $result;
            
        }
        // Helper function to determine if the string is JSON
        private function isJson($string) {
            if(is_object(json_decode($string))){
                return true;
            } else {
                return false;
            }
        }
    }