<?php
    
    namespace App\Models;
    
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    
    class NPIValidation extends Model
    {
        use HasFactory;
        /**
         * @var String This is the endpoint url
         */
        protected $NPIEndpoint ;
        /**
         * @var String This ends up being the full uri to access the resource. Use env vars as needed
         */
        protected $endpoint;
        
        // Create contact for MODERATION QUEUE
        
        /**
         * @param $data int This is the data to be passed in the request
         * This function creates a contact.
         * @return Object Returns object that will include "http_code", along with other response data, specific to that request
         */
        public function validateNPI($number){
            $this->endpoint = env("NPI_BASE_URL")."&number=".$number."&pretty=true";
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
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                
                ),
            ));
            
            $response = json_decode(curl_exec($curl));
            
            if(!$response) {
                $response = false;
                report(curl_error($curl));
            }
            
            curl_close($curl);
            
            return $response;
            
        }
    }