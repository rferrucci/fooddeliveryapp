<?php

/**
 * restaurant-class.php
 *
 * restaurant class for client's food delivery app, where restaurants receive orders from her WordPress installation. Originally written as a plugin for
 WordPress, modified as a standalone script. Script takes email address associated with Woocommerce user profile "shop_managers," and the
 user can associate emails with the name of restaurants to be associated with orders for submission to said restaurant. passwords are
 randomly generated.
 
 There is minimal markup and css here. Just was more of an exercise is writing php objects, which I have for the restaurants.
 The restaurant object has methods for insertion, deletion, and updating of database tables, plus JQuery to make changes to 
 the html table.
 *
 * @author     Ronald R. Ferrucci
 * @copyright  2017 Ronald R. Ferrucci
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 */

class Restaurant{
 	var $email;
	var $id;
	var $restaurant;
	var $online;
	function __construct($email,$restaurant,$online=0, $id =null)
   	{
	 	$this->email = $email;
	       	$this->restaurant = $restaurant;
	       	$this->online= $online;
	       	$this->id = $id;
   	}
   	function delete() {
		//delete resturant from the database
		global $con;
		$sql = "DELETE FROM wp_restaurants WHERE id=?";
		$id = $this->id;
		$stmt = $con->prepare($sql);
		$stmt->bind_param('d',$id);
		$stmt->execute();	
		$restaurant = str_replace("\'", '&#8217;', $this->restaurant);
		if (!$stmt->execute()) echo 'deletion failed';
		else echo 'Restaurant ' . $restaurant  . ' deleted<br>';
	
		unset($this);
	 	$stmt->close();
	}
	function insert() {
		//insert new resturant from the database
		global $con;
		$this->password(12);
		$sql = "INSERT INTO wp_restaurants (email, password, online, restaurant)";
		$sql .= "VALUES (?, ?, 0, ?)";
		
		$stmt = $con->prepare($sql);
		$stmt->bind_param('sss',$this->email,$this->hash, $this->restaurant);
		$restaurant = str_replace("\'", '&#8217;', $this->restaurant);
		if (!$stmt->execute()) echo 'creation failed';
		else {
			$insert = 'Restaurant ' . $restaurant  . ' created in database with email: ' . $this->email . '.<br>';
			$insert .= 'Password will be sent to email.';
			echo $insert;
			//$msg = 'your password for the food delivery service is ' .$password .' . ';
			//$msg .= 'Use this email for logging into the app.';
			//mail($email,$subject,$msg);
		}
		$id = $stmt->insert_id;;
		$this->id = $id;
		$stmt->close();	
	}
	function update($email, $restaurant) {
		//update resturant information in the database
		global $con;
		$this->password(12);
		$sql = "UPDATE wp_restaurants ";
		$sql .= "SET email=?, password=?, restaurant=? WHERE id=?";
		$id = $this->id;
		$stmt = $con->prepare($sql);
		$stmt->bind_param('sssd',$email,$this->hash, $restaurant,$id);
		$restaurant = str_replace("\'", '&#8217;', $restaurant);
		if (!$stmt->execute()) echo 'update failed';
		else {
			$insert = 'Restaurant ' . $restaurant . ' updated in database with email: ' . $email . '.<br>';
			$insert .= 'New password will be sent to email.';
			echo $insert;
			//$msg = 'your password for the food delivery service is ' .$password .' . ';
			//$msg .= 'Use this email for logging into the app.';
			//mail($email,$subject,$msg);
		}
		$this->email=$email;
		$this->restaurant=$restaurant;
		$stmt->close();	
	}
	function password(
		#this function generates a random password of length from 8 to $max.
		$max,
		$keyspace = 'VnWkSCY75Fys!EL24fUoNguHabv1XPeqQ8pRcM3xz9irIjOGBDmwh@l6JZ0tTKdA'
		) {
		$password = '';
		$min = 8;
		//takes maximum length as input and generates a random length for the password with min length of 8.
		//$keyspace has been randomly shuffled in python
		$length = rand($min, $max +1);
		$max = mb_strlen($keyspace, '8bit') - 1;
		if ($max < 1) {
			throw new Exception('$keyspace must be at least two characters long');
			}
		for ($i = 0; $i < $length; ++$i) {
			$password .= $keyspace[mt_rand(0, $max)];
			}
		$this->password = $password;
		$this->hash = hash ( "sha256" , $password);
		}
}

if (isset($_GET)){
	$id= $_GET['id'];
	$query = "SELECT * from wp_restaurants WHERE id=?";
	$stmt = $con->prepare($query);
	$stmt-> bind_param('d',$id);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_array();
	$stmt->close();
	$selected=$row['email'];
	if ($_GET['action']=='edit') $button = 'edit';
	else if ($_GET['action']=='delete') $button = 'delete';
}
function get_restaurants( $per_page = 5, $page_number = 1 ) {
	//get list of restaurants from the database
	global $con;
	$sql = "SELECT * FROM wp_restaurants";
	$sql .= " LIMIT $per_page";
	$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
	$stmt = $con->prepare($sql);
	$stmt->execute();
	$results = $stmt->get_result();
	$restaurants = array();
	while ($res = $results->fetch_assoc()){
		$id=$res['ID'];
		$restaurant=new Restaurant($res['email'],$res['restaurant'],$res['online'],$res['ID']);
		$restaurants[$id] = $restaurant;		
		}
	return $restaurants;	
	}
$restaurants = get_restaurants();
// count number of restaurant objects;
$NRestaurants =count($restaurants);

?>
