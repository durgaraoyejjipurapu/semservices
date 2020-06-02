<?php

	function auth(){
		$response=array("Status"=>"you are not authorized");
		if(isset($_POST)){
             if(isset($_POST['auth_flag']) && $_POST['auth_flag'] =="0"){
             	echo json_encode($response);
             	die();
             	// return;
             }
             if(!isset($_POST['auth_flag'])){
             	echo json_encode($response);
             	die();
             }
         }else{
              if(isset($_GET['auth_flag']) && $_GET['auth_flag'] =="0"){
             	echo  json_encode($response);
             	die(); 
             }

             if(!isset($_GET['auth_flag'])){
             	echo json_encode($response);
             	die();
             }
        }
	}


    function validate_email($email){
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            echo json_encode(array("Status" => "Fail"));
            die();
        }
    }


    function validate_number($str){
        if(!is_numeric($str)){ 
            echo json_encode(array("Status" => "Fail"));
            die();
        }
    }

    function sanit_int($var) {
        return htmlspecialchars(filter_var($var, FILTER_SANITIZE_NUMBER_INT));
    }

    function sanit_email($email) {
        return htmlspecialchars(filter_var($email, FILTER_SANITIZE_EMAIL));
    }

    function sanit_string($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars(filter_var($data, FILTER_SANITIZE_STRING));
        return $data;
    }

    function sanit_sql($conn,$data) {
        
        $data = mysqli_real_escape_string ($conn,$data);
        $data = stripslashes($data);
        return $data;
    }

    function is_base64($str){
        if($str === base64_encode(base64_decode($str))){
            return TRUE;
        }
        return FALSE;
    }

    function valid_buyerid($buyerid) {
        $result = preg_match("#^(BUY){1}#", $buyerid);
        if ($result == 0) {
            return FALSE;
        } else {
            $after_buy_removed = str_replace('BUY', 0, $buyerid);
            if (is_numeric($after_buy_removed)) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }

    function valid_farmerid($farmerid) {
        $result = preg_match("#^(FA){1}#", $farmerid);
        if ($result == 0) {
            return FALSE;
        } else {
            $after_fa_removed = str_replace('FA', 0, $farmerid);
            if (is_numeric($after_fa_removed)) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }

    function check_date_format($date, $format) {
        $d = DateTime::createFromFormat($format, $date);
        if (($d && $d->format($format) === $date) === FALSE) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    function add_api_key($username,$device_id,$receive_time){ 
        $api_key = generate_api_key($username,$device_id);
        $data =array('username'=>$username,'device_id'=>$device_id,'receive_time'=>$receive_time,'api_key'=>$api_key,'api_date'=>date('Y-m-d'));
        $CI = & get_instance(); 
        $CI->db->insert('api_keys', $data);
        return $api_key; 
    }

    function generate_api_key($device_id,$receive_time){
          return sha1($device_id.$receive_time.date(Ymd).uniqid().mt_rand(0000,9999));
    }

    function verify_api_key($api_key,$device_id,$username) {
          $CI = & get_instance(); 
          $where_data = array('username'=>$username,'device_id'=>$device_id,'api_key'=>$api_key,'api_date'=>date('Y-m-d'),'status'=>'active');
          $CI->db->select('IFNULL(count(*),0) records');
          $CI->db->from('api_keys'); 
          $CI->db->where($where_data);
          $rows = $CI->db->get()->row_array();
          // log_message("error",$CI->db->last_query().'----');
          if($rows['records']=="0"){
              // log_message("error","--zero--");
              return FALSE;
          }else{
            // log_message("error","--non zero--");
            return TRUE;
          } 
    }

    function delete_api_keys($username,$device_id){
          $CI = & get_instance();
          $where_data = array('username'=>$username,'device_id'=>$device_id);  
          $CI->db->where($where_data);
          $CI->db->delete('api_keys');
          // log_message("error",$CI->db->last_query());
    }

?>