<?php
    
    namespace App\Http\Middleware;
    
    use Closure;
    use Illuminate\Http\Request;
    
    class ApiAuthenicate
    {
        var $user;
        var $password;
        
        /**
         * Handle an incoming request.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \Closure  $next
         * @return mixed
         */
        public function handle(Request $request, Closure $next)
        {
            $this->user = env('AUTH_UN');
            $this->password = env('AUTH_PW');
            
            $expect = base64_encode($this->user.":".$this->password);
            if($request->header('Authorization') !== "Basic ".$expect) {
                return redirect('/');
            }
            return $next($request);
        }
    }