<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Reeler_Model extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->load->library("MCrypt",'','mcrypt'); 
         $this->load->helper('auth');
    }

    public function get_reeler_apk_version() {
        $row = $this->db->select(array('App_Vesion'))
                        ->from('appversion_master')
                        ->where(array('Row_Id' => "2"))
                        ->limit(1)
                        ->get()->result_array();

        return array("Version" => $row[0]['App_Vesion']);
    }

    public function get_buyer_details() {

        $user_id = $this->mcrypt->decrypt($this->input->post('Username'));
        $password = $this->mcrypt->decrypt($this->input->post('password'));
        // log_message("error",'--Buyer Details--'.json_encode($_POST));

        validate_number($user_id);  


        if (!(count_login_attempt($user_id) < 5)) {
             return array("Status" => "You’ve reached the maximum login attempts for today. So please try tomorrow", "Details" => null);
        }

        
        $query = "select User_Id,User_Pwd,User_Mail,Dept_Code,User_Desig,User_Name,Mobile_No,OTP_Flag ,Licence_No,Aadhar_No,Address from Login_Master l join Buyer_Details b on b.Email_Id=l.User_Id where status='Active' and  right(Aadhar_No,8)=$user_id";
 
        $result = $this->db->query($query)->result();
        // log_message("Error","hiiiiiiiiiiii".sizeof($result));
        if (sizeof($result) > 0) {
            // data found with aadhar
            $OTP_Flag = $result[0]->OTP_Flag;

            if ($OTP_Flag != "3") {
                // Login Attempt fail so record inserted in attemps list
                login_attempt($user_id); 
                return array("Status" => "Not Registered", "Details" => null);
            }

            $User_Pwd = $result[0]->User_Pwd;
            if ($User_Pwd != $password) {
                // Login Attempt fail so record inserted in attemps list
                login_attempt($user_id); 
                return array("Status" => "Login Failed", "Details" => null);
            }

            $Username = $result[0]->User_Id;
            $BuyerLoginDetails = array(
                "BuyerId" => $result[0]->User_Mail, "EmailId" => $result[0]->User_Id, "Buyer_Name" => $result[0]->User_Name, "User_Designation" => $result[0]->User_Desig,
                "Mobile_No" => $this->mcrypt->encrypt($result[0]->Mobile_No), "Aadhar_No" =>$this->mcrypt->encrypt($result[0]->Aadhar_No), "Licence" => $result[0]->Licence_No, "Address" => $result[0]->Address
            );

            // get Auction Center Details..
            $Au_query = "SELECT AC_Id,AC_Name FROM auction_centers";
            $ACDetails = $this->db->query($Au_query)->result();
//            $ACDetails = array();

            // Auth Header
            header("Authorization:".$this->input->post('Authorization'));  
            return array(
                "Status" => "Login Success",
                "Details" => array($BuyerLoginDetails),
                "AuctionDetails" => null,
                "Acenters" => $ACDetails
            );
        } else {
               
            header("Authorization:''");
             // log_message("error","fail block at login");
            // Login Attempt fail so record inserted in attemps list
            login_attempt($user_id);
            // no data found with aadhar
            return array("Status" => "Login Failed", "Details" => null);
        }
    }

    public function get_buyer_action_details() {
      $buyer_id = $this->input->post('Username');
      if(!valid_email($buyer_id)){
            return array("Status" => "Login Success", "AuctionDetails" => array());
      }   


      $date=date('Y-m-d');
      // $date=date('Y-m-d', strtotime(date('Y-m-d') .'  day'));
      $prev_date = date('Y-m-d', strtotime($date .' -2 day'));
$query="SELECT distinct 
           ld.Lot_Id
          ,ld.SubLot_Id
          ,ld.NoOf_Bins
          ,(CASE 
              WHEN LOWER(ld.Farmer_Agree)='yes' AND w.option_2='Active' THEN 'Winner'
              WHEN LOWER(ld.Farmer_Agree)='no' THEN 'Lost'
              ELSE 'Waiting...'
            END) AS AuctionWinner
          ,DATE_FORMAT(max(ad.Created_On),'%b %d %Y %H:%i%p') Created_On
          ,CONCAT('Rs. ',CAST(MAX(CAST(ad.Rate_Offered AS DECIMAL))AS CHAR) ,'/-') Rate_Offered
          ,fd.Farmer_Name
          ,fd.Farmer_Id
          ,fd.Aadhar_No
          ,fd.Mobile_No
          ,ld.Weight_PerKg LotExp_Quantity
          ,ac.AC_Name
          ,race
          ,CONCAT('Rs. ',CAST(MAX(CAST(w.hb_amt AS DECIMAL))AS CHAR) ,'/-') hb_amt
          ,ad.Rate_Offered costperkg
          ,IFNULL(round(LotNet_Quantity,2),0) LotNet_Weight
          ,IFNULL(FORMAT((ROUND(LotNet_Quantity,0)*w.hb_amt),'##,##0'),0) Net_Amount
          ,ad.Created_On as ad_date
          FROM Auction_Details ad 
          LEFT JOIN Lot_Information ld  ON ad.Lot_Id=ld.Lot_Id 
          LEFT JOIN Farmer_Details fd  ON fd.Aadhar_No=ld.FarmerAadhar_Card
          LEFT JOIN Auction_Centers ac  ON ac.AC_Id=ld.Ac_Id
          LEFT JOIN winners w  ON (w.lot_id=ad.lot_id  and w.option_2='Active' and w.buyer_id='$buyer_id')     
          WHERE ad.Buyer_Id='$buyer_id' AND ad.Record_Status='Active' AND ld.Lot_Id IS NOT NULL
          AND  ad.AuctionWinner IN ('Winner','Lost','Pending') AND dateOf_Marketing BETWEEN '$prev_date' AND  '$date'
          GROUP BY ld.Lot_Id
          ,ld.SubLot_Id
          ,ld.NoOf_Bins
          ,ad.AuctionWinner 
          ,fd.Farmer_Name
          ,fd.Farmer_Id
          ,fd.Aadhar_No
          ,fd.Mobile_No
          ,ld.Weight_PerKg
          ,ac.AC_Name,race,LotNet_Quantity,NoOfCrates,WeightOfCrates ORDER BY ad_date DESC ";
          // log_message("error",$query);
          // log_message("error",'in');
      // and (DATE(dateOf_Marketing) BETWEEN '$prev_date' AND  '$date')
      
      // and (MONTH(ad.date)=MONTH(CURRENT_DATE))

      // limit 50

      // log_message('error',$buyer_id.'--'.$query);

        $result = $this->db->query($query)->result();
//        print_r($result);
        $size = sizeof($result);
//        $ADetails = array();
        if ($size > 0) {
            for ($i = 0; $i < $size; $i++) {
                $auction_winner=$result[$i]->AuctionWinner;
                if($auction_winner=="Winner"){
                  $Rate_Offered=$result[$i]->hb_amt;
                }else{
                  // $Rate_Offered=$result[$i]->Rate_Offered;
                  $Rate_Offered='-';
                  // $Rate_Offered=$result[$i]->costperkg;// not usefull
                  
                }
                $ADetails[] = array(
                    "LotId" => $result[$i]->Lot_Id,
                    "AuctionCenter" => $result[$i]->AC_Name,
                    "Biddingprice" => $Rate_Offered,
                    "BiddingDate" => $result[$i]->Created_On,
                    "BiddingStatus" => $result[$i]->AuctionWinner,
                    "BuyerId" => $buyer_id,
                    "race" => $result[$i]->race,
                    "quantity" => $result[$i]->LotNet_Weight,
                    "costperkg" => $result[$i]->costperkg,
                    "totalcost" => $result[$i]->Net_Amount
                );
            }
        }else{
            $ADetails=array();
        }
        return array("Status" => "Login Success", "AuctionDetails" => $ADetails,);
    }

    function get_buyer_action_details_wrt_date(){
              $buyer_id = $this->input->post('Username');
              $date = $this->input->post("date");

              validate_email($buyer_id);  
              if(!check_date_format($date, 'Y-m-d')){
                return array("Status" => "Login Success", "AuctionDetails" =>array());
              }


            $query="SELECT 
                   ld.Lot_Id
                  ,ld.SubLot_Id
                  ,ld.NoOf_Bins
                  ,(CASE 
                      WHEN LOWER(ld.Farmer_Agree)='yes' AND w.option_2='Active' THEN 'Winner'
                      WHEN LOWER(ld.Farmer_Agree)='no' THEN 'Lost'
                      ELSE 'Waiting...'
                    END) AS AuctionWinner
                  ,DATE_FORMAT(ad.Created_On,'%b %d %Y %H:%i%p') Created_On
                  ,CONCAT('Rs. ',CAST(MAX(CAST(ad.Rate_Offered AS DECIMAL))AS CHAR) ,'/-') Rate_Offered
                  ,fd.Farmer_Name
                  ,fd.Farmer_Id
                  ,fd.Aadhar_No
                  ,fd.Mobile_No
                  ,ld.Weight_PerKg LotExp_Quantity
                  ,ac.AC_Name
                  ,race
                  ,CONCAT('Rs. ',CAST(MAX(CAST(w.hb_amt AS DECIMAL))AS CHAR) ,'/-') hb_amt
                  ,ad.Rate_Offered costperkg
                  ,IFNULL(round(LotNet_Quantity,2),0) LotNet_Weight
                  ,IFNULL(FORMAT((ROUND(LotNet_Quantity,0)*w.hb_amt),'##,##0'),0) Net_Amount
                  ,ad.Created_On as ad_date
                  FROM Auction_Details ad 
                  LEFT JOIN Lot_Information ld  ON ad.Lot_Id=ld.Lot_Id 
                  LEFT JOIN Farmer_Details fd  ON fd.Aadhar_No=ld.FarmerAadhar_Card
                  LEFT JOIN Auction_Centers ac  ON ac.AC_Id=ld.Ac_Id
                  LEFT JOIN winners w  ON (w.lot_id=ad.lot_id  and w.option_2='Active' and w.buyer_id='$buyer_id')     
                  WHERE ad.Buyer_Id='$buyer_id' AND ad.Record_Status='Active' AND ld.Lot_Id IS NOT NULL
                  AND  ad.AuctionWinner IN ('Winner','Lost','Pending') AND dateOf_Marketing='$date'
                  GROUP BY ld.Lot_Id
                  ,ld.SubLot_Id
                  ,ld.NoOf_Bins
                  ,ad.AuctionWinner 
                  ,fd.Farmer_Name
                  ,fd.Farmer_Id
                  ,fd.Aadhar_No
                  ,fd.Mobile_No
                  ,ld.Weight_PerKg
                  ,ac.AC_Name,race,LotNet_Quantity,NoOfCrates,WeightOfCrates,Rate_Offered ORDER BY ad_date DESC ";
          // log_message("error",$query);
          // log_message("error",'in');
      // and (DATE(dateOf_Marketing) BETWEEN '$prev_date' AND  '$date')
      
      // and (MONTH(ad.date)=MONTH(CURRENT_DATE))

      // limit 50

      // log_message('error',$buyer_id.'--'.$query);

        $result = $this->db->query($query)->result();
//        print_r($result);
        $size = sizeof($result);
//        $ADetails = array();
        if ($size > 0) {
            for ($i = 0; $i < $size; $i++) {
                $auction_winner=$result[$i]->AuctionWinner;
                if($auction_winner=="Winner"){
                  $Rate_Offered=$result[$i]->hb_amt;
                }else{
                  $Rate_Offered=$result[$i]->Rate_Offered;
                }
                $ADetails[] = array(
                    "LotId" => $result[$i]->Lot_Id,
                    "AuctionCenter" => $result[$i]->AC_Name,
                    "Biddingprice" => $Rate_Offered,
                    "BiddingDate" => $result[$i]->Created_On,
                    "BiddingStatus" => $result[$i]->AuctionWinner,
                    "BuyerId" => $buyer_id,
                    "race" => $result[$i]->race,
                    "quantity" => $result[$i]->LotNet_Weight,
                    "costperkg" => $result[$i]->costperkg,
                    "totalcost" => $result[$i]->Net_Amount
                );
            }
        }else{
            $ADetails=array();
        }
        return array("Status" => "Login Success", "AuctionDetails" => $ADetails);
      
    }

    public function offered_bid() {

        date_default_timezone_set('Asia/Kolkata');
        $timestamp = time();
        $date_time = date("Y-m-d H:i:s", $timestamp);

        $Aid = "AUC" . rand(10000, 99999);
        $log = "AUC" . rand(10000, 99999);

        $AId = $this->input->post("AId");
        $Lot_Id = $this->input->post("LotId");
        $BuyerId = $this->input->post("BuyerId");
        $RateOffered = $this->input->post("RateOffered");
        $IpAddress = $this->input->post("IpAddress");
        $RepresentativeName = $this->input->post("RepresentativeName");
        $AadharCardNo = $this->mcrypt->decrypt($this->input->post("AadharCardNo"));
        $message = $this->input->post("message");

        // log_message("error",'-- Aadhar No offer bid---'.$AadharCardNo);
        // log_message("error","---OFFERED BID---".json_encode($_POST));

        // {"AId":"14","LotId":"1605202014003","BuyerId":"durgaraoyejjipurapu@gmail.com","RateOffered":"123","IpAddress":"865221031138544","RepresentativeName":"","AadharCardNo":"5c8f9555d7e61f69d10d4fa0c6e22067","message":"success","auth_flag":"1"}
        validate_number($AId);
        validate_number($Lot_Id);
        validate_email($BuyerId);
        if($AadharCardNo!="-"){
          validate_number($AadharCardNo); 
        } 
        validate_number($RateOffered);



        // Get Highest bid
        $query_highest_bid = "SELECT 
                              Lot_Id
                              ,a.Buyer_Id
                              ,Buyer_Name
                              ,IFNULL(max(CAST(Rate_Offered as decimal(10,2))),0) MaxRate_Offered
                              FROM Auction_Details a
                              join Buyer_Details b on b.Email_Id=a.Buyer_Id
                              WHERE Lot_Id='$Lot_Id' and a.Record_Status='Active' 
                              and Rate_Offered=(select IFNULL(max(CAST(Rate_Offered as decimal(10,2))),0) FROM Auction_Details
                              where  Lot_Id='$Lot_Id' and Record_Status='Active')
                              group by Lot_Id,a.Buyer_Id,Buyer_Name";
//        return $query_highest_bid;
        $result_highest_bid = $this->execute_query($query_highest_bid);
//        print_r($result_highest_bid);

        $result_highest_bid_size = sizeof($result_highest_bid);


//        log_message("Error", '--Log--' . $log);
//        log_message("Error", '--Log--' . $Aid);
        if ($RateOffered == "") {
            // no need to maintains any transaction here...
            $insert_array = array('Auction_Id' => $log, 'Lot_Id' => $Lot_Id, 'Buyer_Id' => $BuyerId, 'Representative_ofBidder' => $RepresentativeName, 'AadharCardNo' => $AadharCardNo, 'QtyofCocoons_purchased' => "", 'Rate_Offered' => $RateOffered, 'AuctionWinner' => "", 'AuctionDesc' => "", 'Option1' => "", 'Option2' => "", 'Option3' => "Please Enter Rate Offered (per kg)", 'Created_By' => $BuyerId, 'Ip_Address' => $IpAddress);
            $this->db->insert("auction_details_logs", $insert_array);
            $insert_id = $this->db->insert_id();


            $update_array = array('Auction_Id' => $Aid . $insert_id);
            $where_array = array("RowId" => $insert_id);
            $return_flag = $this->update_table($update_array, $where_array, 'auction_details_logs');

            return array("Status" => "Please Enter Rate Offered (per kg)");
        }
//        return $result_highest_bid;

        for ($i = 0; $i < $result_highest_bid_size; $i++) {

            $result_highest_bid[$i]->Lot_Id;
            $h_buyer_id = $result_highest_bid[$i]->Buyer_Id;
            if ($h_buyer_id == $BuyerId) {

                $insert_array = array('Auction_Id' => $log, 'Lot_Id' => $Lot_Id, 'Buyer_Id' => $BuyerId, 'Representative_ofBidder' => $RepresentativeName, 'AadharCardNo' => $AadharCardNo, 'QtyofCocoons_purchased' => "", 'Rate_Offered' => $RateOffered, 'AuctionWinner' => "", 'AuctionDesc' => "", 'Option1' => "", 'Option2' => "", 'Option3' => "You are the Highest Bidder for this Lot", 'Created_By' => $BuyerId, 'Ip_Address' => $IpAddress);
                $this->db->insert("auction_details_logs", $insert_array);

                $insert_id = $this->db->insert_id();

                // update auction id with insertion id in inserted row
                $update_array = array('Auction_Id' => $Aid . $insert_id);
                $where_array = array("RowId" => $insert_id);
                $return_flag = $this->update_table($update_array, $where_array, 'auction_details_logs');

                return array("Status" => "You are the Highest Bidder for this Lot");
            } else if ($RateOffered < $result_highest_bid[$i]->MaxRate_Offered) {

                $insert_array = array('Auction_Id' => $log, 'Lot_Id' => $Lot_Id, 'Buyer_Id' => $BuyerId, 'Representative_ofBidder' => $RepresentativeName, 'AadharCardNo' => $AadharCardNo, 'QtyofCocoons_purchased' => "", 'Rate_Offered' => $RateOffered, 'AuctionWinner' => "", 'AuctionDesc' => "", 'Option1' => "", 'Option2' => "", 'Option3' => "Your offered Bid price is less than highest bid price", 'Created_By' => $BuyerId, 'Ip_Address' => $IpAddress);
                $this->db->insert("auction_details_logs", $insert_array);
                $insert_id = $this->db->insert_id();

                // update auction id with insertion id in inserted row
                $update_array = array('Auction_Id' => $Aid . $insert_id);
                $where_array = array("RowId" => $insert_id);
                $return_flag = $this->update_table($update_array, $where_array, 'auction_details_logs');

                return array("Status" => "Your offered Bid price is less than highest bid price");
            } else if ($RateOffered == $result_highest_bid[$i]->MaxRate_Offered) {
                $insert_array = array('Auction_Id' => $log, 'Lot_Id' => $Lot_Id, 'Buyer_Id' => $BuyerId, 'Representative_ofBidder' => $RepresentativeName, 'AadharCardNo' => $AadharCardNo, 'QtyofCocoons_purchased' => "", 'Rate_Offered' => $RateOffered, 'AuctionWinner' => "", 'AuctionDesc' => "", 'Option1' => "", 'Option2' => "", 'Option3' => "Your offered Bid price is already Exists", 'Created_By' => $BuyerId, 'Ip_Address' => $IpAddress);
                $this->db->insert("auction_details_logs", $insert_array);
                $insert_id = $this->db->insert_id();

                // update auction id with insertion id in inserted row
                $update_array = array('Auction_Id' => $Aid . $insert_id);
                $where_array = array("RowId" => $insert_id);
                $return_flag = $this->update_table($update_array, $where_array, 'auction_details_logs');

                return array("Status" => "Your offered Bid price is already Exists");
            }
        }

        $this->db->trans_start();
            $insert_array = array("Auction_Id" => $Aid, "Lot_Id" => $Lot_Id, "Buyer_Id" => $BuyerId, "Representative_ofBidder" => $RepresentativeName
                , "AadharCardNo" => $AadharCardNo, "QtyofCocoons_purchased" => "", "Rate_Offered" => $RateOffered, "AuctionWinner" => "Pending"
                , "AuctionDesc" => "", "Option1" => "", "Option2" =>$message, "Option3" => ""
                , "Created_On" => $date_time, "Created_By" => $BuyerId, "Ip_Address" => $IpAddress
            );
            $this->db->insert("Auction_Details", $insert_array);

            $insert_array1 = array('Auction_Id' => $log, 'Lot_Id' => $Lot_Id, 'Buyer_Id' => $BuyerId, 'Representative_ofBidder' => $RepresentativeName, 'AadharCardNo' => $AadharCardNo, 'QtyofCocoons_purchased' => "", 'Rate_Offered' => $RateOffered, 'AuctionWinner' => "", 'AuctionDesc' => "", 'Option1' => "", 'Option2' => $message, 'Option3' => "Insert Success", 'Created_By' => $BuyerId, 'Ip_Address' => $IpAddress);
            $this->db->insert("Auction_Details_Logs", $insert_array1);            
        $this->db->trans_complete();
        


        if ($this->db->trans_status()) {
            return array("Status" => "Insert Success");
        } else {
            $this->db->trans_rollback();
            return array("Status" => "Insert Fail");
        }
    }

    public function lots_based_on_acid_bid() {
//        {"AId":"14","BId":"bapi@gmail.com"}
        $AId = $this->input->post("AId");
        $BId = $this->input->post("BId");

        if(!is_numeric($AId)){
           return array("Status" => "Fetch Fail", "LotsDetails" => array());
        }

        if(!filter_var($BId, FILTER_VALIDATE_EMAIL)){
            return array("Status" => "Fetch Fail", "LotsDetails" => array());
        }

        // $query1="SELECT  RowId,TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Created_On)*1000 Timediffe from (select * from lot_information WHERE Ac_Id='$AId' ORDER BY RowId DESC LIMIT 1) AS T";

        // $result1 = $this->execute_query($query1);
        // $size = sizeof($result);
        // $bi_time=$result1[0]->Timediffe;



//  ,DATE_FORMAT(Auction_FinishTime, '%d-%m-%Y')+' '+ DATE_FORMAT(Auction_FinishTime,'%H:%i%p') as EndDate
        $query = "SELECT distinct t1.Lot_Id,t1.SubLot_Id,t1.Reelermaxbid_Amount,t2.maxbid_Amount,t1.Timediffe,t1.EndDate,t1.auction_id_unique
                from
                (SELECT 
                      distinct l.Lot_Id,SubLot_Id
                          ,IFNULL(max(Rate_Offered),0) Reelermaxbid_Amount
                          ,TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Auction_FinishTime)*1000 Timediffe
                          ,concat(DATE_FORMAT(Auction_FinishTime, '%d-%m-%Y'),' ',DATE_FORMAT(Auction_FinishTime,'%H:%i:%s')) as EndDate
                          ,auction_id_unique
                       FROM Lot_Information l
                       left join Auction_Details a on a.Lot_Id=l.Lot_Id and Buyer_Id='$BId' and a.Record_Status='Active'
                       WHERE l.Ac_Id='$AId' and l.Record_Status='Active' 
                           and DATE_FORMAT(l.Created_On, '%d-%m-%Y') = DATE_FORMAT(CURRENT_DATE, '%d-%m-%Y') and Auction_FinishTime is not null and 
                       Auction_FinishTime!='' and TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Auction_FinishTime)*1000>0 and TIMESTAMPDIFF(MINUTE,CURRENT_TIMESTAMP,l.Created_On)<=0
                           group by l.Lot_Id,SubLot_Id,Auction_FinishTime) t1
                join   
                (SELECT 
                     distinct l.Lot_Id
                     ,SubLot_Id
                         ,IFNULL(max(Rate_Offered),0) maxbid_Amount
                         ,TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Auction_FinishTime)*1000 Timediffe
                          ,auction_id_unique
                     FROM Lot_Information l
                     left join Auction_Details a on a.Lot_Id=l.Lot_Id and a.Record_Status='Active'
                     WHERE l.Ac_Id='$AId'  and l.Record_Status='Active' 
                         and DATE_FORMAT(L.Created_On, '%d-%m-%Y') = DATE_FORMAT(CURRENT_DATE, '%d-%m-%Y') and Auction_FinishTime is not null and      
                     Auction_FinishTime!='' and TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Auction_FinishTime)*1000>0 and TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,l.Created_On)*1000<=0
                         group by l.Lot_Id,SubLot_Id,Auction_FinishTime) t2
                on
                t1.Lot_Id = t2.Lot_Id";
        // log_message("error","--Get AcId BId--".$query);        
        $result = $this->execute_query($query);
        $size = sizeof($result);

        // current call to strart time diff biddtime

        if ($size > 0) {
            for ($i = 0; $i < $size; $i++) {

                $LotsDetails[] = array(
                    "Lot_Id" => $result[$i]->Lot_Id,
                    "subLot_Id" => $result[$i]->SubLot_Id,
                    "BB_Amt" => $result[$i]->Reelermaxbid_Amount,
                    "HB_Amt" => $result[$i]->maxbid_Amount,
                    "Bid_Time" => $result[$i]->Timediffe-(7*1000),
                    "BidEndDate"=>$result[$i]->EndDate,
                    "auction_id_unique"=>$result[$i]->auction_id_unique
                );
            }
            // -(7*1000)
            return array("Status" => "Fetch Success", "LotsDetails" => $LotsDetails);
        } else {
            return array("Status" => "Fetch Success", "LotsDetails" => array());
        }
    }

    public function get_auction_count() {

        $Username = $this->input->post('Username');
        validate_email($Username);

        $query = "SELECT count(distinct case when ad.AuctionWinner in('Winner','Pending','Lost') then  ld.Lot_Id  else null end) total
                  ,count(distinct case when ad.AuctionWinner='Winner' then ld.Lot_Id  else null end) Winner
                  ,count(distinct case when ad.AuctionWinner='Lost' then ld.Lot_Id  else null end) Lost
                 FROM Auction_Details ad
                 join Lot_Information ld on ad.Lot_Id=ld.Lot_Id and ld.Record_Status='Active'
                 WHERE Buyer_Id='$Username' and ad.Record_Status='Active'";
        $result = $this->execute_query($query);
        $ACounts[] = array("Total" => $result[0]->total, "Winner" => $result[0]->Winner, "Lost" => $result[0]->Lost);
        return array("Status" => "Success", "AuctionCount" => $ACounts);
    }

    public function otp_sent_count($mobile_number){
        $result = $this->execute_query("SELECT COUNT(*) AS total_count FROM otp_limit WHERE mobile_number ='$mobile_number' AND DATE(created_on)=CURRENT_DATE()");
        return $result[0]->total_count;
    }

    public function firebase_push_notification_id() {

        $Token = $this->input->post('Token');
        $Imei = $this->input->post('Imei');
        $UserId = $this->input->post('UserId');

        validate_number($Imei);
        if(!valid_buyerid($UserId)){
          return array("Status" => "Insert Fail","message"=>"Invalid UserId");
        }

        date_default_timezone_set('Asia/Kolkata');
        $timestamp = time();
        $date_time = date("Y-m-d H:i:s", $timestamp);

        $query = "SELECT * FROM FireBase WHERE Imei =$Imei";
        $result = $this->execute_query($query);
        if (sizeof($result) > 0) {

            $update = array('UserId' => $UserId, "Token" => $Token, "Server_time" => $date_time);
            $where = array("Imei" => $Imei);
            if ($this->update_table($update, $where, 'FireBase')) {

                return array("Status" => "Insert Success");
            } else {

                return array("Status" => "Insert Fail");
            }
        } else {

            $insert_array = array("UserId" => $UserId, "Token" => $Token, "Imei" => $Imei, "Server_time" => $date_time);
            if ($this->db->insert("FireBase", $insert_array)) {
                return array("Status" => "Insert Success");
            } else {
                return array("Status" => "Insert Fail");
            }
        }
    }

    public function get_auctions_list() {
        // $query = "select AC_id as AC_Id,AC_Name as AC_Name, Address as AC_Address, Lat as AC_Lat,long as AC_long from auction_centers";
        $query = "select AC_id as AC_Id,AC_Name as AC_Name, Address as AC_Address, Lat as AC_Lat,auction_centers.long as AC_long from auction_centers where AC_Id NOT IN ('14','15')";
        $result = $this->execute_query($query);
        return array("Status" => "Fetch Success", "Acentersdtls" => $result);
    }

    public function bidding_for_others() {
        $OTP = rand(100000, 999999);
        $Aadhar_No = $this->mcrypt->decrypt($this->input->post('Aadhar_No'));
        $Mobile_No = $this->mcrypt->decrypt($this->input->post('Mobile_No'));

        validate_number($Aadhar_No);
        validate_number($Mobile_No);
        //  log_messagE("error",'--data--'.$Aadhar_No.'--'.$Mobile_No);
        // log_messagE("error",json_encode($_POST));
        // $request_headers=$this->input->request_headers();
        // log_message("error",json_encode($request_headers)); 

        // log_message("error","expected key is ".md5(substr($Aadhar_No, 2, strlen($Aadhar_No)).'token'.date('mdY')));

        $query = "SELECT User_Id,User_Name,User_Pwd,Mobile_No,Aadhar_No,OTP,OTP_Flag FROM Login_Master l
                  join Buyer_Details b on User_Id=b.Email_Id
                  where User_Desig='Buyer' and right(b.Aadhar_No,8)='$Aadhar_No' and Mobile_No='$Mobile_No'";
        $result = $this->execute_query($query);

        $size = sizeof($result);
        if ($size > 0) {
            for ($i = 0; $i < $size; $i++) {
                $OTP_Flag = $result[$i]->OTP_Flag;
                if ($OTP_Flag == "3") {
                    return array("Status" => "Registered", "Otp " => null);
                }
            }
        } else {
            return array("Status" => "Failed", "Otp" => null);
        }

        // send msg
        for ($j = 0; $j < $size; $j++) {
           // $msg = $OTP . " is your One Time Password for Reeler Mobile Resgistration of Sericulture E-Marketing";
            $msg = '[#] '.$OTP . " is your One Time Password for Reeler Mobile Resgistration of Sericulture E-Marketing.\nFmdh82v9RKy";
            $Mobile_No = $result[$j]->Mobile_No;
//            $OTP_Flag = $result[$i]->OTP_Flag;
            // HERE call send sms apis method.
            $this->send_sms($msg, $Mobile_No);
            // Update OTP_Flag and OTP

            $update_array = array("OTP" => $OTP, "OTP_Flag" => "2");
            $where_array = "right(Aadhar_No,8)=" . $Aadhar_No . " and Mobile_No=" . $Mobile_No;
            $return_flag = $this->update_table($update_array, $where_array, 'Buyer_Details');
        }

        $this->otp_request($OTP, $Mobile_No);
        // return array("Status" => "Success", "Otp" => $OTP);
        return array("Status" => "Success");
    }

    public function send_otp_for_forgot_pwd() {

        $OTP = rand(100000, 999999);
        // log_message('error','send otp for frgt pwd');
        $Aadhar_No = $this->mcrypt->decrypt($this->input->post("Aadhar_No"));
        $Mobile_No = $this->mcrypt->decrypt($this->input->post("Mobile_No"));

        validate_number($Aadhar_No);
        validate_number($Mobile_No);

     //   log_message('error',json_encode($_POST));
        $query = "SELECT User_Id,User_Name,User_Pwd,Mobile_No,Aadhar_No,OTP,OTP_Flag FROM Login_Master l
                  join Buyer_Details b on User_Id=b.Email_Id
                  where User_Desig='Buyer' and right(b.Aadhar_No,8)='$Aadhar_No' and Mobile_No='$Mobile_No'";

        $result = $this->execute_query($query);

        $size = sizeof($result);
        if ($size > 0) {
            for ($i = 0; $i < $size; $i++) {
                $OTP_Flag = $result[$i]->OTP_Flag;

                if ($OTP_Flag != "3") {
                    return array("Status" => "Reeler Not Registered", "Otp " => null);
                }
            }
        } else {
            return array("Status" => "Reeler Not Found", "Otp" => null);
        }


        // send msg
        for ($j = 0; $j < $size; $j++) {
            $msg = '[#] '.$OTP . " is your One Time Password for Reeler Forgot Password of Sericulture E-Marketing.\nFmdh82v9RKy";
            $Mobile_No = $result[$j]->Mobile_No;
            // HERE call send sms apis method.
            $this->send_sms($msg, $Mobile_No);
        }

        $this->otp_request($OTP, $Mobile_No);
        // return array("Status" => "Success", "Otp" => $OTP);
        return array("Status" => "Success");
        // msg sending work is pending here...
    }

    public function create_password() { 
//       {"Aadhar_No":"584489658965","Mobile_No":"9874563214","OldPassword":"","Password":"12345"}
        $Aadhar_No = $this->mcrypt->decrypt($this->input->post("Aadhar_No"));
        $Mobile_No = $this->mcrypt->decrypt($this->input->post("Mobile_No"));
        validate_number($Aadhar_No);
        validate_number($Mobile_No);


       if($_POST["OldPassword"]!=''){
          $OldPassword = sanit_string($this->mcrypt->decrypt($this->input->post("OldPassword")));
        }else{
          $OldPassword =sanit_string($this->input->post("OldPassword"));
        }
        $Password = sanit_string($this->mcrypt->decrypt($this->input->post("Password")));

        if (trim($OldPassword) != '') {
            // log_message("error","Old password is not empty");
            // if your in means reeler using change password that happens after login

            $query_login="select User_Pwd from Login_Master A
                    JOIN Buyer_Details B
                    ON A.User_Id = B.Email_Id
                    WHERE  B.Mobile_No='$Mobile_No' and A.User_Pwd='$OldPassword'";
            //log_message('error',$query_login);
            $rows=$this->execute_query($query_login);       
            if(sizeof($rows)>0){
                // check is new password meets password policy rules or not
                $check_pwd_msg=$this->checkPassword($Password);
                // log_message("error",$check_pwd_msg);
                if($check_pwd_msg!=1){
                    // return;
                    return json_encode(array("status"=>$check_pwd_msg));
                }

                $query = "UPDATE Login_Master A
                        JOIN Buyer_Details B
                        ON A.User_Id = B.Email_Id
                        SET A.User_Pwd = '$Password'
                        WHERE right(B.Aadhar_No,8)='$Aadhar_No' and B.Mobile_No='$Mobile_No' and User_Pwd='$OldPassword'";
              // log_message('error',$query);
                 $return_flag = $this->execute_query($query, 'u');
                 if($return_flag){
                    // insert password history
                    $insert_array=array("aadhaar_no"=>$Aadhar_No,"old_password"=>$OldPassword,"new_password"=>$Password,"status"=>"success");
                    $this->password_history($insert_array); 

                    return array("Status" => "Update Success");
                }else{
                    // insert password history
                    $insert_array=array("aadhaar_no"=>$Aadhar_No,"old_password"=>$OldPassword,"new_password"=>$Password,"status"=>"fail");
                    $this->password_history($insert_array); 
                    return array("Status" => "Update Failed");    
                }

            }else{
                return array("Status" => "Invalid Details");
            }

        } else {
            
            // log_message("error","New Password Creation....".$Password);
            // if your in means reeeler/buyer using forgot password
            // check is new password meets password policy rules or not

            $check_pwd_msg=$this->checkPassword($Password);              
            if($check_pwd_msg!=1){
              return json_encode(array("status"=>$check_pwd_msg));
            }

            $this->db->trans_start();
            
            $query1 = "UPDATE
                            Buyer_Details SET OTP_Flag = 3
                            WHERE right(Aadhar_No,8)='$Aadhar_No' and Mobile_No='$Mobile_No'";

            $return_flag = $this->execute_query($query1, 'u');

            $query2 = "UPDATE Login_Master A
                            JOIN Buyer_Details B
                            ON A.User_Id = B.Email_Id
                            SET A.User_Pwd = '$Password'
                            WHERE right(B.Aadhar_No,8)='$Aadhar_No' and b.Mobile_No='$Mobile_No'";
            $return_flag = $this->execute_query($query2, 'u');

            $this->db->trans_complete();
        }

        $OldPassword='frgt';
        if ($this->db->trans_status()) {
            // insert password history
            $insert_array=array("aadhaar_no"=>$Aadhar_No,"old_password"=>$OldPassword,"new_password"=>$Password,"status"=>"success");
            $this->password_history($insert_array); 
            return array("Status" => "Update Success");
        } else {
            $this->db->trans_rollback();
              // insert password history
              $insert_array=array("aadhaar_no"=>$Aadhar_No,"old_password"=>$OldPassword,"new_password"=>$Password,"status"=>"fail");
              $this->password_history($insert_array); 
            return array("Status" => "Update Failed");
        }
    }

    public function checking_auction_participation_request() {
        $BuyerId = $this->input->post('BuyerId');
        $AId = $this->input->post('AId');

        validate_email($BuyerId);
        validate_number($AId);

        $query = "  SELECT  b.Buyer_Id
                    ,b.Email_Id
                    ,Buyer_Name
                    ,ReqStatus
                    ,ACenter
                    FROM AuctionRequest a
                    join Buyer_Details b on b.Email_Id=a.BuyerId
                    where BuyerId='$BuyerId' and ACenter='$AId'
                    and  DATE_FORMAT(a.Created_On,'%d-%m-%Y')=DATE_FORMAT(CURRENT_DATE,'%d-%m-%Y')
                    and a.RowId=(select max(RowId) FROM AuctionRequest where BuyerId='$BuyerId' and ACenter='$AId')";

        
        $result = $this->execute_query($query);
        $size = sizeof($result);
        
        // log_message("error","CHECKIN G".json_encode($_POST));
        // log_message("error",$query);
        // log_message("error",'SIZE' .$size);

        if ($size == 0) {
            return array("Status" => "Failed", "AuctionRequest" => null);
        }

        for ($i = 0; $i < $size; $i++) {
            if ($result[$i]->ReqStatus == "No") {
                return array("Status" => "No", "AuctionRequest" => null);
            } else if ($result[$i]->ReqStatus == "" || $result[$i]->ReqStatus == null) {
                return array("Status" => "Pending", "AuctionRequest" => null);
            }
        }

        for ($j = 0; $j < $size; $j++) {
            $AuctionRequest[] = array("BuyerId" => $result[$j]->Buyer_Id, "EmailId" => $result[$j]->Email_Id, "BuyerName" => $result[$j]->Buyer_Name, "AId" => $result[$j]->ACenter);
        }
        return array("Status" => "Success", "AuctionRequest" => $AuctionRequest);
    }

    public function sending_auction_participation_request() {

        $BuyerId = $this->input->post("BuyerId");
        $ACenter = $this->input->post("ACenter");
        $ReqStatus = sanit_string($this->input->post("ReqStatus"));
        $Created_By = sanit_string($this->input->post("Created_By"));
        $Imei = sanit_string($this->input->post("Imei"));

        validate_email($BuyerId);
        validate_number($ACenter);
        validate_number($Imei);

        if(!(strtolower($ReqStatus)=="yes" || strtolower($ReqStatus)=="no" || is_null($ReqStatus) || empty($ReqStatus))){
            return array("Status" => "Insert Failed");
        }


       $query = "SELECT  b.Buyer_Id
                    ,b.Email_Id
                    ,Buyer_Name
                    ,ReqStatus
                    ,ACenter
                    FROM AuctionRequest a
                    join Buyer_Details b on b.Email_Id=a.BuyerId
                    where BuyerId='$BuyerId' and ACenter='$ACenter'
                    and  DATE_FORMAT(a.Created_On,'%d-%m-%Y')=DATE_FORMAT(CURRENT_DATE,'%d-%m-%Y')";    
        


        $result = $this->db->query($query)->result_array();
        $size = sizeof($result);
        
        if($size >2){
            return array("Status"=>"limit reached");
        }             

        $query = "SELECT  b.Buyer_Id
                    ,b.Email_Id
                    ,Buyer_Name
                    ,ReqStatus
                    ,ACenter
                    FROM AuctionRequest a
                    join Buyer_Details b on b.Email_Id=a.BuyerId
                    where BuyerId='$BuyerId' and ACenter='$ACenter'
                    and  DATE_FORMAT(a.Created_On,'%d-%m-%Y')=DATE_FORMAT(CURRENT_DATE,'%d-%m-%Y')
                    and a.RowId=(select max(RowId) FROM AuctionRequest where BuyerId='$BuyerId' and ACenter='$ACenter')";

        // log_message('error',$query);

        $result = $this->execute_query($query);
        $size = sizeof($result);
        
        for ($i = 0; $i < $size; $i++) {
            if ($result[$i]->ReqStatus == "" || $result[$i]->ReqStatus == null) {
                return array("Status" => "Pending");
            }
        }

        $insert_array = array("BuyerId" => $BuyerId, "ACenter" => $ACenter, "Created_By" => $Created_By, "Ip_Address" => $Imei);
        $this->db->insert("AuctionRequest", $insert_array);
        if ($this->db->insert_id()>0) {
            return array("Status" => "Insert Success");
        } else {
            return array("Status" => "Insert Failed");
        }
    }

    public function deleting_auction_participation_request_sent_by_reeler() {

        $BuyerId = $this->input->post("BuyerId");
        $ACenter = $this->input->post("ACenter");
        $ReqStatus = sanit_string($this->input->post("ReqStatus"));
        $Created_By = sanit_string($this->input->post("Created_By"));
        $Imei = $this->input->post("Imei");

        validate_email($BuyerId);
        validate_number($ACenter);
        validate_number($Imei);

        $query="select max(RowId) From AuctionRequest  where BuyerId='$BuyerId' and ACenter='$ACenter' and DATE_FORMAT(Created_On,'%d-%m-%Y')=DATE_FORMAT(CURRENT_DATE,'%d-%m-%Y')";
        $result=$this->execute_query($query);
        $count=count($result);
        // log_message("error",'$$$$$$$$$$$$$--'.$count.'---');
        if($count == 0 ){
           return array("Status" => "Not Found");
        }

        $query="select max(RowId) From AuctionRequest  where BuyerId='$BuyerId' and ACenter='$ACenter' and ReqStatus='No' and DATE_FORMAT(Created_On,'%d-%m-%Y')=DATE_FORMAT(CURRENT_DATE,'%d-%m-%Y')";
        $result=$this->execute_query($query);
        $count=count($result);
        // log_message("error",'$$$$$$$$$$$$$--'.$count.'---');
        if($count > 0 ){
           return array("Status" => "Not Found");
        }

        if(strtolower($ReqStatus)=="no"){
            $query = " UPDATE 
                   AuctionRequest Set ReqStatus='$ReqStatus',Modified_On=CURRENT_DATE,Modified_By='$Created_By'
                   where BuyerId='$BuyerId' and ACenter='$ACenter' and DATE_FORMAT(Created_On,'%d-%m-%Y')=DATE_FORMAT(CURRENT_DATE,'%d-%m-%Y')
                   and RowId=(select max(RowId) where BuyerId='$BuyerId' and ACenter='$ACenter' limit 1)";
        } else{
          $query = " UPDATE 
                   AuctionRequest Set ReqStatus='$ReqStatus' ,Modified_On=CURRENT_DATE,Modified_By='$Created_By'
                   where BuyerId='$BuyerId' and ACenter='$ACenter' and DATE_FORMAT(Created_On,'%d-%m-%Y')=DATE_FORMAT(CURRENT_DATE,'%d-%m-%Y')
                   and RowId=(select max(RowId) where BuyerId='$BuyerId' and ACenter='$ACenter' and ReqStatus is null)";
        }
        
        // log_message('error',$query); 

        $this->execute_query($query, 'u');    
        if ($this->db->affected_rows()>0) {
            return array("Status" => "Update Success");
        } else {
            return array("Status" => "Update Failed");
        }
    }

    public function get_all_firebase_notifications_from_server() {
        $Username = $this->input->post("Username");
        $query = "SELECT Id,RowId,Title,Is_Background,MessageBody,ImageFile
                ,CONCAT(DATE_FORMAT(TimeStamp,'%d %b %Y'),' ',DATE_FORMAT(TimeStamp,'%l:%i%p')) AS TimeStamp  
                ,Token
                ,CASE
                  WHEN Token = '' THEN 'All'  
                  ELSE 'Individual'
                  END as Status         
                FROM FireBaseMsgs
                where (Token='$Username' or Token='') and Status='Active'
                order by RowId Asc";

//        echo $query;

        $result = $this->execute_query($query);
        $size = sizeof($result);
        if ($size > 0) {
            for ($i = 0; $i < $size; $i++) {
                $FireBaseMsgs[] = array(
                    "RowId" => $result[$i]->RowId, "Title" => $result[$i]->Title,
                    "MessageBody" => $result[$i]->MessageBody, "TimeStamp" => $result[$i]->TimeStamp, "Status" => $result[$i]->Status
                );
            }
            return array("Status" => "Fetch Success", "FireBaseMsgs" => $FireBaseMsgs);
        } else {
            return array("Status" => "No Data Found", "FireBaseMsgs" => null);
        }
    }

    public function delete_firebase_notifications_msg() {
        $RowId = $this->input->post('RowId');
        $UserId = $this->input->post('UserId');
        $Type = $this->input->post('Type');

        if ($Type == "All") {
            $query = "UPDATE FireBaseMsgs set Status='InActive' WHERE Token='$UserId'  and Status='Active'";
        } else {
            $query = "UPDATE FireBaseMsgs set Status='InActive' WHERE Id='$RowId'";
        }

        if ($this->execute_query($query, 'u')) {
            return array("Status" => "Delete Success");
        } else {
            return array("Status" => "Delete Failed");
        }
    }

    public function store_reeler_current_logged_in_auction_center() {

        $AId = $this->input->post("AId");
        $BId = $this->input->post("BId");
        $Imei = $this->input->post("Imei");



        validate_number($AId);
        // validate_email($BId);
        validate_number($Imei);
        valid_buyerid($BId);

        // $query = "delete from DailyNotifications where DATE_FORMAT(Created_On, '%d-%m-%Y')< DATE_FORMAT(CURRENT_DATE, '%d-%m-%Y')";
        $query = "delete from DailyNotifications where DATE_FORMAT(Created_On, '%d-%m-%Y')< DATE(NOW()) - INTERVAL 12 DAY";
        $result = $this->execute_query($query,'u');

        $query1 = "SELECT 1 FROM DailyNotifications WHERE Imei = '$Imei' and DATE_FORMAT(Created_On, '%d-%m-%Y')< DATE_FORMAT(CURRENT_DATE, '%d-%m-%Y')";
        $result1 = $this->execute_query($query1);

        $size = sizeof($result1);
        if ($size > 0) {
            $query_u = "UPDATE DailyNotifications set Ac_Id='$AId' where DATE_FORMAT(Created_On, '%d-%m-%Y') = DATE_FORMAT(CURRENT_DATE, '%d-%m-%Y') and Imei='$Imei'";
            $this->execute_query($query_u, 'u');
        } else {

            $insert_array = array("Imei" => $Imei, "Buyer_Id" => $BId, "Ac_Id" => $AId, "Created_By" => $BId, "Ip_Address" => $Imei);
            $this->db->insert("DailyNotifications", $insert_array);
        }

        return array("Status" => "Insert Success");
    }

    public function auction_start_time() {
      // log_message('error','auction start time in 000');
//        {"ACId":"14"}
        $Ac_Id = $this->input->post("ACId");
        validate_number($Ac_Id);
        // $query = "  select TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Created_On)*1000 AS Timediffe, 
        //             DATE_FORMAT(Created_On,'%d %b %Y') as Starttime
        //             FROM Lot_Information
        //             where Ac_Id='$Ac_Id' and Record_Status='Active'
        //             and DATE_FORMAT(Created_On, '%d-%m-%Y')=DATE_FORMAT(CURRENT_DATE, '%Y-%m-%d')
        //             and Auction_FinishTime is not null 
        //             and Auction_FinishTime!=''
        //             and TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Created_On)*1000 >0
        //             group by Created_On";

        $query=" SELECT TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Created_On)*1000 AS Timediffe, 
                    DATE_FORMAT(Created_On,'%d %b %Y') AS Starttime
                    FROM Lot_Information
                    WHERE Ac_Id='$Ac_Id' AND Record_Status='Active'
                    AND DATE(Created_On)=DATE(CURRENT_DATE)
                    AND Auction_FinishTime IS NOT NULL 
                    AND Auction_FinishTime!=''
                    AND TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Created_On)*1000 >0
                    GROUP BY Created_On";
        // log_message('error','auction start time in 1111'.$query);
        $result = $this->execute_query($query);
//        print_r($result);
        $size = sizeof($result);
        if ($size > 0) {
            for ($i = 0; $i < $size; $i++) {
                $successresponse = array("Status" => "Success", "Starttime" => $result[$i]->Starttime, "Timediffe" => $result[$i]->Timediffe);
            }
            return $successresponse;
        } else {
            return array("Status" => "No Auctions", "Starttime" => null, "Timediffe" => null);
        }
    }

    public function auction_start_time1() {
      // log_message('error','auction start time in 1111');
//        {"ACId":"14"}
        $Ac_Id = $this->input->post("ACId");
        validate_number($Ac_Id);
        // $query = "  select TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Created_On)*1000 AS Timediffe, 
        //             DATE_FORMAT(Created_On,'%d %b %Y') as Starttime
        //             FROM Lot_Information
        //             where Ac_Id='$Ac_Id' and Record_Status='Active'
        //             and DATE_FORMAT(Created_On, '%d-%m-%Y')=DATE_FORMAT(CURRENT_DATE, '%Y-%m-%d')
        //             and Auction_FinishTime is not null 
        //             and Auction_FinishTime!=''
        //             and TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Created_On)*1000 >0
        //             group by Created_On";

       // $query=" SELECT (TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Created_On)+5)*1000 AS Timediffe, 

        $query=" SELECT (TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Created_On)+5)*1000 AS Timediffe, 
                    DATE_FORMAT(Created_On,'%d %b %Y') AS Starttime
                    FROM Lot_Information
                    WHERE Ac_Id='$Ac_Id' AND Record_Status='Active'
                    AND DATE(Created_On)=DATE(CURRENT_DATE)
                    AND Auction_FinishTime IS NOT NULL 
                    AND Auction_FinishTime!=''
                    AND TIMESTAMPDIFF(SECOND,CURRENT_TIMESTAMP,Created_On)*1000 >0
                    GROUP BY Created_On";
        $result = $this->execute_query($query);
//        print_r($result);
        $size = sizeof($result);
        if ($size > 0) {
            for ($i = 0; $i < $size; $i++) {
                $successresponse = array("Status" => "Success", "Starttime" => $result[$i]->Starttime, "Timediffe" => $result[$i]->Timediffe);
            }
            return $successresponse;
        } else {
            return array("Status" => "No Auctions", "Starttime" => null, "Timediffe" => null);
        }
    }

    public function send_sms($message, $phone_number) {
        validate_number($phone_number);
        $sent_count=$this->otp_sent_count($phone_number);
        if($sent_count<=2){
          // store otp request.
            // $this->otp_request($message, $phone_number);

          // if limit crossed no need to send
            $Remarks = $message;
            $user = "peritustech"; //your username
            $password = "Peritus@123"; //your password
            $mobilenumbers = "91" . $phone_number; //enter Mobile numbers comma seperated
            $message = strip_tags($Remarks); //enter Your Message
            $message = str_replace("?", "", $message);
            $senderid = "E-SERI"; //Your senderid
            $messagetype = "N"; //Type Of Your Message
            $DReports = "Y"; //Delivery Reports
            $url = "http://www.smscountry.com/SMSCwebservice_Bulk.aspx";
            $message = urlencode($message);
            $ch = curl_init();
            if (!$ch) {
                die("Couldn't initialize a cURL handle");
            }
            $ret = curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "User=$user&passwd=$password&mobilenumber=$mobilenumbers&message=$message&sid=$senderid&mtype=$messagetype&DR=$DReports");
            $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $curlresponse = curl_exec($ch); // execute
            
            
        } 
    }


    function otp_request($message,$phone_number){
      // store otps request 
            validate_number($phone_number); 
            
            $insert_array = array("mobile_number" => $phone_number,'message'=>$message);
            $this->db->insert("otp_limit", $insert_array);
    }

    public function update_table($update_array, $where_array, $table_name) {
        $this->db->set($update_array);
        $this->db->where($where_array);
        $this->db->update($table_name);
        return $this->db->affected_rows();
    }

    public function execute_query($query, $action = '') {
        if ($action == "u") {
            return $this->db->query($query);
        } else {
            return $this->db->query($query)->result();
        }
    }


  function checkPassword($pwd) {
    $errors=1;  
    $disallowed_characters_1 = array("!", "_", "-", "+", "=", ":", ";", '"', ',');
    $existance_flag = 0;
    foreach ($disallowed_characters_1 as $value) {
        if (strpos($pwd, $value) !== FALSE) {
            $existance_flag = 1;
            break;
        }
    }

    if ($existance_flag == 1) {
        $errors = "Only this @#%$^&*. Special characters are allowed";
        return $errors;
    }

    $disallowed_characters_2 = array("'", "(", ")", "|", "/");
    $existance_flag1 = 0;
    foreach ($disallowed_characters_2 as $value) {
        if (strpos($pwd, $value) !== FALSE) {
            $existance_flag1 = 1;
            break;
        }
    }

    if ($existance_flag1 == 1) {
        $errors = "Only this @#%$^&*. Special characters are allowed";
        return $errors;
    }

    if (strlen($pwd) < 8 || strlen($pwd) > 12) {
        $errors = "Password minimum 8 characters and maximum 12 allowed.";
        return $errors;
    }
    // + indicates one or more digits or numbers.
    if (!preg_match("#[0-9]+#", $pwd)) {
        $errors = "Password must include at least one number!";
        return $errors;
    }
    // + indicates one or more 
    if (!preg_match("#[a-z]+#", $pwd)) {
        $errors = "Password must include at least one letter!";
        return $errors;
    }

    if (!preg_match("#[A-Z]+#", $pwd)) {
        $errors = "Password must include at least one Captical letter!";
        return $errors;
    }

    if (!preg_match('/[@#%$^&*.]/', $pwd)) {
        $errors = "Password should contain at least one special character";
        return $errors;
    }

    if (preg_match("/\s/", $pwd)) {
        $errors = "Password should not contain any white space";
        return $errors;
    }
    return $errors;
}

  function password_history($insert_data){
      $this->db->insert('passwords_history',$insert_data);
  }


  function verify_otp(){ 
      $mobile_number =$this->mcrypt->decrypt($this->input->post('mobile_number'));
      $msg = $this->input->post('otp');

      if (!(count_otp_verification_attempts($mobile_number) < 3)) { 
             return array("Status" =>'You’ve reached the maximum OTP verification attempts');
      } 

      if(is_numeric($mobile_number) && is_numeric($msg)){
          $query="SELECT message,id FROM otp_limit WHERE (mobile_number ='$mobile_number' AND DATE(created_on)=CURRENT_DATE()) ORDER BY id DESC LIMIT 1";
          $result = $this->db->query($query)->row_array();
          if(isset($result['id'])){
              if($result['message']==$msg){
                    return array("Status" => "Success");
              }
          }
          // store verify attempt if fails
          otp_verify_attempt($mobile_number,$msg);
          return array("Status" => "Fail");
      }else{
          // store verify attempt if fails
          otp_verify_attempt($mobile_number,$msg);
          return array("Status" => "Fail");
      }
  }

  public function logout() {
         $username=$this->mcrypt->decrypt($this->input->post('username')); 
         $device_id=$this->input->post('device_id'); 
         log_message("error","at logout function".json_encode($_POST));
         delete_api_keys($username,$device_id);
         return array("Status" => "Success");
  }
    
}

?>