<?php
    
    namespace App\Models;
    
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use App\Models\AuthToken;
    use PhpParser\Node\Expr\Cast\Object_;
    
    
    class Salesforce extends Model
    {
        use HasFactory;
        
        /**
         * @var String This is the bearer token acquired from the AuthToken model functionality.  This part should be automated and if there's no bearer token, you have big issues
         */
        protected $token;
        /**
         * @var String This ends up being the full uri to access the resource. Use env vars as needed
         */
        protected $endpoint;
        /**
         * @var String The verb for the request
         */
        protected $verb;
        /**
         * @var Array This is the data for whatever request.  It will be json_encode'd so ensure that your structure accounts for that
         */
        protected $data;
        /**
         * @var String This is the rest endpoint url
         */
        protected $RESTEndpoint ;
        /**
         * @var String This is the key for the moderation queue
         */
        protected $ModerationKey ;
        /**
         * @var String This is the key for the processing queue
         */
        protected $ProcessingKey ;
        /**
         * @var Array This to provide context for what is happening if the request fails.  Use something like ["addingContact"=>"cannot add contact via endpoint x,y,z"]
         */
        protected $errorDescriptor;
        
        public function __construct(array $data = [])
        {
            // Set the token up for use
            $this->token = AuthToken::returnToken();
            // Make the base uri available so it's easily changed and extensible
            $this->RESTEndpoint     = env("SFMC_REST_URL");
            $this->ModerationKey    = env("SFMC_MODERATION_KEY");
            $this->ProcessingKey    = env("SFMC_PROCESSING_KEY");
        }
        
        // Create contact for MODERATION QUEUE
        
        /**
         * @param $data Array This is the data to be passed in the request
         * This function creates a contact.
         * @return Object Returns object that will include "http_code", along with other response data, specific to that request
         */
        public function createModerationContact($data){
            $this->endpoint = $this->RESTEndpoint."hub/v1/dataevents/key:".$this->ModerationKey."/rowset";
            $this->verb = "POST";
            $this->data = array($data);
            $response = $this->makeJSONCall();
            return $response;
        }
        
        // Create contact for PROCESSING QUEUE
        
        /**
         * @param $data Array This is the data to be passed in the request
         * This function creates a contact.
         * @return Object Returns object that will include "http_code", along with other response data, specific to that request
         */
        public function createProcessingContact($data){
            $this->endpoint = $this->RESTEndpoint."hub/v1/dataevents/key:".$this->ProcessingKey."/rowset";
            $this->verb = "POST";
            $this->data = array($data);
            $response = $this->makeJSONCall();
            return $response;
        }
        
        public function makeJSONCall() {
            
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $this->verb,
                CURLOPT_POSTFIELDS => json_encode($this->data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$this->token,
                    'Content-Type: application/json'
                ),
            ));
            
            $response = json_decode(curl_exec($curl));
            
            if(!$response) {
                $response = curl_getinfo($curl);
                report(curl_error($curl));
            }
            
            $response['http_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            return $response;
            
        }
        
        
    }