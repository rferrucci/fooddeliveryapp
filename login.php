<?php
/*this code was used to login to an app I built that was connected to a clients WordPress installation, db.file.php connects to
a wordpress database. The app enabled the restaurant clients of her food delivery service to receive orders.
*/
include_once('db_file.php');

$con = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME) or die('Unable to Connect');

if($_SERVER['REQUEST_METHOD']=='POST'){
	//email and password are sent from the application using post
	$email = $_POST['email'];
	$password = $_POST['password'];
	//password is hashed in the database for security purposes
	$hash= hash ( "sha256" , $password);

	$sql = "SELECT * FROM wp_restaurants WHERE email =? AND password=?";
	$stmt = $con->prepare($sql);
	$stmt-> bind_param('ss', $email, $hash);
	$stmt-> execute();

	$result = $stmt->get_result();
	$stmt->close();
	if ($result->num_rows != 0){
		echo "success";
		//client wanted to know when restaurants were connected, 1 is online 0 is offline
		$sql = "UPDATE wp_restaurants SET online=1 WHERE email = ?";
		$stmt = $con->prepare($sql);
		$stmt-> bind_param('s', $email);
		$stmt-> execute();
		$stmt->close();
	  }
	else{
	    echo "failure";
	    }
	
	//$stmt->close();
	$con->close();
 }
 else{
echo 'post error';
}
