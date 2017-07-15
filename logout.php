<?php

/**
 * logout.php
 *
 * logs restaurants out of the database
 *
 * @author     Ronald R. Ferrucci
 * @copyright  2017 Ronald R. Ferrucci
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 */


//connect to wordpress database
require_once('db_file.php');

$con = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME) or die('Unable to Connect');

if($_SERVER['REQUEST_METHOD']=='POST'){

	$email = $_POST['email'];
  	//client wanted to know when restaurants were online, online is 1, offline is 0
	$sql = "UPDATE wp_restaurants SET online=0 WHERE email = ?";
	$stmt = $con->prepare($sql);
	$stmt-> bind_param('s', $email);
	$stmt-> execute();
	
	if ($stmt->affected_rows != 0){
	    echo "success";
	    }
	else
	    {
	    echo "failure";
	    }
	
	$stmt->close();
	$con->close();
	}
else{
    echo 'post error';
}
