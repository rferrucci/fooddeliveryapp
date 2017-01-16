<?php

/**
 * update_order.php
 *
 * This script updates the order in the database to reflect change of status from the app user, either setting status to "recived," acknowledging 
receipt of the order, or "completed," indicated that the order has been filled. Also adds a note from the restaurant, if there is one.
 *
 * @author     Ronald R. Ferrucci
 * @copyright  2017 Ronald R. Ferrucci
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0

 */


include_once('db_file.php');
$con=mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);


if($_SERVER['REQUEST_METHOD']=='POST'){
	$order_ID = $_POST['id'];
	$note = $_POST['note'];
	$update = $_POST['update'];
 
	$sql = "UPDATE wp_order_status SET note=? , status=? WHERE order_id=?";
	$stmt = $con->prepare($sql);

	$stmt->bind_param('ssd',$note,$update,$order_ID);

	/* execute query */
  
	try {
		$stmt->execute();
		echo "Successfully Received";
 	}catch (Exception $e){
		echo "Could not register ";
	}

	$stmt->close();
	}
else{
	echo 'error';
}
$con->close();
