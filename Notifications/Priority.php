<?php
    
    namespace App\Notifications;
    
    use Illuminate\Notifications\Notification;
    use Illuminate\Support\Facades\Log;
    
    class Priority extends Notification
    {
        public $email_token;
        public $email_url;
        
        function __construct()
        {
            $this->email_token = env('SENDGRID_TOKEN');
            $this->email_url = env('SENDGRID_URL');
        }
        
        public function build($message, $details)
        {
            $data = '{ "personalizations": [
                {
                    "to": [
                        {
                            "email": "dist.list@gmail.com"
                        }
                    ],
                    "subject": "Priority 1 error"
                }
            ],
            "from": {
                "email": "notifications@drug.com",
                "name":"notifications@drug.com"
            },
            "content": [
                {
                    "type": "text/plain",
                    "value": "There has been a level 1 error on the Drug Site.  Please check the log .\n' . $message . ' \n' . $details . '"
                }
            ]
        }';
            $this->sendEmail($data);
        }
        
        public function sendEmail($data)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_URL, $this->email_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " . $this->email_token, "Content-Type: application/json"));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $result = curl_exec($ch);
            $r = json_decode($result);
            $err = curl_error($ch);
            
            if ($r || $err) {
                Log::error('Log message', ['error' => 'Alert Failure: did not send' . json_encode($result)]);
            }
            curl_close($ch);
            return $result;
        }
    }