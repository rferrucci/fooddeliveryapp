<?php
/**
 * restaurants.php
 *
 * for client's food delivery app, where restaurants receive orders from her WordPress installation. Originally written as a plugin for
 WordPress, modified as a standalone script. Script takes email address associated with Woocommerce user profile "shop_managers," and the
 user can associate emails with the name of restaurants to be associated with orders for submission to said restaurant. passwords are
 randomly generated.
 *
 * @author     Ronald R. Ferrucci
 * @copyright  2017 Ronald R. Ferrucci
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 */

?>

<style>

 fieldset#restaurantForm {
     min-width: 100px;
     display: inline-block;
 }



 fieldset label{
     margin-right: 10px;
     position: relative;
 }

</style>


<?php 

require_once('db_file.php');

$con = new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);

if (mysqli_connect_errno($con))
{
   echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$con->set_charset("utf8");

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
		if (!$stmt->execute()) echo 'deletion failed';
		else echo 'Restaurant ' . $this->restaurant . ' deleted<br>';
	 	$stmt->close();
	 	$link ='http://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]';
		wp_redirect( $url );
	}
	function insert() {
		//insert new resturant from the database
		global $con;
		$this->password(12);

		$sql = "INSERT INTO wp_restaurants (email, password, online, restaurant)";
		$sql .= "VALUES (?, ?, 0, ?)";
		
		$stmt = $con->prepare($sql);
		$stmt->bind_param('sss',$this->email,$this->hash, $this->restaurant);
		
		if (!$stmt->execute()) echo 'creation failed';
		else {
			$insert = 'Restaurant ' . $this->restaurant . ' created in database with email:' . $this->email . '.<br>';
			$insert .= 'Password will be sent to email.';
			echo $insert;
			//$msg = 'your password for the food delivery service is ' .$password .' . ';
			//$msg .= 'Use this email for logging into the app.';
			//mail($email,$subject,$msg);
		}
		$stmt->close();	
		$link ='http://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]';
		wp_redirect( $url );
	}
	function update() {
		//update resturant information in the database
		global $con;
		$this->password(12);
		$sql = "UPDATE wp_restaurants ";
		$sql .= "SET email=?, password=?, restaurant=? WHERE id=?";
		$id = $this->id;
		$stmt = $con->prepare($sql);

		$stmt->bind_param('sssd',$this->email,$this->hash, $this->restaurant,$id);
		if (!$stmt->execute()) echo 'update failed';
		else {
			$insert = 'Restaurant ' . $this->restaurant . ' updated in database with email:' . $this->email . '.<br>';
			$insert .= 'New password will be sent to email.';
			echo $insert;
			//$msg = 'your password for the food delivery service is ' .$password .' . ';
			//$msg .= 'Use this email for logging into the app.';
			//mail($email,$subject,$msg);
		}
		$stmt->close();	
		$link ='http://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]';
		wp_redirect( $url );	
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


//here we are getting a list of emails associated with shop managers, a particular user profile from woocommerce.		
$query = "SELECT user_email 
	FROM wp_users
	JOIN wp_usermeta on wp_users.ID=wp_usermeta.user_id 
	WHERE wp_usermeta.meta_key='wp_capabilities' 
	AND wp_usermeta.meta_value LIKE '%shop_manager%'";

$shop_managers = $wpdb->get_results($query);
		
//we also want to get emails that are already associated with restaurants
$query = "SELECT email FROM wp_restaurants";

$used = $wpdb->get_results($query);
$emails = array();
		
foreach ($used as $e){
	array_push($emails, $e->email);


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

if (isset($_POST)){
	if (isset($_POST['ID'])) $id = $_POST['ID'];
	if ($_POST['submitChanges'] == 'delete') $restaurants[$id]->delete();
	else if ($_POST['submitChanges'] == 'update') $restaurants[$id]->update();
	else if ($_POST['submitChanges'] == 'insert'){
		$res=new Restaurant($_POST['email'],$_POST['restaurant']);
		$res->insert();
	}
}

?>
<h2>Restaurant Form</h2>

<form action="#v_hash" method="post" name="submit_restaurant_info" id="restaurant-form">
<fieldset>
<legend>Insert or update restaurant</legend>
<label for="restaurant">Restaurant: </label><input type="text" name="restaurant" id="restaurant" value=" <?php echo $row['restaurant'] ?> "></input><br>
<label for"email">Email: <label>
<select name="email" id="email" ><option placeholder value="">Select Email Address</option>
<?php

foreach ($shop_managers as $email){
	if ($email->user_email == $selected) //if editing, email will already be selected
		echo '<option selected value =' . $email->user_email . '>' . $email->user_email . '</option>';
	else if (in_array($email->user_email, $emails)) //if already associated with a restaurant, email will be unable to be chosen
		echo '<option disabled value =' . $email->user_email . '>' . $email->user_email . '</option>';
	else // otherwise, all is good
		echo '<option value =' . $email->user_email . '>' . $email->user_email . '</option>';		
}

?>
</select><br>
<input type="hidden" name="ID" value = <?php echo $id ?> >

<?php

if ($button == 'edit') echo '<button id="submitChanges" name="submitChanges" value="update">Update</button>';
else if ($button == 'delete') echo '<button id="submitChanges" name="submitChanges" value="delete">Delete</button>';
else echo '<button id="submitChanges" name="submitChanges" value="insert">New</button>';
echo '<br>';
?>

<b>Note</b>: password will be randomly generated and emailed to the client
</fieldset>

</form>

<?php
echo '<h2>Food Delivery Restaurants</h2><p>';
if ($NRestaurants != 0){echo $NRestaurants . ' restaurants are available<br>'; }
else {echo 'No restaurants avaliable<br>'; }
echo '</p>';

?>
<table>
<thead><tr><th>Edit&#47;Delete</th><th>Restaurant</th><th>Email</th><th>Online</th></tr></thead>
<tbody>
<?php
$link = "http://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";

foreach ($restaurants as $r){
	echo '<tr>';
	echo '<td><a href =' . $link . '?action=edit&id=' . $r->id . '>Edit</a><br>';
	echo '<a href =' . $link . '?action=delete&id=' . $r->id . '>Delete</a><br>';
	echo '</td>';
	echo '<td>' . $r->restaurant . '</td>';
	echo '<td>' . $r->email . '</td>';
	echo '<td>';
	if ($r->online==1) {echo '<img width="16" alt="online" title="online" height="16" src="images/green-circle.png">';}
	else {echo '<img width="16" height="16"  alt="offline" title="offline" src="images/transparent-red.png">';}
	echo '</td>';
	echo '</tr>';
}
?>
</tbody>
</table>

<?php

				
