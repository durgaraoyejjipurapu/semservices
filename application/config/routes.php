<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/


$route['default_controller'] = 'welcome';
$route['404_override'] = 'errors/page_missing';
$route['translate_uri_dashes'] = FALSE;


$route['API'] = 'Rest_Server';

//$route['routename/(:any)/(:any)']="Class/method/$1/$2";

//======================================== Reeler Services =======================================
//http://103.210.72.120/semservice1/api/ReleerVersion
//http://36.255.252.196/SemService/ReleerVersion
$route['ReleerVersion']="Reeler/reeler_app_version";


//http://103.210.72.120/semservice1/api/login/GetBuyerDetails
//http://36.255.252.196/SemService/BuyerDetails
$route['BuyerDetails']="Reeler/buyer_details";

//http://103.210.72.120/semservice1/api/login/GetAuctionDetails/
//http://36.255.252.196/SemService/GetAuctionDetails
$route['GetAuctionDetails']="Reeler/auction_details";

// http://103.210.72.120/semservice1/api/Auction/GetLotsByABId
//http://36.255.252.196/SemService/GetLotsByAcidBid
$route['GetLotsByAcidBid']="Reeler/lots_based_on_acid_bid";

//http://103.210.72.120/semservice1/api/Lots/OfferedBid
//http://36.255.252.196/SemService/OfferBid
// Note : This service not active.
$route['OfferBid']="Reeler/offered_bid";

//http://103.210.72.120/semservice1/api/login/GetAuctionsCount
//http://36.255.252.196/SemService/GetAuctionCount
$route['GetAuctionCount']='Reeler/get_auction_counts';

//http://103.210.72.120/semservice1/api/login/GetFireBase
//http://36.255.252.196/SemService/SendFireBasePushNoficationId
$route['SendFireBasePushNoficationId']="Reeler/sending_firebase_push_notification_id";

//http://103.210.72.120/semservice1/api/Auction/GetAuctionDtlsCenters
//http://36.255.252.196/SemService/auction_center_list
$route['auction_center_list']="Reeler/auction_center_list";
        
//http://103.210.72.120/semservice1/api/Auction/GetServerDate
//http://36.255.252.196/SemService/server_current_time
$route['server_current_time']="Reeler/server_current_time";

//http://103.210.72.120/semservice1/api/login/GetBuyerReg
//http://36.255.252.196/SemService/bidding_for_others
$route['buyer_registration']="Reeler/bidding_for_others";

//http://103.210.72.120/semservice1/api/login/GetOTP
//http://36.255.252.196/SemService/get_opt_for_forgot_pwd
$route['get_opt_for_forgot_pwd']="Reeler/send_otp_for_forgot_pwd";

//Both 12(For Create Password ) and 13  (For Change Password) 
//http://103.210.72.120/semservice1/api/login/UpdateBuyerPwd
//http://36.255.252.196/SemService/create_password
$route['create_password']="Reeler/create_password";

//http://36.255.252.196/SemService/change_password
$route['change_password']="Reeler/create_password";

//http://103.210.72.120/semservice1/api/AuctionRequest/GetYesAuctionRequest 
//http://36.255.252.196/SemService/checking_auction_participation_request
$route['checking_auction_participation_request']="Reeler/checking_auction_participation_request";

//http://103.210.72.120/semservice1/api/AuctionRequest/InsertAuctionRequest
//http://36.255.252.196/SemService/sending_auction_participation_request
$route['sending_auction_participation_request']="Reeler/sending_auction_participation_request";

//http://103.210.72.120/semservice1/api/AuctionRequest/UpdateAuctionRequest
//http://36.255.252.196/SemService/deleting_auction_participation_request_sent_by_reeler
$route['deleting_auction_participation_request_sent_by_reeler']="Reeler/deleting_auction_participation_request_sent_by_reeler";

//http://103.210.72.120/semservice1/api/login/GetFireBaseMsgs
//http://36.255.252.196/SemService/get_all_firebase_notifications_from_server
$route['get_all_firebase_notifications_from_server']="Reeler/get_all_firebase_notifications_from_server";

//http://103.210.72.120/semservice1/api/login/DeleteFireBaseMsgs
//http://36.255.252.196/SemService/delete_firebase_notifications_msg
$route['delete_firebase_notifications_msg']="Reeler/delete_firebase_notifications_msg";

//http://103.210.72.120/semservice1/api/Notification/insertBuyersDN
//http://36.255.252.196/SemService/store_reeler_current_logged_in_auction_center
$route['store_reeler_current_logged_in_auction_center']="Reeler/store_reeler_current_logged_in_auction_center";

//http://103.210.72.120/semservice1/api/Auction/GetAuctionStartTime
//http://36.255.252.196/SemService/get_auction_start_time
$route['get_auction_start_time']="Reeler/auction_start_time";
$route['get_auction_start_time1']="Reeler/auction_start_time1";


 
$route['GetAuctionDetailsWrtDate']="Reeler/auction_details_wrt_date";

//======================================== Reeler Services =======================================



// ================================== Market Officer Services ==========================================
	//http://103.210.72.120/semservice/api/StaffVersion
$route['market_app_version']="Market_Staff/market_app_version";

//http://103.210.72.120/semservice/api/Marketlogin/GetMarketDetails
//{"Username":"testadmin","password":"password@123"}
$route['staff_login']="Market_Staff/staff_login"; 

//http://103.210.72.120/semservice/api/FinishedLots/GetFinishedLots
//{"AId":"14"}
$route['finished_items']="Market_Staff/finished_items";

//http://103.210.72.120/semservice/api/AuctionRequest/GetAuctionRequest
//{"ACenter":"14"}
$route['reeler_request_for_auction_participation']="Market_Staff/reeler_request_for_auction_participation";

//http://103.210.72.120/semservice/api/AuctionRequest/UpdateAuctionRequest
//{"BuyerId":"bapi@gmail.com","ACenter":"14","ReqStatus":"No","Created_By":"testadmin","Imei":"584456985632455"}
$route['send_reelers_request_accept_or_reject']="Market_Staff/send_reelers_request_accept_or_reject";


//http://103.210.72.120/semservice/api/FinishedLots/FarmerAcceptence
//{"Acceptence":"Yes","LotId":"2711201814001","BuyerId":"BUY12345678","AuctionId":"1","FAadhar_No":"584456985632","userId":"testadmin","Lot_Image":""}
$route['send_farmer_acceptance']="Market_Staff/send_farmer_acceptance";


// ================================== Market Officer Services ==========================================


// ==================================== To Add Lots via weight meachine===================================
$route['get_farmer_info']="Lots/get_farmer_info";
$route['add_lots']="Lots/lots_add";
$route['update_lot_details']="Lots/update_lot_details";
$route['update_lot_details1']="Lots/update_lot_details1";
// ==================================== End Add Lots ===================================

$route['verify_otp']="Reeler/verify_otp";

// $route['generate']="Reeler/generate_token";

// $route['validate']="Reeler/validate";

$route['logout'] = 'Reeler/logout';