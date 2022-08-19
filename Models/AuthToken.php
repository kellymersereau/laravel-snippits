<?php
    
    namespace App\Models;
    
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Carbon\Carbon;
    
    class AuthToken extends Model
    {
        use HasFactory;
        
        public function __construct() {
        
        }
        
        /**
         * The attributes that are mass assignable.
         *
         * @var array
         */
        protected $fillable = ['token', 'expires'];
        
        
        /**
         * @return String. This static method can be called to get:set a valid token
         */
        public static function returnToken(){
            
            return AuthToken::checkAuthToken();
        }
        
        /**
         * If the previous token is expired, delete it
         */
        protected static function deleteToken() {
            
            $currentToken = AuthToken::first();
            $currentToken->delete();
        }
        
        /**
         * Create a new token record
         */
        protected static function createToken() {
            $now = Carbon::now();
            $token = AuthToken::getAuthToken();
            $time = $now->addMinutes(18)->toDateTimeString();
            $currentToken = new AuthToken();
            $currentToken->token = $token;
            $currentToken->expires = $time;
            $currentToken->save();
        }
        
        /**
         * @return string This is the request to get a new token
         */
        protected static function getAuthToken() {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => env("SFMC_AUTH_URL"),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(AuthToken::returnCredentials()),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = json_decode(curl_exec($curl));
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if(isset($response)) {
                $response->http_code = $response;
            } else {
                $response = [];
                $response['http_code'] = $http_code;
            }
            
            
            $token = $response ? $response->access_token : "";
            if(!$response || $response) {
                report(curl_error($curl));
            }
            
            curl_close($curl);
            
            return $token;
            
        }
        
        /**
         * @return String Returns a valid token
         */
        protected static function checkAuthToken() {
            $now  = Carbon::now();
            
            if(!isset(AuthToken::first()->token)) {
                AuthToken::createToken();
            }
            $currentToken = AuthToken::first();
            if($now->toDateTimeString() >= AuthToken::first()->expires) {
                AuthToken::deleteToken();
                AuthToken::createToken();
            }
            
            return AuthToken::first()->token;
        }
        
        /**
         * @return Array Returns the correct format for request credentials from the "INTEGRATION"
         */
        protected static function returnCredentials() {
            switch(env('INTEGRATION')) {
                default: // for Salesforce
                    $credentials = ["grant_type"=>"client_credentials", "client_id"=>env("SFMC_CLIENT_ID"), "client_secret"=>env("SFMC_CLIENT_SECRET")];
                
            }
            
            return $credentials;
        }
    }