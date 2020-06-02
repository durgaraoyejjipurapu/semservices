<?php

//-------------------------------- Login Attempts  Block -----------------------------------
function login_attempt($userid) {
    $CI = & get_instance();
    if (is_numeric($userid) || is_string($userid)) {
        $data = array('buyerid' => $userid, 'attempt_date' => date('Y-m-d'));
        $CI->db->insert('login_attempts', $data);
    } 
}

function count_login_attempt($userid) {
    $CI = & get_instance();
    if (is_numeric($userid) || is_string($userid)) {
        $where_data = array('buyerid' => $userid, 'attempt_date' => date('Y-m-d'));
        $CI->db->select('IFNULL(count(*),0) records');
        $CI->db->from('login_attempts');
        $CI->db->group_by('buyerid,attempt_date');
        $CI->db->where($where_data);
        $rows = $CI->db->get()->row_array();
        return $rows['records'];
    }
}
 
//-------------------------------- Login Attempts  Block -----------------------------------
//==================================== OTP flooding Block ====================================
function otp_verify_attempt($mobile_no, $otp) {
    $CI = & get_instance();
    if (is_numeric($mobile_no) && is_numeric($otp)) {
        $data = array('mobile_no' => $mobile_no, 'otp' => $otp, 'attempt_date' => date('Y-m-d'));
        $CI->db->insert('otp_verify_attempts', $data);
    }
}

function count_otp_verification_attempts($mobile_no) {
    $CI = & get_instance();
    if (is_numeric($mobile_no)) {
        $where_data = array('mobile_no' => $mobile_no, 'attempt_date' => date('Y-m-d'));
        $CI->db->select('IFNULL(count(*),0) records');
        $CI->db->from('otp_verify_attempts');
        $CI->db->group_by('mobile_no,attempt_date');
        $CI->db->where($where_data);
        $rows = $CI->db->get()->row_array();
        return $rows['records'];
    }
} 
?>