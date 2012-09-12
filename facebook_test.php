<?php 
include('rapidFacebookAuth.php');


$app_id       = "your_app_id";
$app_secret   = "your app secret";
$redirect_uri = 'redirect uri';
$scope        = 'manage_pages';
$fb_id        = 'facebook_id name or number';
$rapidfb      = new rapidFacebookAuth($app_id,$app_secret,$redirect_uri,$scope);


if(array_key_exists('code', $_GET)){ 
    $results = $rapidfb->get_short_access_token();
    var_dump('1',$results);

    $results = $rapidfb->get_long_access_token();
    var_dump('2',$results);

    // ready to go, extend this class and have at it with $this->long_access_token    
}

?>

<a href="<?php echo $rapidfb->initiate_link_string(); ?>">click here to start</a>
