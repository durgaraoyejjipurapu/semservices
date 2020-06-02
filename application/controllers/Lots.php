<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Lots extends REST_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Lots_Model', 'lm');
        $this->load->helper('auth');

        /* cache control */
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }
    
    public function get_farmer_info_post(){
        // auth();
        $farmerid=$this->input->post('farmerid');
        if(!valid_farmerid($farmerid)){
            return json_encode(array("Status" => "Fail"));
        } 

        $query="SELECT St_type FROM `farmer_details` WHERE Farmer_Id='$farmerid' LIMIT 1";
        $row=$this->db->query($query)->row_array();
        if(isset($row['St_type']) && $row['St_type']=='OS'){
            // log_message("error","Os");
            $this->response($this->lm->farmer_marketing_search($farmerid,"OS") );                        
        }else{
            // log_message("error","AP");
            $this->response($this->lm->farmer_marketing_search($farmerid,"AP") );            
        }

    }
    
    function lots_add_post() {
        // auth();
        $this->response($this->lm->add_lots());
    }

    function update_lot_details_post(){
        // auth();
        $this->response($this->lm->update_lots_details());
    }
    function update_lot_details1_post(){
        // auth();
        $this->response($this->lm->update_lots_details1());
    }   
}