<?php
    
    namespace App;
    
    use App\Notifications\Priority;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Storage;
    
    class Email
    {
        protected $email_token;
        protected $email_url;
        
        public function __construct()
        {
            $this->email_token 	= env('SENDGRID_TOKEN');
            $this->email_url 	= env('SENDGRID_URL');
        }
        // Used only to send report of cadence messages sent
        public function sendReport($email = "dist.list@gmail.com", $subject = "Report",$message = "Report" ) {
            if(is_array($email)) {
                $email_cont = "{";
                foreach($email as $val) {
                    $email_cont .= '"email":"'.$val.'"},{';
                }
                $email_cont = substr($email_cont,0, -2);
            } else {
                $email_cont = '{"email":"'.$email.'"}';
            }
            $data = '{
			"personalizations": [
				{
					"to":  [
					'.$email_cont.'
				],
					"subject": "'.$subject.'"
				}
			],
			"from": {
				"email": "Reporting@Drug.com"
			},
			"content": [
				{
					"type": "text/plain",
					"value": "'.$message.'"
				}
			]
		}';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_URL,$this->email_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: ".$this->email_token, "Content-Type: application/json"));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);
            
            if($result === FALSE) {
                Log::error('Log message', ['error' => 'Could not send email.'.str_replace(array("\r\n", "\n", "\r"), "", $data) . " - " . str_replace(array("\r\n", "\n", "\r"), "", $result)]);
            }
            curl_close($ch);
            
            return $result;
        }
    }