<?php
    
    namespace App;
    
    use App\Notifications\Priority;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Storage;
    
    class PhoneValidator {
        private $context;
        public $results;
        
        public function __construct(){
            $auth = base64_encode(env("PHONE_VALIDATOR_AUTH"));
            $this->context = stream_context_create([
                "http" => [
                    "header" => "Authorization: Basic $auth"
                ]]);
            $this->results = $this->buildModel();
        }
        
        /*
         * Build the model that will be returned. By default return error, name [for callerName] and type [for carrier]
         */
        private function buildModel(){
            $o  = [];
            $o['error'] = null;
            $o['name'] = null;
            $o['type'] = null;
            $this->results = $o;
            return $o;
        }
        
        public function carrier($phone){
            if($j = @file_get_contents("https://lookups.twilio.com/v1/PhoneNumbers/+1".$phone."?Type=carrier", false, $this->context)) {
                $d = json_decode($j);
                $type = $d->carrier->type;
                $err = $d->carrier->error_code ? : "no errors";
                $this->results['type'] = $type;
                if($err !== "no errors") {
                    $this->results['error'] = $err;
                    $json = Storage::disk('local')->get('phoneValidatorErrorCodes.json');
                    $json = json_decode($json, true);
                    $errorMsg = 'no message';
                    foreach($json as $item){
                        if($item['code'] === $err){
                            $errorMsg = $item['message'];
                        }
                    }
                    Log::error('Log message', ['error' => 'PhoneValidator Failure. Code: '.$err.' Message: '.$errorMsg]);
                    $alert = new Priority();
                    $alert->build("Error in carrier for phoneValidator", "Error code: ".$err." Error message: ".$errorMsg);
                }
            }
            return json_encode($this->results);
        }
    }