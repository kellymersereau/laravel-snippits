<?php
    
    namespace App;
    
    // Need to ensure that all servers use the same timezone format
    use App\Notifications\Priority;
    use App\OAuth;
    use Illuminate\Support\Facades\Log;
    use DateTime;
    use App\SavingsCard;
    
    date_default_timezone_set("UTC");
    
    class FormData {
        public $data = array(
            "Query_String__c" 		=> null, 	// TEXT [LONG] - will update with the latest NOT keep every one (string can be very long)
            "Initial_Group_Number__c"	=> null, // if a text message was initiated by clicking link on website for pre-populated text message, this was how they came in
            "Group_Number__c"		=> null, 	// this is the group number determined by cookie.php
            "Card_Type__c" 			=> null, 	// Medium
            "email" 				=> null, // TEXT
            "Card_Number__c"		=> null, // TEXT
            "Optin_Text__c"			=> null, // BOOLEAN
            "Assessment__c" 		=> null, // TEXT
            "LastName" 				=> null, // TEXT
            "MobilePhone" 			=> null, // NATIVE
            "Unsubscribed__c" 		=> null, // BOOLEAN
            "Unsubscribed_Text__c" 	=> null, // BOOLEAN
            "Text_Medication_Reminder__c" => null, // DATETIME
            "Text_Refill_Reminder__c" 	=> null, // DATETIME
            "Prescription_Quantity__c" => null, // NUMBER(3,0)
            "Text_Health_Optin__c" => null, // DATETIME
            "Cadence_Type__c" => null, // TEXT
        );
        private $bool_values = array("Optin_Email__c", "Optin_Text__c", "Unsubscribed_Text__c"); // boolean values for mapping
        
        private $unsubscribe;
        
        private $now; // This is a datetime variable
        
        private $userId;
        
        private $twilioRecord = "check=false&twilio="; // parameters to APEX REST service to retrieve text-based record or create record
        
        private $twilioRecordCheck = "check=true&twilio="; // parameters to APEX REST service to test if record exists and NOT create if it doesn't
        
        public $sfid;
        
        function __construct($request) {
            
            $this->unsubscribe = strpos($_SERVER['REQUEST_URI'],"optout.php") > -1 ? TRUE : FALSE; // Set this to true in case of unsubscribe so we can bypass security
            
            if($this->security_check($request)) {
                // Map $request keys/values to $this->data keys/values
                foreach($request->input() as $key=>$val) {
                    if(array_key_exists($key, $this->data)) {
                        $val = $this->sanitizeString($val);
                        // Set booleans to be correctly interpreted
                        in_array($key, $this->bool_values) && $val !== '' ? $val = TRUE : $val = $val;
                        // If the value is MobilePhone we need to make sure it's clean
                        if($key === "MobilePhone") {
                            $val = preg_replace('/[^0-9\.]/', '', $val);
                        }
                        $this->data[$key] = $val;
                    }
                }
            } else {
                Log::error('Log message', ['error' => 'Not passing security_check.'.json_encode($request).', REFERER: '.@$_SERVER["HTTP_REFERER"]]);
                header("HTTP/1.0 404 Not Found");
                die();
            }
            
            // Create DateTime variable "$now"
            $now = new DateTime(date("c"));
            $this->now = $now->format(DATE_ATOM);
            
            // Do logic in getUserInfo method
            $userInfo = $this->getUserInfo($request);
            
            // variables for data
            $this->userId 	= 	@$userInfo->Id;
            $this->data['Text_Health_Optin__c'] ? $this->data['Text_Health_Optin__c'] = $this->now : null;
            
            // Use retrieved data to populate sfid.
            $request->input('sfid') ? $this->sfid = $request->input('sfid') : $this->sfid = $this->userId;
            
            // Update cookie values
            $this->data['Query_String__c'] = @$_COOKIE['qs'];
            $this->data['Card_Type__c'] = @$_COOKIE['medium'];
            
        }
        private function security_check($request) {
            if($this->unsubscribe) {
                return true;
            }
            
            $isGoodHost = false;
            
            if(strpos(@$_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > -1) $isGoodHost = true;
            
            // Only invalidate hidden captcha if it exists AND is incorrect - prevent issues with email returns that do not contain this param
            $hiddenCaptcha = $request->input('honeypot') && $request->input('honeypot') !== '' ? FALSE: TRUE;
            
            // This is to prevent bots     // This prevents unknown referrers
            if(!$hiddenCaptcha || !$isGoodHost) { // count($this->is_good_host) < 1) {
                // If they are unsubscribing or coming from an email link we can skip this
                
                if($this->unsubscribe) {
                    return true;
                }
                return false;
            }
            return true;
        }
        // Method to determine user record status
        private function getUserInfo($request){
            // If we only received a phone number it's either the NEW Text_Health_Optin__c attempt or reminders
            if($this->data['MobilePhone']) {
                $tRec = $this->sfConnect('POST', $this->twilioRecordCheck.$this->data['MobilePhone']);
                if(@$tRec->Id) { // If a phone number based record exists, return that
                    return $tRec;
                } else {
                    return $this->sfConnect('POST', $this->twilioRecord.$this->data['MobilePhone']);
                }
            }
        }
        public function sfConnect($type, $args ) {
            $oauth = new OAuth();
            $sfResults = json_decode($oauth->rest($oauth->userApexUrl, $oauth->token, $type, $args));
            
            if(@$sfResults->errorCode){
                Log::error('Log message', ['error' => 'Salesforce transmit failure FIRST CONNECTION'.json_encode($sfResults)]);
                $alert = new Priority();
                $alert->build("Salesforce transmit failure FIRST CONNECTION", json_encode($sfResults));
            }
            
            return $sfResults->con;
        }
        public function sanitizeString($var){
            $var = stripslashes($var);
            $var = htmlentities($var);
            $var =  strip_tags($var);
            return $var;
        }
        public function resubscribe($text = false)
        {
            // Set unsubscribe to false
            $this->data['Unsubscribed__c'] = 0;
            
            $dataToUpdate = $text ? array("Unsubscribed_Text__c"=> false) : array("Unsubscribed__c"=> false);
            
            $oauth = new OAuth();
            $token = $oauth->token;
            
            //  Send update to Salesforce
            $send_sf = json_decode($oauth->rest(env('SF_BASE_URL').'services/data/v37.0/sobjects/Contact/'.$this->sfid, $token, 'PATCH', json_encode($dataToUpdate)));
            
            if(@$send_sf[0]->errorCode) {
                Log::error('Log message', ['error' => 'Could not resubscribe: Salesforce error: '.json_encode($send_sf[0])]);
            }
        }
    }