<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Reeler extends REST_Controller {

    function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->helper('auth');
        $this->load->model('Reeler_Model', 'rm');
        /* cache control */
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');    
    }

    /**
     *      returns Reeler apk Version
     */
    public function reeler_app_version_get() {
        auth();
        $this->response($this->rm->get_reeler_apk_version());
    }

    public function buyer_details_post() {  
        auth(); 
        $this->response($this->rm->get_buyer_details());
    }

    public function auction_details_post() {
        auth();
        $this->response($this->rm->get_buyer_action_details());
    }

    public function auction_details_wrt_date_post(){
        auth();
            $this->response($this->rm->get_buyer_action_details_wrt_date());
    }

    
    public function lots_based_on_acid_bid_post(){
        auth();
        $this->response($this->rm->lots_based_on_acid_bid());
    }
    
    public function offered_bid_post() {
        auth();
        $this->response($this->rm->offered_bid());
    }

    public function get_auction_counts_post() {
        auth();
        $this->response($this->rm->get_auction_count());
    }

    public function sending_firebase_push_notification_id_post() {
        auth();
        $this->response($this->rm->firebase_push_notification_id());
    }

    public function auction_center_list_get() {
        auth();
        $this->response($this->rm->get_auctions_list());
    }

    public function server_current_time_get() {
        auth();
        date_default_timezone_set('Asia/Kolkata');
        $timestamp = time();
        $date_time = date("d-m-Y H:i:s", $timestamp);
        log_message('error','-------Server Current Time ----'.$date_time);
        $this->response(array("Date" => $date_time));
    }

    public function bidding_for_others_post() {
        auth(); 
        $this->response($this->rm->bidding_for_others());
    }

    public function send_otp_for_forgot_pwd_post() {
        auth();
        $this->response($this->rm->send_otp_for_forgot_pwd());
    }

    public function create_password_post() {
        auth();
        $this->response($this->rm->create_password());
    }

    public function checking_auction_participation_request_post() {
        auth();
        $this->response($this->rm->checking_auction_participation_request());
    }

    public function sending_auction_participation_request_post() {
        auth();
      $this->response($this->rm->sending_auction_participation_request());  
    }

    public function deleting_auction_participation_request_sent_by_reeler_post() {
        auth();
        $this->response($this->rm->deleting_auction_participation_request_sent_by_reeler());
    }
    
    public function get_all_firebase_notifications_from_server_post(){
        auth();
        $this->response($this->rm->get_all_firebase_notifications_from_server());
    }
    
    public function delete_firebase_notifications_msg_post(){
        auth();
        $this->response($this->rm->delete_firebase_notifications_msg());
    }
    
    public  function store_reeler_current_logged_in_auction_center_post(){
        $this->response($this->rm->store_reeler_current_logged_in_auction_center());
    }
    
    public function auction_start_time_post(){
        auth();
        $this->response($this->rm->auction_start_time());
    }

    public function auction_start_time1_post(){
        auth();
        $this->response($this->rm->auction_start_time1());
    }

    public function verify_otp_post(){
        auth();
        $this->response($this->rm->verify_otp());
    }

    public function logout_post(){
         auth();
         $this->response($this->rm->logout());
    }

    // public function generate_token_get(){
 
    //     $tokenData = array();
    //     $tokenData['id'] = 'dddd'.date('Y-m-d').'hahahahahaaa'; //TODO: Replace with data for token 
    //     header("Authorization:".AUTHORIZATION::generateToken($tokenData));
    // }

    // public function validate_get(){
 
    //     $request_headers=$this->input->request_headers();
    //     $token =$request_headers['Authorization'];
    //     try {
    //         $response=AUTHORIZATION::validateToken($token);
    //         if(!$response){
    //             echo json_encode(array("status"=>"invalid"));
    //         }else{
    //             echo json_encode(array("status"=>"success"));
    //         }
    //     } catch (Exception $e) {
    //         echo json_encode(array("status"=>"invalid"));
    //     }

    // }
}

?>