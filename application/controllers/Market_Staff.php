<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Market_Staff extends REST_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Market_Staff_Model', 'msm');
        $this->load->library("MCrypt",'','mcrypt'); 
        $this->load->helper('auth');

        /* cache control */
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }
    
    public function market_app_version_get() {
        auth();
        $this->response($this->msm->get_staff_apk_version());
    }
    
    public function staff_login_post(){
        auth();
        $this->response($this->msm->login_check());
    }
    
    
    public function finished_items_post(){
        auth();
        $this->response($this->msm->finished_items());
    }
    
    public function reeler_request_for_auction_participation_post(){
        auth();
        $this->response($this->msm->reeler_request_for_auction_participation());
    }
    
    public function  send_reelers_request_accept_or_reject_post(){
        auth();
        $this->response($this->msm->send_reelers_request_accept_or_reject());
    }
    
    public function send_farmer_acceptance_post(){
        auth();
        $this->response($this->msm->send_farmer_acceptance());
    }

}

?>