<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Market_Staff_Model extends CI_Model {

    function __construct() {
        parent::__construct();
         $this->load->model('Market_Staff_Model', 'msm');
        $this->load->helper('auth');
    }

    public function get_staff_apk_version() {
        $row = $this->db->select(array('App_Vesion'))
                        ->from('appversion_master')
                        ->where(array('Row_Id' => "1"))
                        ->limit(1)
                        ->get()->result_array();
        // log_message("error",json_encode($row));                
        return array("Version" => $row[0]['App_Vesion']);
    }

    public function login_check() {
//        {"Username":"testadmin","password":"password@123"}
        // log_message("error",'at controller Post data'.json_encode($_POST));

        $Username = $this->mcrypt->decrypt($this->input->post("Username"));
        $password = $this->mcrypt->decrypt($this->input->post("password"));

        // if(!valid_email($Username)){
        //     return array("Status" => "Login Failed", "Details" => null);
        // }

       if (!(count_login_attempt($Username) < 5)) {
             return array("Status" => "You’ve reached the maximum login attempts for today. So please try tomorrow", "Details" =>array());
        }  

        $query = "select User_Id,User_Pwd,User_Desig,User_Name,AC_Id,AC_Name from Login_Master l 
                    join Auction_Centers a on a.AC_Id=l.Dept_Code
                    where User_Id='$Username'  and status='Active'";
        // log_message("error",'++++'.$query);

        $result = $this->db->query($query)->result();
        if (sizeof($result) > 0) {
            // data found with aadhar  
            $User_Pwd = $result[0]->User_Pwd;
            if ($User_Pwd != $password) {
                // Login Attempt fail so record inserted in attemps list
                login_attempt($Username); 
                return array("Status" => "Login Failed", "Details" => null);
            }

            $Username = $result[0]->User_Id;
            $MarketLoginDetails = array(
                "UserId" => $result[0]->User_Id, "UserName" => $result[0]->User_Name, "UserDesig" => $result[0]->User_Desig, "ACId" => $result[0]->AC_Id, "ACName" => $result[0]->AC_Name
            );
            header("Authorization:".$this->input->post('Authorization'));  
            return array(
                "Status" => "Login Success",
                "Details" => array($MarketLoginDetails)
            );

        } else {
            header("Authorization:''");  
            // Login Attempt fail so record inserted in attemps list
            login_attempt($Username); 
            // no data found with email for staff
            return array("Status" => "Login Failed", "Details" => null);
        }
    }

    public function finished_items() {

        // Sync 
        // $route['sync_service/(:num)']='Sem/sync_service/$1';
        $ACode = $this->input->post('AId');
        validate_number($ACode);
        // $data=file_get_contents("http://36.255.252.196/sem_n/sync_service/$ACode");
        // log_message('error',$data);


        // $service_url= "http://36.255.252.196/sem_n/sync_service/$ACode";
        // $curl = curl_init($service_url);
        // $curl_post_data = "";
        // $json = json_encode($curl_post_data);
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // // curl_setopt($curl, CURLOPT_POST, true);
        // // curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        // $curl_response_n = curl_exec($curl);
        // curl_close($curl);
        


        $query = "select distinct ld.Lot_Id
                    , ld.SubLot_Id
                    , ad.auction_id
                    , fd.Farmer_Id
                    , fd.Aadhar_No
                    , fd.Mobile_No FMobile_No
                    , fd.Farmer_Name
                    , Weight_PerKg LotExp_Quantity
                    , Farmer_Agree
                    , bd.Email_Id Buyer_Id	
                    , bd.Buyer_Name
                    , bd.Mobile_No BMobile_No
                    , hb_amt as Rate_Offered
                    , NoOf_DFLsBrushed NoOf_DFLsBrushed
                    , race RaceBvhCB
                    FROM Lot_Information ld
                    join winners ad on ld.Lot_Id=ad.lot_id
                    join Farmer_Details fd on fd.Aadhar_No=ld.FarmerAadhar_Card and fd.Record_Status='Active'
                    join Buyer_Details bd on bd.Email_Id=ad.buyer_id and bd.Record_Status='Active'
                    where 
                    DATE_FORMAT(ld.Created_On,'%d-%m-%Y')=DATE_FORMAT(CURRENT_DATE,'%d-%m-%Y') and
                    ld.Record_Status='Active' and ld.Ac_Id='$ACode' and cast(TIMESTAMPDIFF(MINUTE,CURRENT_TIMESTAMP,Auction_FinishTime) as SIGNED)<=0
                    and (Farmer_Agree='' or Farmer_Agree is null) and ad.option_2='Active' and hb_amt>0 ORDER BY ld.SubLot_Id";
        // log_message('error',$query.'ooohoooo');
        $result = $this->db->query($query)->result_array();
//        print_r($result);
//        return;
        $size = sizeof($result);
        $data = array();
        if ($size > 0) {
            for ($i = 0; $i < $size; $i++) {
                $data[] = array(
                    "Lot_Id" => $result[$i]['Lot_Id'],
                    "Auction_Id" => $result[$i]['auction_id'],
                    "Farmer_Id" => $this->mcrypt->encrypt($result[$i]['Aadhar_No']),
                    "Farmer_Name" => $result[$i]['Farmer_Name'],
                    "Rate_Offered" => $result[$i]['Rate_Offered'],
                    "Buyer_Name" => $result[$i]['Buyer_Name'],
                    "Buyer_Id" => $result[$i]['Buyer_Id'],
                    "SubLot_Id" => $result[$i]['SubLot_Id']
                    
                );
            }
            return array("Status" => "Fetch Success", "Data" => $data);
        } else {
            return array("Status" => "Fetch Success", "Data" => []);
        }
    }

    public function reeler_request_for_auction_participation() {
        $ACenter = $this->input->post('ACenter');
        validate_number($ACenter);
        $query = "SELECT  b.Buyer_Id
                    ,b.Email_Id
                    ,Buyer_Name
                    ,ReqStatus
                    ,DATE_FORMAT(a.Created_On,'%h:%i%p') AS ReqTime
                    FROM AuctionRequest a
                    join Buyer_Details b on b.Email_Id=a.BuyerId
                    where ReqStatus is null and ACenter='$ACenter' and DATE_FORMAT(a.Created_On,'%d-%m-%y')=DATE_FORMAT(CURRENT_DATE,'%d-%m-%y')  GROUP BY a.RowId";
        // log_message("error",$query .'reeler request');            
        $result = $this->db->query($query)->result_array();
        $size = sizeof($result);
        $Auctionrequest = array();
        if ($size > 0) {
            for ($i = 0; $i < $size; $i++) {
                $Auctionrequest[] = array(
                    "BuyerId" => $result[$i]['Buyer_Id'],
                    "EmailId" => $result[$i]['Email_Id'],
                    "BuyerName" => $result[$i]['Buyer_Name'],
                    "ReqTime" => $result[$i]['ReqTime']
                );
            }
            return array("Status" => "Success", "AuctionRequest" => $Auctionrequest);
        } else {
            return array("Status" => "Failed", "AuctionRequest" => null);
        }
    }

    public function send_reelers_request_accept_or_reject() {
//        {"BuyerId":"bapi@gmail.com","ACenter":"14","ReqStatus":"No","Created_By":"testadmin","Imei":"584456985632455"}

        $BuyerId = $this->input->post('BuyerId');
        $ACenter = $this->input->post('ACenter');
        $ReqStatus = $this->input->post('ReqStatus');
        $Created_By = $this->input->post('Created_By');
        $Imei = $this->input->post('Imei');

        validate_number($ACenter);

        // $this->load->helper('email'); 
        if(!valid_email($Username)){
            return array("Status" => "Update Failed");
        }   

        if(!(strtolower($ReqStatus)=="yes" || strtolower($ReqStatus)=="no")){
            return array("Status" => "Update Failed");
        }

        validate_number($Imei); 

        // $query = "UPDATE auctionrequest Set ReqStatus='$ReqStatus' ,Modified_On=CURRENT_DATE,Modified_By='$Created_By'
        //             where BuyerId='$BuyerId' and ACenter='$ACenter' and DATE_FORMAT(Created_On,'%d-%m-%y')=DATE_FORMAT(CURRENT_DATE,'%d-%m-%y')
        //             and RowId=(select max(RowId) where BuyerId='$BuyerId' and ACenter='$ACenter' and ReqStatus is null)";

        $query = "UPDATE auctionrequest Set ReqStatus='$ReqStatus' ,Modified_On=CURRENT_DATE,Modified_By='$Created_By'
                    where BuyerId='$BuyerId' and ACenter='$ACenter' and DATE_FORMAT(Created_On,'%d-%m-%y')=DATE_FORMAT(CURRENT_DATE,'%d-%m-%y')
                    and 
                    RowId in (select * from (select max(RowId) from auctionrequest where BuyerId='$BuyerId' and ACenter='$ACenter' and ReqStatus is null)tmp)";

         // log_message("error",$query);

        $this->db->query($query);

        // $path='images/insert_statements.sql';
        // $myfile = fopen($path, "a") or die("Unable to open file!");
        // $txt = "$query;\n";
        // fwrite($myfile, $txt);


        if ($this->db->affected_rows() > 0) {
            return array("Status" => "Update Success");
        } else {
            return array("Status" => "Update Failed");
        }
    }

    function send_farmer_acceptance() {
//       {"Acceptence":"Yes","LotId":"2711201814001","BuyerId":"one@gmail.com","AuctionId":"1","FAadhar_No":"584456985632","userId":"testadmin","Lot_Image":""}

        $Acceptence = $this->input->post("Acceptence");
        $LotId = $this->input->post("LotId");
        $BuyerId = $this->input->post("BuyerId");
        $AuctionId = $this->input->post("AuctionId");
        $FAadhar_No = $this->mcrypt->decrypt($this->input->post("FAadhar_No"));
        $userId = $this->input->post("userId");
        $Lot_Image = $this->input->post("Lot_Image");

        validate_number($LotId);
        validate_email($BuyerId);
        validate_number($FAadhar_No);
        if(!(strtolower($Acceptence)=="yes" || strtolower($Acceptence)=="no")){
            return array("Status" => "Insert Failed");
        }
    
        // if(is_base64($Lot_Image)==FALSE){
        //     return array("Status" => "Insert Failed");
        // }

        validate_number($AuctionId);


        $file_name = "";
        if ($Lot_Image !== "") {
            $Lot_Image = base64_decode($Lot_Image);
            $file_name = 'uploads/FarmerSigns/' . $LotId . '_' . $AuctionId . '.jpg';
           // file_put_contents($file_name, $Lot_Image);
        }
       
        if(strtolower($Acceptence)=="yes"){
              $query = "update Lot_Information set Farmer_Agree='$Acceptence',Option3='$file_name',Option2='$BuyerId' where Lot_Id='$LotId' and auction_id_unique='$AuctionId' and FarmerAadhar_Card='$FAadhar_No'";
         }else if(strtolower($Acceptence)=="no"){
             $query = "update Lot_Information set Farmer_Agree='$Acceptence',Option3='$file_name' where Lot_Id='$LotId' and auction_id_unique='$AuctionId' and FarmerAadhar_Card='$FAadhar_No'";
         }
      
        $this->db->query($query);
        


        // $path='images/insert_statements.sql';
        // $myfile = fopen($path, "a") or die("Unable to open file!");
        // $txt = "$query;\n";
        // fwrite($myfile, $txt);



        if ($this->db->affected_rows() > 0) {

            // Transaction Details store both accepted and rejected transactions

            $trans_query = "INSERT INTO `transaction_details`(`Auction_Id`, `Lot_Id`, `Buyer_Id`, `Farmer_Id`, `Farmer_Agree`) VALUES ('$AuctionId', '$LotId', '$BuyerId', '$FAadhar_No', '$Acceptence')";
            $flag = $this->db->query($trans_query);

            $path='images/insert_statements.sql';
            $myfile = fopen($path, "a") or die("Unable to open file!");
            $txt = "$query;\n";
            fwrite($myfile, $txt);

            $row_id = $this->db->insert_id();
            if ($row_id > 0) {
                file_put_contents($file_name, $Lot_Image);
            }
            $transaction_id = 'TXN'.(rand(1000000, 9999999) + $row_id);
            $update_query = "UPDATE Transaction_Details SET Transaction_Id='$transaction_id' where RowId='$row_id'";
            $this->db->query($update_query);


            $path='images/insert_statements.sql';
            $myfile = fopen($path, "a") or die("Unable to open file!");
            $txt = "$query;\n";
            fwrite($myfile, $txt);


            // trans qr code 
            $this->generate_qr($transaction_id);
            
            return array("Status" => "Insert Success");
        } else {
            return array("Status" => "Insert Failed");
        }
    }

    public function generate_qr($content) {
        $this->load->library('ciqrcode');
       
        // end of auction centers details

        $params['data'] = $content;
        $params['level'] = 'H';
        $params['size'] = 10;
        $params['savename'] = 'uploads/Transactions/'.$content. '.png';
        $this->ciqrcode->generate($params);
        $data['path'] = 'QR_Code/tes.png';
        $data['page_url'] = 'qr_generator';
    }

}
