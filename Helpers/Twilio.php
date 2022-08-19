<?php
    
    namespace App;
    
    use App\Notifications\Priority;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Storage;
    
    class Twilio {
        protected $client;
        protected $sending_num;
        protected $twilio_account_sid;
        protected $twilio_auth_token;
        protected $messages;
        
        public function __construct() {
            $this->sending_num =  env('TWILIO_SENDING_NUM');
            $this->twilio_account_sid = env('TWILIO_ACCOUNT_SID');
            $this->twilio_auth_token = env('TWILIO_AUTH_TOKEN');
        }
        public function send($to, $message) {
            if($this->isBlacklisted($to)) {
                Log::error('Log message', ['error' => 'Trying to send a message to a blacklisted phone number ']);
                $alert = new Priority();
                $alert->build("Trying to send a message to a blacklisted phone number", "To: ".$to);
                return false;
            }
            // Need to format the $to number for Twilio by prepending "+1"
            $to = "+1".$to;
            
            $r = $this->curlSend($to, $message);
            
            if(isset($r->code)) {
                $json = Storage::disk('local')->get('phoneValidatorErrorCodes.json');
                $json = json_decode($json, true);
                $errorMsg = 'no message';
                foreach($json as $item){
                    if($item['code'] === $r->code){
                        $errorMsg = $item['message'];
                    }
                }
                Log::error('Log message', ['error' => 'Twilio error code detected: '.$r->code." Status code: ".$r->status." Message: ".$errorMsg]);
                $alert = new Priority();
                $alert->build("Twilio error code detected", "Error code: ".$r->code." Status code: ".$r->status." Message: ".$errorMsg);
            }
        }
        function curlSend($to, $message) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_URL, "https://api.twilio.com/2010-04-01/Accounts/".$this->twilio_account_sid."/Messages.json");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            $data = "&From=".$this->sending_num."&To=".$to."&Body=".urlencode($message);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_USERPWD, $this->twilio_account_sid.":".$this->twilio_auth_token );
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);
            $r = json_decode($result);
            
            curl_close($ch);
            return $r;
        }
        protected function isBlacklisted($phone) {
            try {
                $query = DB::table('blacklist')->where('phone','=', $phone);
            } catch(\Exception $e) {
                Log::error('Log message', ['error' => 'Transactional database failure for text blacklist']);
                $alert = new Priority();
                $alert->build("Transactional database failure for text blacklist", json_encode($e));
            }
            
            if($query->count() > 0) {
                return true;
            }
            return false;
        }
    }