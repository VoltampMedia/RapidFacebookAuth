<?php 
/**
 *  Rapid Facebook Auth methods
 *  This is a small class to work through the Facebook Oauth procedure to get
 *  from the starting link to the final long term access_token for storage
 *  @author      Eric Cope
 *  @copyright   2012 Voltamp Media, Inc.
 *  @license     GPL 2.0
 *  
 */
class RapidFacebookAuth {

    public $app_id;
    public $app_secret;
    public $redirect_uri;
    public $scope;
    public $expected_state;
    public $returned_state;
    public $code;
    public $short_access_token;
    public $long_access_token;


    public function __construct($app_id,$app_secret,$redirect_uri,$scope)
    {
        $this->app_id         = $app_id;
        $this->app_secret     = $app_secret;
        $this->redirect_uri   = $redirect_uri;
        $this->scope          = $scope;
        $this->expected_state = md5($_SERVER['HTTP_HOST']);
        if(array_key_exists('state', $_GET)) {
            $this->returned_state = $_GET['state'];
            if($this->returned_state != $this->expected_state) {
                throw new RapidFacebookAuthException('State Variable returned does not match ours.');
            }
        } else {
            $this->returned_state = '';
        }

        if(array_key_exists('code', $_GET)){ 
            $this->code = $_GET['code'];
        } else {
            $this->code = '';
        }

        /**
         *  init these to zero length strings for strlen comparisons
         */
        $this->short_access_token = '';
        $this->long_access_token  = '';

        return;
    }

    /**
     *  Used to return Facebook link to initiate the process
     */
    public function initiate_link_string()
    {
        $link_string = "https://www.facebook.com/dialog/oauth?" .
                       "client_id=" . $this->app_id . 
                       "&redirect_uri=" . $this->redirect_uri . 
                       "&scope=" . $this->scope . 
                       "&state=" . $this->expected_state;
        return $link_string;
    }   

    /**
     *  this gets the first access_token from the code returned by facebook
     */
    public function get_short_access_token()
    {
        if(strlen($this->code) < 1){
            throw new RapidFacebookAuthException('Facebook Code not stored');
        }
        $curl_url = "https://graph.facebook.com/oauth/access_token?" .
                "client_id=" . $this->app_id . 
                "&redirect_uri=" . $this->redirect_uri . 
                "&client_secret=" . $this->app_secret . 
                "&code=" . $this->code;
        $curl_result = $this->curl($curl_url);
        $this->short_access_token = $curl_result['access_token'];
        return $curl_result;
    }

    /**
     *  this exchanges the short access token for the long access token
     */
    public function get_long_access_token()
    {
        if(strlen($this->short_access_token) == 0) {
            throw new RapidFacebookAuthException('Facebook Short Term Access Token is not stored.');
        }
        $curl_url = "https://graph.facebook.com/oauth/access_token?" .
                "client_id=" . $this->app_id . 
                "&client_secret=" . $this->app_secret . 
                "&grant_type=fb_exchange_token" . 
                "&fb_exchange_token=" . $this->short_access_token;
        $curl_result = $this->curl($curl_url);
        $this->long_access_token = $curl_result['access_token'];
        return $curl_result;
    }
    /**
     *  used to wrap up all CURL calls.
     */
    public function curl($url)
    {
        $headers = array(); 
        $headers[] = "Content-Type: application/x-www-form-urlencoded"; 

        $ch = curl_init(); 
         
        curl_setopt($ch, CURLOPT_URL,$url); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);  
        curl_setopt($ch, CURLOPT_HEADER, false); 

        $answer = curl_exec($ch); 

        // CURL error detection
        if(curl_errno($ch) !== 0) {
            throw new RapidFacebookAuthException('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        // facebook error detection - they return json
        $json = json_decode($answer);
        if(is_object($json)) {
            // if $json->error exists, there was an error, so get it and throw the exception
            if(isset($json->error)){ 
                throw new RapidFacebookAuthException($json->error->type . ": " . $json->error->message, $json->error->code);
            } else {
                return $json;
            }
        }

        // otherwise, its a query string to parse and return
        parse_str($answer,$results);

        return $results; 
    } 
}

class RapidFacebookAuthException extends Exception {

    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        /** 
         *  $previous was added in PHP 5.3, we support previous versions though
         */
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            parent::__construct($message,$code,$previous);
        } else {
            parent::__construct($message,$code);
        }
        return;
    }
}