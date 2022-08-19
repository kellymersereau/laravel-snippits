<?php
    
    namespace App;
    
    use App\Models\AuthToken;
    
    class SFMC {
        /**
         * @var String This is the bearer token acquired from the AuthToken model functionality.  This part should be automated and if there's no bearer token, you have big issues
         */
        protected $token;
        /**
         * @var String The verb for the request
         */
        protected $verb;
        protected $data;
        /**
         * @var String This is the endpoint url
         */
        protected $endpoint;
        /**
         * @var String This is the rest endpoint url
         */
        protected $RESTEndpoint ;
        /**
         * @var String This is the rest endpoint url for the Goal Getter form
         */
        protected $GoalGetterRESTEndpoint ;
        /**
         * @var String This is the SOAP endpoint url
         */
        protected $SOAPEndpoint ;
        /**
         * @var String This is the Triggered Send REST endpoint url
         */
        protected $TriggeredSendEndpoint ;
        /**
         * @var String This is the Triggered Send LOOKUP REST endpoint url
         */
        protected $TriggeredSendLookupEndpoint ;
        /**
         * @var Array This to provide context for what is happening if the request fails.  Use something like ["addingContact"=>"cannot add contact via endpoint x,y,z"]
         */
        protected $errorDescriptor;
        protected $RegistrationKey;
        protected $SendDefinitionKey;
        
        public function __construct($goalGetter)
        {
            // Set the token up for use
            $this->token = AuthToken::returnToken($goalGetter);
            // Make the base uri available so it's easily changed and extensible
            $this->RESTEndpoint                   = env("SFMC_REST_URL");
            $this->SOAPEndpoint                   = env("SFMC_SOAP_URL");
            $this->GoalGetterRESTEndpoint         = env("SFMC_GOAL_GETTER_REST_URL");
            $this->TriggeredSendLookupEndpoint    = env("SFMC_TRIGGERED_SEND_LOOKUP_URL");
            $this->TriggeredSendEndpoint          = env("SFMC_TRIGGERED_SEND_URL");
            $this->RegistrationKey = env('SFMC_MASTER_REGISTRATION_KEY');
            $this->SendDefinitionKey = env('SFMC_TRIGGERED_SEND_DEFINITION_KEY');
        }
        
        public function lookupRecordViaEmail($email){
            // This uses SOAP
            $this->verb = 'POST';
            return $this->makeSOAPCall('EmailAddress', $email);
        }
        public function lookupRecordViaSCID($scid){
            // This uses SOAP
            $this->verb = 'POST';
            return $this->makeSOAPCall('SavingsCardNumber', $scid);
        }
        public function createUpdateRecord($email, $scid, $groupnum, $scImgURL, $scURL, $utmSource = null, $utmMedium = null, $utmCampaign = null, $utmContent = null, $utmTerm = null){
            // This uses REST
            $data = (object) [
                "keys" => [
                    "SubscriberKey"=>$email
                ],
                "values" => [
                    "EmailAddress"=>$email,
                    "SavingsCardNumber"=>$scid,
                    "GroupNumber"=>$groupnum,
                    "SavingsCardImageURL"=>$scImgURL,
                    "SavingsCardLinkURL"=>$scURL,
                    "utm_source"=>$utmSource,
                    "utm_medium"=>$utmMedium,
                    "utm_campaign"=>$utmCampaign,
                    "utm_content"=>$utmContent,
                    "utm_term"=>$utmTerm
                ]
            ];
            $this->data = array($data);
            $this->verb = "POST";
            return $this->makeRESTCall($this->RESTEndpoint);
        }
        public function createGoalGetterUpdateRecord($email, $firstName, $onDrug, $utmSource = "No source present", $utmMedium = "No medium present", $utmCampaign = "No campaign present", $utmContent = "No content present", $utmTerm = "No term present"){
            if($onDrug === 'yes'){
                $updateOnDrug = true;
            } else {
                $updateOnDrug = false;
            }
            // This uses REST
            $data = (object) [
                "keys" => [
                    "SubscriberKey"=>$email
                ],
                "values" => [
                    "EmailAddress"=>$email,
                    "FirstName"=>$firstName,
                    "onDrug"=>$updateOnDrug,
                    "utm_source"=>$utmSource,
                    "utm_medium"=>$utmMedium,
                    "utm_campaign"=>$utmCampaign,
                    "utm_content"=>$utmContent,
                    "utm_term"=>$utmTerm
                ]
            ];
            $this->data = array($data);
            $this->verb = "POST";
            return $this->makeRESTCall($this->GoalGetterRESTEndpoint);
        }
        public function sendTriggerEmail($email, $scid, $groupnum, $scImgURL, $scURL, $utmSource = null, $utmMedium = null, $utmCampaign = null, $utmContent = null, $utmTerm = null){
            // This uses REST
            $data = (object) [
                "To" => [
                    "Address"=>$email,
                    "SubscriberKey"=>$email,
                    "ContactAttributes"=>[
                        "SubscriberAttributes"=>[
                            "EmailAddress"=>$email,
                            "SavingsCardNumber"=>$scid,
                            "GroupNumber"=>$groupnum,
                            "SavingsCardImageURL"=>$scImgURL,
                            "SavingsCardLinkURL"=>$scURL,
                            "utm_source"=>$utmSource,
                            "utm_medium"=>$utmMedium,
                            "utm_campaign"=>$utmCampaign,
                            "utm_content"=>$utmContent,
                            "utm_term"=>$utmTerm
                        ]
                    ]
                ]
            ];
            $this->data = $data;
            $this->verb = "POST";
            return $this->makeRESTCall($this->TriggeredSendEndpoint);
        }
        public function makeRESTCall($endpoint){
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $this->verb,
                CURLOPT_POSTFIELDS =>json_encode($this->data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$this->token
                ),
            ));
            $response = json_decode(curl_exec($curl));
            if(!$response) {
                $response = curl_getinfo($curl);
                report(curl_error($curl));
            }
            curl_close($curl);
            
            return $response;
        }
        public function makeSOAPCall($dataToLookup, $dataValue){
            $curl = curl_init();
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->SOAPEndpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $this->verb,
                CURLOPT_POSTFIELDS => '<?xml version="1.0" encoding="UTF-8"?>
                <s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:a="http://schemas.xmlsoap.org/ws/2004/08/addressing" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                    <s:Header>
                        <a:Action s:mustUnderstand="1">Retrieve</a:Action>
                        <a:To s:mustUnderstand="1">'.$this->SOAPEndpoint.'</a:To>
                        <fueloauth xmlns="http://exacttarget.com">'.$this->token.'</fueloauth>
                    </s:Header>
                    <s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                        <RetrieveRequestMsg xmlns="http://exacttarget.com/wsdl/partnerAPI">
                         <RetrieveRequest>
                                <ObjectType>DataExtensionObject['.$this->RegistrationKey.']</ObjectType>
                                <Properties>EmailAddress</Properties>
                                <Properties>SavingsCardNumber</Properties>
                                <Properties>GroupNumber</Properties>
                                <Filter xsi:type="SimpleFilterPart">
                                    <Property>'.$dataToLookup.'</Property>
                                    <SimpleOperator>equals</SimpleOperator>
                                    <Value>'.$dataValue.'</Value>
                                </Filter>
                            </RetrieveRequest>
                      </RetrieveRequestMsg>
                    </s:Body>
                </s:Envelope>',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: text/xml'
                ),
            ));
            
            $response = curl_exec($curl);
            
            if(!$response) {
                $response = curl_getinfo($curl);
                report(curl_error($curl));
            }
            
            curl_close($curl);
            
            return $response;
        }
    }