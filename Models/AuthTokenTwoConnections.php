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
        public static function returnToken($goalGetter){
            
            return AuthToken::checkAuthToken($goalGetter);
        }
        
        /**
         * If the previous token is expired, delete it
         */
        protected static function deleteToken($goalGetter) {
            
            if($goalGetter){
                $currentToken = AuthToken::firstWhere('goalGetter', true);
            } else {
                $currentToken = AuthToken::firstWhere('goalGetter', false);
            }
            $currentToken->delete();
            
        }
        
        /**
         * Create a new token record
         */
        protected static function createToken($goalGetter) {
            $now = Carbon::now();
            $token = AuthToken::getAuthToken($goalGetter);
            $time = $now->addMinutes(18)->toDateTimeString();
            $currentToken = new AuthToken();
            $currentToken->token = $token;
            if($goalGetter){
                $currentToken->goalGetter = true;
            } else {
                $currentToken->goalGetter = false;
            }
            $currentToken->expires = $time;
            $currentToken->save();
        }
        
        /**
         * @return string This is the request to get a new token
         */
        protected static function getAuthToken($goalGetter) {
            if($goalGetter){
                $clientId = env('SFMC_GG_CLIENT_ID');
                $clientSecret = env('SFMC_GG_CLIENT_SECRET');
            } else {
                $clientId = env('SFMC_CLIENT_ID');
                $clientSecret = env('SFMC_CLIENT_SECRET');
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => env('SFMC_AUTH_URL'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret
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
        protected static function checkAuthToken($goalGetter) {
            $now  = Carbon::now();
            
            if($goalGetter){
                if(!isset(AuthToken::firstWhere('goalGetter', true)->token)) {
                    AuthToken::createToken($goalGetter);
                }
                if($now->toDateTimeString() >= AuthToken::firstWhere('goalGetter', true)->expires) {
                    AuthToken::deleteToken($goalGetter);
                    AuthToken::createToken($goalGetter);
                }
                
                return AuthToken::firstWhere('goalGetter', true)->token;
            } else {
                if(!isset(AuthToken::firstWhere('goalGetter', false)->token)) {
                    AuthToken::createToken($goalGetter);
                }
                if($now->toDateTimeString() >= AuthToken::firstWhere('goalGetter', false)->expires) {
                    AuthToken::deleteToken($goalGetter);
                    AuthToken::createToken($goalGetter);
                }
                
                return AuthToken::firstWhere('goalGetter', false)->token;
            }
            
        }
    }