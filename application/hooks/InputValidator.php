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

        log_message("error",'request headers'.json_encode($request_headers));
        log_message("error",'Post data'.json_encode($_POST)); 
        log_message("error",'Post data'.$request_uri);        

        if(!(array_key_exists('header_one',$request_headers) && array_key_exists('header_two',$request_headers) && array_key_exists('Authorization',$request_headers) && array_key_exists('time',$request_headers))){  
            return "0";
        }

        /*
            if Reelere
            header_one = aadharno  || '-'
            header_two = device id

            if staff
            header_one = staff username
            header_two = device id constant
         */

        $Aadhar_No = $this->CI->mcrypt->decrypt($request_headers['header_one']);  // username
        $Device_id = $request_headers['header_two'];  // device id 
        $Authorization = $request_headers['Authorization'];
        $Time = $request_headers['time'];
        

        $db =& get_instance()->db->conn_id; 
        $Aadhar_No = mysqli_real_escape_string($db,htmlspecialchars(strip_tags($Aadhar_No))); 
        $Device_id = mysqli_real_escape_string($db,htmlspecialchars(strip_tags($Device_id)));  

        $Authorization = mysqli_real_escape_string($db,htmlspecialchars(strip_tags($request_headers['Authorization'])));
        $Time = mysqli_real_escape_string($db,htmlspecialchars(strip_tags($request_headers['time'])));   
        
        log_message("error","after ency".$Aadhar_No.'***'.$Device_id.'***'.$Authorization.'***'.$Time);
          
        /*
         * Services that call before login.   
         */
        $pre_login_services = array('ReleerVersion','server_current_time','market_app_version');
        if((in_array($request_uri, $pre_login_services)) && $Aadhar_No=="-"){
              if($Aadhar_No=="-"){
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
        }else{
               
             // Verify valid User Or not 
               $login_services = array('BuyerDetails','staff_login');
               if(!in_array($request_uri, $login_services)){ 
                      /*
                      *  calling post login services so verify auth  
                      */
                     // verify_api_key($api_key,$device_id,$username);
                     if(!verify_api_key($Authorization,$Device_id,$Aadhar_No)){ 
                            return "0";
                     }else{
                        return "1";
                     }
               }else{
                    $aadhar=substr($Aadhar_No, 2, strlen($Aadhar_No));       
                    if(is_numeric($aadhar)){ 
                            $query="SELECT User_Id,Mobile_No,Aadhar_No FROM Login_Master l 
                                    JOIN Buyer_Details b ON User_Id=b.Email_Id
                                    WHERE  RIGHT(b.Aadhar_No,6)='$aadhar'";        
                            $row=$this->CI->db->query($query)->row_array();
                            if(isset($row['User_Id']) && $row['User_Id']!=''){ 
                                delete_api_keys($Aadhar_No,$Device_id);
                                $key=add_api_key($Aadhar_No ,$Device_id,$Time); 
                                $_POST['Authorization'] = $key; 
                                return "1";
                            }else{  
                                login_attempt($Aadhar_No);
                                return "0";
                            }
   
                    }else{
                         // check login details for market officers  
                        $query="select User_Id from `login_master` where User_Id='$Aadhar_No' and User_Desig='Officer' limit 1";
                        $row=$this->CI->db->query($query)->row_array();
                        if(isset($row['User_Id']) && $row['User_Id']!=''){
                              // delete api keys
                              delete_api_keys($Aadhar_No,$Device_id);

                              $key=add_api_key($Aadhar_No ,$Device_id,$Time);
                              $_POST['Authorization'] = $key;   
                             return "1";
                        }else{
                            login_attempt($Aadhar_No);
                            return "0";
                        }    
                    }


               }

        }


        
    }
}
    
?>