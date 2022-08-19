<?php
    
    namespace App\Http\Middleware;
    
    use Closure;
    use Illuminate\Http\Request;
    use App\Models\GroupNumberCookies;
    
    class GroupNumberCookie
    {
        /**
         * Handle an incoming request.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
         * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
         */
        public function handle(Request $request, Closure $next)
        {
            $GNCookie = new GroupNumberCookies();
            $GNCookie->CookieObject->SourceURL = $request->url().$request->getRequestUri();
            $GNCookie->GroupName = $GNCookie->CookieObject->groupName;
            $GNCookie->GroupNumber = $GNCookie->CookieObject->groupNumber;
            $GNCookie->referer = $GNCookie->CookieObject->referer;
            
            $med_cookie_name = $GNCookie->CookieObject->session_cookie_name."-medium"; // was "-med"
            $qs_cookie_name = $GNCookie->CookieObject->session_cookie_name."-qs";
            $src_cookie_name = $GNCookie->CookieObject->session_cookie_name."-source";
            $camp_cookie_name = $GNCookie->CookieObject->session_cookie_name."-campaign";
            $term_cookie_name = $GNCookie->CookieObject->session_cookie_name."-term";
            $content_cookie_name = $GNCookie->CookieObject->session_cookie_name."-content";
            
            $qs = $GNCookie->CookieObject->query_string != "" ? $GNCookie->CookieObject->query_string : "No qs present";
            $medV = $GNCookie->CookieObject->medium != "" ? $GNCookie->CookieObject->medium : "No medium present";
            $srcV = $GNCookie->CookieObject->source != "" ? $GNCookie->CookieObject->source : "No source present";
            $campV = $GNCookie->CookieObject->campaign != "" ? $GNCookie->CookieObject->campaign : "No campaign present";
            $termV = $GNCookie->CookieObject->term != "" ? $GNCookie->CookieObject->term : "No term present";
            $contentV = $GNCookie->CookieObject->content != "" ? $GNCookie->CookieObject->content : "No content present";
            if(!$request->cookie("hcpgn")) {
                @setcookie($med_cookie_name, $medV, 0, '/');
                @setcookie($qs_cookie_name, $qs, 0, '/');
                @setcookie($src_cookie_name, $srcV, 0, '/');
                @setcookie($camp_cookie_name, $campV, 0, '/');
                @setcookie($term_cookie_name, $termV, 0, '/');
                @setcookie($content_cookie_name, $contentV, 0, '/');
                @setcookie("hcpgn", $GNCookie->GroupNumber, 0, '/');
            }
            
            return $next($request);
        }
    }