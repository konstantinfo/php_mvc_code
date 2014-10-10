<?php
/**
 * @abstract This model class is written for User Model for this project
 * @Package Model
 * @category Model
 * @since 1.0.0 20-Oct-12
 * @copyright Copyright & Copy ; 2014
 *
 */
App::uses('AuthComponent', 'Controller/Component');
App::uses('CakeSession', 'Model/Datasource');

class User extends AppModel {
    public $name = 'User';
    
    /**
    * @abstract This function is define to encrypted the user password before save.
    * @access Public
    * @since 1.0.0 1-Feb-2014
    */
    
    public function beforeSave($options = array()) {
        if (isset($this->data[$this->alias]['password'])) {
            $this->data[$this->alias]['password'] = AuthComponent::password($this->data[$this->alias]['password']);
        }
        return true;
    }
    
    /**
    * @abstract This function is define to check the email validation.
    * @access Public
    * @since 1.0.0 1-Feb-2014
    */
    
    public function checkEmail($email_address = null) {

        if (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email_address)) {
            return false;
        }
        else {
            return true;
        }
    }
    
    /**
    * @abstract This function is define to validate the user password.
    * @access Public
    * @since 1.0.0 1-Feb-2014
    */
    
    public function checkPassword($password = null) {        
        
        $password = AuthComponent::password($password);
        $user_id = CakeSession::read('Auth.User.id');
        
        $result = $this->find('count',array('conditions'=>array("User.password" =>$password,"User.id"=>$user_id)));
        if($result == 0) {
            return false;
        }
        else {
            return true;
        }
    }
    
    /**
    * @abstract This function is define to generate random password.
    * @access Public
    * @since 1.0.0 1-Feb-2014
    */
    
    public function generatePassword ($length = 8){        
        
        $password = "";
        $i = 0;
        $possible = "0123456789bcdfghjkmnpqrstvwxyz"; 
        
        while ($i < $length){
            $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
            
            if (!strstr($password, $char)) { 
                $password .= $char;
                $i++;
            }
        }
        
        return $password;
    } 
      
    /**
    * @abstract This function is define to generate bitly_v3_shorten URL.
    * @access Public
    * @since 1.0.0 1-Feb-2014
    */
    
    public function bitly_v3_shorten($longUrl) {
        $x_login = Configure::read('bitly.username');
        $x_apiKey = Configure::read('bitly.api_key');
        $x_bitly_api = 'http://api.bit.ly/v3/';
        $result = array();

        $url = $x_bitly_api . "shorten?login=" . $x_login . "&apiKey=" . $x_apiKey . "&format=json&longUrl=" . urlencode($longUrl);

        if ($x_login != '' && $x_apiKey != '') {
            $url .= "&x_login=" . $x_login . "&x_apiKey=" . $x_apiKey;
        }

        $output_str = "";
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $output_str = curl_exec($ch);
        } 
        catch (Exception $e) {
            echo '<hr/>'.$url.'<hr/>';
        }

        $output = json_decode($output_str);
        if (isset($output->{'data'}->{'hash'})) {
            $result['url'] = $output->{'data'}->{'url'};
            $result['hash'] = $output->{'data'}->{'hash'};
            $result['global_hash'] = $output->{'data'}->{'global_hash'};
            $result['long_url'] = $output->{'data'}->{'long_url'};
            $result['new_hash'] = $output->{'data'}->{'new_hash'};
        }
       
        if (isset($result['url'])) {
            $bitly_url = $result['url'];
        } 
        else {
            $bitly_url = '';
        }
        
        return $bitly_url;
    }

}