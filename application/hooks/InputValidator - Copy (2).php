
<?php
class InputValidator{
	function filter_post_param(){
		if(isset($_POST)){  
			$db =& get_instance()->db->conn_id; 
			foreach ($_POST as $key => $value) {
            	$sanit_val=htmlspecialchars(strip_tags($_POST[$key]));
    			$val = mysqli_real_escape_string($db,$sanit_val);
            	$_POST[$key]=$val;
        	} 
		} 

		// log_message("error","Post Data" .json_encode($_POST));
		$response_api=$this->verify_auth();
		// log_message("error","verify auth response--s".$response_api);
		if(isset($_POST)){
          	$_POST['auth_flag']=$response_api;
		}else{
			$_GET['auth_flag']=$response_api;
		}

	}

	function verify_auth(){
		// log_message("error","calling Verify auth");
		$this->CI=& get_instance();
		$this->CI->load->library("MCrypt",'','mcrypt');
		$request_headers=$this->CI->input->request_headers(); 
// 
        // log_message("error",json_encode($_POST).'----------------');
        $url_array=explode('/', $_SERVER['REQUEST_URI']);
        $request_uri=end($url_array); 
		// log_message("error",'request headers'.json_encode($request_headers));
        // log_message("error",'Post data'.json_encode($_POST));

		if(!(array_key_exists('header_one',$request_headers) && array_key_exists('header_two',$request_headers) && array_key_exists('Authorization',$request_headers) && array_key_exists('time',$request_headers))){
			return "0";
		}

        /*
            if Reelere
            header_one = aadharno
            header_two = mobile no

            if staff
            header_one = staff username
            header_two = "staff" constant
         */

		$Aadhar_No = $this->CI->mcrypt->decrypt($request_headers['header_one']); 
        $Mobile_No = $this->CI->mcrypt->decrypt($request_headers['header_two']);

        $db =& get_instance()->db->conn_id; 
        $Aadhar_No = mysqli_real_escape_string($db,htmlspecialchars(strip_tags($Aadhar_No))); 
        $Mobile_No = mysqli_real_escape_string($db,htmlspecialchars(strip_tags($Mobile_No)));  


        // log_message("error","--aadhar no ".$Aadhar_No .'--Mobile No--'.$Mobile_No);
        $Authorization = $request_headers['Authorization'];
        $Time = $request_headers['time'];

        /*
         * Services that call before login.   
         */
        $pre_login_services = array('ReleerVersion','BuyerDetails','server_current_time','market_app_version','staff_login');
        if(in_array($request_uri, $pre_login_services)){ 
              if($Aadhar_No=="-" && $Mobile_No=="-"){
                    // To get app version of market staff before login  Aadhar No and Mobile No is must be  '-'
                    // market staff app version
                    // token+date 
                    $key=md5('token'.date('mdY'));
                    // log_message("error",'innser Key'.$key);
                    if($key == $Authorization){
                        return "1";
                    }else{
                        return "0";
                    }
             }
        } 
        
        if(strtolower($Mobile_No)=="staff"){    
               $this->CI->load->helper('email');
               /*
                * Ror staff Aadhar No is nothing but email 
                * For Moble no holds value called  "staff"
                */
               $aadhar=substr($Aadhar_No, -6);
              // log_message("error","For Staff....--".$aadhar);
               // Market Staff  
               $key=md5($aadhar.'token'.date('mdY'));
   
                // key checking..
                if($key == $Authorization){
                    // check login details for market officers  
                    $query="select User_Id from `login_master` where User_Id='$Aadhar_No' and User_Desig='Officer' limit 1";
                    // log_message("error","--staff query--".$query);

                    $row=$this->CI->db->query($query)->row_array();
                    // log_message("error",'-- print data --'.json_encode($row));
                    if(isset($row['User_Id']) && $row['User_Id']!=''){
                        // log_message("error","--- True Block---");
                        return "1";
                    }else{
                        login_attempt($user_id);
                        // log_message("error","--- False Block---");
                        return "0";
                    }
                }else{
                    // else say no
                    return "0";
                }  


        }else{

                $aadhar=substr($Aadhar_No, 2, strlen($Aadhar_No));
                $key=md5($aadhar.'token'.date('mdY'));
                if($key == $Authorization){
                    // check login details.
                    if(is_numeric($aadhar)){
                            $query="SELECT User_Id,Mobile_No,Aadhar_No FROM Login_Master l 
                                    JOIN Buyer_Details b ON User_Id=b.Email_Id
                                    WHERE  RIGHT(b.Aadhar_No,6)='$aadhar'";    
                            // $query="SELECT User_Id,Mobile_No,Aadhar_No FROM Login_Master l
                            //     JOIN Buyer_Details b ON User_Id=b.Email_Id
                            //     WHERE  RIGHT(b.Aadhar_No,6)='$aadhar' AND (Mobile_No='$Mobile_No' OR  User_Pwd='$Mobile_No')";               
                            $row=$this->CI->db->query($query)->row_array();
                            if(isset($row['User_Id']) && $row['User_Id']!=''){
                                // log_message("error","Reeler true block".$query);    
                                return "1";
                            }else{ 
                                login_attempt($user_id);
                                return "0";
                            }
   
                    }else{
                        login_attempt($user_id);
                        return "0";
                    }
                }else{
                    return "0";
                }   
        }
	}
}
	
?>