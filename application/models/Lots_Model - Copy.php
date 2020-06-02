<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Lots_Model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    function farmer_marketing_search($farmerid, $state) {
        $ACode=$this->input->post("AId");
        if ($state == "AP") {
            $this->db->select(array('RowId', 'Farmer_Id as farmerID', 'Farmer_Name as farmerName', 'St_Name', 'VILLAGE_VC_NAME as farmerVillage', 'Aadhar_No', 'Mobile_No as farmerMobile'));
            $this->db->from('farmer_details');
            $this->db->join('rv_master', 'rv_master.VILLAGE_CODE = farmer_details.vill_Name');
            $this->db->where(array('farmer_details.Farmer_Id' => "$farmerid", 'farmer_details.St_type' => "$state"));
            $data = $this->db->get()->row_array();
            if (sizeof($data) > 0) {
                
                $data['Status'] = "Success";
                $data['Data']=$this->get_lots_list_based_on_farmer_id($data['Aadhar_No'],$ACode);
            } else {
                $data['Status'] = "Fail";
            }
            // log_message("error",$this->db->last_query());
            return $data;
        } else {
            $query = "SELECT 
                        RowId
                       ,Farmer_Id
                       ,Farmer_Name
                       ,St_Name
                       ,vill_Name as VILLAGE_VC_NAME
                       ,Aadhar_No
                       ,Mobile_No
                       FROM Farmer_Details where
                       (Farmer_Id='$Aadhar_No' or Aadhar_No='$Aadhar_No') and Record_Status='Active' and St_Name!='AP'";
            log_message('error','-- OS BLOCK--'.$query);
            return $this->db->query($query)->result_array();
        }
    }

    public function add_lots() {
        // date time
        date_default_timezone_set('Asia/Kolkata');
        $timestamp = time();
        $date_time = date("Y-m-d H:i:s", $timestamp);

        if (isset($_POST) && count($_POST) > 0) {

            $entry_type = $this->input->post('entry_type');
            $Chawkie_CenterName = "";
            $chawkie = $this->input->post('Chawkie');
            if ($chawkie != "Own") {
                $Chawkie_CenterName = $this->input->post('Chawkie_CenterName');
            }
            if ($entry_type == "Multiple") {

                $ddl_nooflots = (int) $this->input->post('ddl_nooflots');
                for ($j = 0; $j < $ddl_nooflots; $j++) {
                    $params = array(
                        'Lot_Id' => $this->lot_id($this->input->post('ACode')),
                        'SubLot_Id' => $this->sub_lot_id($this->input->post('ACode')),
                        'Farmer_Id' => "",
                        'FarmerAadhar_Card' => $this->input->post('FarmerAadhar_Card'),
                        'Race' => $this->input->post('Race'),
                        'NoOf_DFLsBrushed' => "",
                        'Date_OfBrushing' => "",
                        'Chawkie' => $chawkie,
                        'Chawkie_CenterName' => $Chawkie_CenterName,
                        'DateOf_Marketing' => $this->input->post('DateOf_Marketing'),
                        'Weight_PerKg' => (((int) $this->input->post('Weight_PerKg')) / $ddl_nooflots),
                        'NoOf_Lots' => "",
                        'NoOf_Bins' => $this->sub_lot_id($this->input->post('ACode')),
                        'Ac_Id' => $this->input->post('ACode'),
                        'Created_On' => $date_time,
                        'Created_By' => $this->input->post('userId'),
                        'Ip_Address' => $this->input->post('Ip_Address'),
                        'Option1' => "",
                        'Option2' => "",
                        'Option3' => ""
                    );
                    $lot_information_id = $this->add_lot_information($params);
                }
            } else {
                $params = array(
                    'Lot_Id' => $this->lot_id($this->input->post('ACode')),
                    'SubLot_Id' => $this->sub_lot_id($this->input->post('ACode')),
                    'Farmer_Id' => "",
                    'FarmerAadhar_Card' => $this->input->post('FarmerAadhar_Card'),
                    'Race' => $this->input->post('Race'),
                    'NoOf_DFLsBrushed' => "",
                    'Date_OfBrushing' => "",
                    'Chawkie' => $this->input->post('Chawkie'),
                    'Chawkie_CenterName' => "",
                    'DateOf_Marketing' => $this->input->post('DateOf_Marketing'),
                    'Weight_PerKg' => $this->input->post('Weight_PerKg'),
                    'NoOf_Lots' => "",
                    'NoOf_Bins' => $this->sub_lot_id($this->input->post('ACode')),
                    'Ac_Id' => $this->input->post('ACode'),
                    'Created_On' => $date_time,
                    'Created_By' => $this->input->post('userId'),
                    'Ip_Address' => $this->input->post('Ip_Address'),
                    'Option1' => "",
                    'Option2' => "",
                    'Option3' => ""
                );
                $lot_information_id = $this->add_lot_information($params);
            }
            if ($lot_information_id > 0) {
                return array('Status' => 'Success', 'message' => 'Lots Added Successfully...');
            } else {
                return array('Status' => 'Fail', 'message' => 'Lots Adding Process Failed...');
            }
        } else {
            return array('Status' => 'Fail', 'message' => 'Please Provide Valid data...', 'url' => '');
        }
    }
    
    function add_lot_information($params) {
        $this->db->insert('lot_information', $params);
        return $this->db->insert_id();
    }
    
    function lot_id($AId) {

        $date = (int) date('d') . date('m') . date('Y');
        $total_records = $this->get_count_records($AId);
        $count = strlen($total_records);
        $Ac_Id =$AId;

        if ($count == 1) {
            $LotNo = $date . $Ac_Id . "00" . $total_records;
        } else if ($count == 2) {
            $LotNo = $date . $Ac_Id . "0" . $total_records;
        } else if ($count == 3) {
            $LotNo = $date . $Ac_Id . "" . $total_records;
        }
        return $LotNo;
    }

    function sub_lot_id($AId) {
        $total_records = $this->get_count_records($AId);
        $count = strlen($total_records);
        if ($count == 1) {
            $sub_lot_id = "00" . $total_records;
        } else if ($count == 2) {
            $sub_lot_id = "0" . $total_records;
        } else if ($count == 3) {
            $sub_lot_id = "" . $total_records;
        }

        return $sub_lot_id;
    }

    function get_count_records($AId) {
//        $this->load->model('LotInformationModel');
//        $lim = new LotInformationModel();
        $total_records = $this->get_daily_lots_count($AId);
        return $total_records = (int) (($total_records[0]['total_records']) + 1);
    }

    function get_daily_lots_count($AId) {
        
        $query = "select count(*) as total_records from Lot_Information where Record_Status='Active' and DATE_FORMAT(Created_On, '%d-%m-%Y') = DATE_FORMAT(CURRENT_DATE, '%d-%m-%Y') and Ac_Id='$AId' limit 1";
        return $this->db->query($query)->result_array();
    }
 
    // and LotNet_Quantity is NULL 

    function get_lots_list_based_on_farmer_id($Aadhar_No,$ACode){
            $query="SELECT distinct Lot_Id,RowId,SubLot_Id FROM `lot_information` where FarmerAadhar_Card='$Aadhar_No' AND DateOf_Marketing=DATE_FORMAT(CURRENT_DATE,'%y-%m-%d') AND Ac_Id='$ACode' AND Farmer_Agree='yes' GROUP BY Lot_Id";
            // log_message("error",'--'.$this->db->last_query());
            return $this->db->query($query)->result_array();

            // (Farmer_Agree=''  OR Farmer_Agree IS NULL) 

    }

    function update_lots_details(){


        // [{"createdOn":"2019-01-03","lotid":"0","acID":"14","createdBy":"Testadmin","imei":"911444005670979","noOfCrates":"3","weight":"320"}]

        $data=$this->input->post('lotDetailsJson');
        // log_message('error',$data);
        $data=json_decode($data);
        $createdOn=$data[0]->createdOn;
        $lotid=$data[0]->lotid;
        $acID=$data[0]->acID;
        $createdBy=$data[0]->createdBy;
        $imei=$data[0]->imei;
        $noOfCrates=$data[0]->noOfCrates;
        $weight=$data[0]->weight;
        $rowid=$data[0]->rowid;

        if((!is_numeric($weight)) || $weight<=0){
            return array('Status' => 'Fail'); 
        }


        $query="select  * from `lot_information` where Lot_Id='$lotid' and Ac_Id='$acID' and LotNet_Quantity is NULL limit 1";
        // log_message("error",$query);

        $rows=$this->db->query($query)->row_array();
        if(sizeof($rows)==0){
            return array("Status"=>"exists");
        }

        // $LotNet_Quantity = $this->input->post('LotNet_Quantity');
        // $NoOfCrates = $this->input->post('NoOfCrates');
        // $WeightOfCrates = $this->input->post('WeightOfCrates');
        // $RaceBvhCB = $this->input->post('Race');
        // $Cdp = $this->input->post('Cdp');
        // $RowId = $this->input->post('RowId');


        // crates 
        $auction_center = $this->get_auction_center($acID);
        $WeightOfCrates = $auction_center['CratesWeight'];    

        $update_array = array(
                            "Lot_Id" =>$lotid, 
                            "LotNet_Quantity" => $weight,
                            "NoOfCrates" => $noOfCrates, 
                            "WeightOfCrates" => $WeightOfCrates
        );
        $flag = $this->update_farmer_agree_lots_details(array("RowId"=>$rowid,"Lot_Id"=>$lotid,"Ac_Id"=>$acID), $update_array);
        if ($flag) {
            return array('Status' => 'Success');
        } else {
            return array('Status' => 'Fail');
        }
    }

       function update_lots_details1(){


        // [{"createdOn":"2019-01-03","lotid":"0","acID":"14","createdBy":"Testadmin","imei":"911444005670979","noOfCrates":"3","weight":"320"}]
        
        // $data=$this->input->post('lotDetailsJson');
        // log_message('error',$data);
        // $data=json_decode($data);
        // log_message('error',json_encode($_POST));
        // $_POST=json_decode('{"createdOn":"25-12-2015 00:00:00","rowid":"1054","lotid":"120220192004 ","acID":"2","createdBy":"Hanuman Market","imei":"192.168.0.191","noOfCrates":"1","weight":"3.78\r"}');
        
        $createdOn=$this->input->post('createdOn');
        $lotid=trim($this->input->post('lotid'));
        $acID=$this->input->post('acID');
        $createdBy=$this->input->post('createdBy');
        $imei=$this->input->post('imei');
        $noOfCrates=$this->input->post('noOfCrates');
        $weight=$this->input->post('weight');
        $rowid=$this->input->post('rowid');

 // {"createdOn":"12\/25\/2015 12:00:00 AM","rowid":"73089","lotid":"001","acID":"14","createdBy":"Test","imei":"192.168.3.77","noOfCrates":"10","weight":"7.0"}
        $weight = $weight+0;
        // log_message('error','--'.$weight);
        if((!is_numeric($weight)) || $weight<=0){
            // log_message('error','-- no num'.$weight);
            return array('Status' => 'Fail'); 
        }
        // log_message('error','-- after if'.$weight);

        $query="select  * from `lot_information` where Lot_Id='$lotid' and Ac_Id='$acID' and LotNet_Quantity is NULL limit 1";
        // log_message("error",$query);

        $rows=$this->db->query($query)->result_array();
        if(sizeof($rows)==0){
            // log_message('error','exists');
            return array("Status"=>"exists");
        }

        // $LotNet_Quantity = $this->input->post('LotNet_Quantity');
        // $NoOfCrates = $this->input->post('NoOfCrates');
        // $WeightOfCrates = $this->input->post('WeightOfCrates');
        // $RaceBvhCB = $this->input->post('Race');
        // $Cdp = $this->input->post('Cdp');
        // $RowId = $this->input->post('RowId');


        // crates
        $auction_center = $this->get_auction_center($acID);
        $WeightOfCrates = $auction_center['CratesWeight'];    

        $weight=$weight-($noOfCrates*$WeightOfCrates);
        // log_message("error",''.$weight);
        $update_array = array(
                            "Lot_Id" =>$lotid, "LotNet_Quantity" => $weight,"NoOfCrates" => $noOfCrates, "WeightOfCrates" => $WeightOfCrates
                         );
        // "RowId"=>$rowid,
        $flag = $this->update_farmer_agree_lots_details(array("Lot_Id"=>$lotid,"Ac_Id"=>$acID), $update_array);
        if ($flag) {
            // log_message("error",'True bock');
            return array('Status' => 'Success');
        } else {
            // log_message("error",'Fail');
            return array('Status' => 'Fail');
        }
    }

    function update_farmer_agree_lots_details($where_array, $params) {
        log_message("error",'update function');
        $this->db->where($where_array);
        $this->db->update('lot_information', $params);
        // log_message('error',$this->db->update_complie('lot_information', $params););
        // log_message('error','T'.$this->db->last_query());
        if($this->db->affected_rows()>0){
            // log_message("error",'True99');
            // log_message('error','T'.$this->db->last_query());
            return TRUE;
        }else{
             // log_message("error",'Fail 99');
             // log_message('error','F'.$this->db->last_query());

            return FALSE;
        }
    }

    function get_auction_center($AC_Id) {
        return $this->db->get_where('auction_centers', array('AC_Id' => $AC_Id))->row_array();
    }

}
