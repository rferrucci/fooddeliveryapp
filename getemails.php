<?php
//here we are getting a list of emails associated with shop managers, a particular user profile from woocommerce.
require_once('db_file.php');

$query = "SELECT ID, user_email 
	FROM wp_users
	JOIN wp_usermeta on wp_users.ID=wp_usermeta.user_id 
	WHERE wp_usermeta.meta_key='wp_capabilities' 
	AND wp_usermeta.meta_value LIKE '%shop_manager%'";
#$shop_managers = $wpdb->get_results($query);
$stmt = $con->prepare($query);
$stmt->execute();		
$shop_managers = $stmt->get_result();		
//we also want to get emails that are already associated with restaurants
$query = "SELECT email FROM wp_restaurants";
#$results = $wpdb->get_results($query);
$stmt = $con->prepare($query);
$stmt->execute();
$results = $stmt->get_result();
$used = array();
foreach ($results as $r){
	array_push($used, $r->email);
}

$emails = array();

foreach ($shop_managers as $s){
	$email = $s['user_email'];
	$id = $s['ID'];
	if (in_array($email, $used)){ $s['disabled'] = 'yes'; }
	else { $s['disabled'] = 'no'; }
	
	#$user = array('id'=>$id, 'email'=>$email, 'selected'=>$selected)
	array_push($emails, array('id'=>$id, 'email'=>$email, 'disabled'=>$disabled));
	}

#echo json_encode(array("emails"=>$emails));
#$emails = json_encode($emails);

#echo $emails;

