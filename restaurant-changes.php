<?php
// process-restaurant.php
require_once('db_file.php');
$con = new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
if (mysqli_connect_errno($con))
{
   echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
$con->set_charset("utf8");

include_once("getRestaurants.php");
$errors         = array();      // array to hold validation errors
$data           = array();      // array to pass back data

// validate the variables ======================================================
    // if any of these variables don't exist, add an error to our $errors array

    if (empty($_POST['restaurant']))
        $errors['name'] = 'Name is required.';


// return a response ===========================================================

    // if there are any errors in our errors array, return a success boolean of false
    if ( ! empty($errors)) {

        // if there are items in our errors array, return those errors
        $data['success'] = false;
        $data['errors']  = $errors;
    } else {

        // if there are no errors process our form, then return a message

        // DO ALL YOUR FORM PROCESSING HERE
        // THIS CAN BE WHATEVER YOU WANT TO DO (LOGIN, SAVE, UPDATE, WHATEVER)

        // show a message of success and provide a true success variable
        $data['success'] = true;
        $data['message'] = 'Success!';
    }


if (isset($_POST)){
	if (isset($_POST['ID'])){
		$id = $_POST['ID'];
		}
	if ($_POST['submitChanges'] == 'delete'){
		$restaurants[$id]->delete();
		
	}
	else if ($_POST['submitChanges'] == 'update'){
		$restaurants[$id]->update($_POST['email'],$_POST['restaurant']);
		$email = trim($_POST['email']);
		$restaurant = $_POST['restaurant'];		
	}
	else if ($_POST['submitChanges'] == 'insert'){
		$res=new Restaurant($_POST['email'],$_POST['restaurant']);
		$res->insert();
		$restaurants[$res->id] = $res;
		$email = trim($_POST['email']);
		$restaurant = $_POST['restaurant'];
		
	}
}
$con->close();

    // return all our data to an AJAX call
    echo json_encode($data);
