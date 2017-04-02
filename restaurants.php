<html>
<?php
/**
 * restaurants.php
 *
 * for client's food delivery app, where restaurants receive orders from her WordPress installation. Originally written as a plugin for
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
 ?>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"> <!-- load bootstrap via CDN -->

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<style>
 
 table#restaurantTable{
	width: 600px;
 }
 
  table#restaurantTable th{
	text-align:left;
 }
 table#restaurantTable td{
	padding: 5px;
	margin: 5px 25px;
 }
</style>
<?php 

$link = "http://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
require_once('db_file.php');
$con = new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
if (mysqli_connect_errno($con))
{
   echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
$con->set_charset("utf8");

include_once("restaurant-class");


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
?>

<body>

<div class="col-sm-6 col-sm-offset-3">

<h2>Restaurant Form</h2>
<form name="submit_restaurant_info" id="restaurantForm">
<fieldset>

<legend>Insert or update restaurant</legend>
    <div id="restaurant-group" class="form-group">

<label for="restaurant">Restaurant: </label><input type="text" name="restaurant" id="restaurant" class="form-control" value="<?php echo $row['restaurant'] ?> "></input>
 <span class="help-block"></span>
    </div>

<br>
<label for"email">Email: <label><select class="form-control" name="email" id="email" >
<option placeholder value="">Select Email Address</option>

<?php

foreach ($shop_managers as $email){
	if ($_GET['email'] == $email->user_email)
		echo '<option selected value =' . $email->user_email . '>' . $email->user_email . '</option>';
	else if ($email->disabled == 'yes') //if already associated with a restaurant, email will be unable to be chosen
		echo '<option disabled value =' . $email->user_email . '>' . $email->user_email . '</option>';
	else // otherwise, all is good
		echo '<option value =' . $email->user_email . '>' . $email->user_email . '</option>';		
}
?>


</select><br>
<input type="hidden" name="ID" name="ID" value = <?php echo $id ?> >
<br>
<?php
if ($button == 'edit') echo '<button class="form-control" id="submit" name="submitChanges" value="update">Update</button>';
else if ($button == 'delete') echo '<button id="submit" class="form-control" name="submitChanges" value="delete">Delete</button>';
else echo '<button id="submit" class="form-control" name="submitChanges" value="insert">New</button>';
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
<table id="restaurantTable">
<thead><tr><th>Edit&#47;Delete</th><th>Restaurant</th><th>Email</th><th>Online</th></tr></thead>
<tbody>
<?php
foreach ($restaurants as $r){
	echo '<tr class="rid" id="rid-'. $r->id .'">';
	echo '<td><a href =' . $link . '?action=edit&id=' . $r->id . '>Edit</a><br>';
	echo '<a href =' . $link . '?action=delete&id=' . $r->id . '>Delete</a><br>';
	echo '</td>';
	echo '<td class="restaurant">' . str_replace("\'", '&#8217;', $r->restaurant) . '</td>';
	echo '<td class="email">' . $r->email . '</td>';
	echo '<td>';
	if ($r->online==1) {echo '<img width="16" alt="online" title="online" height="16" src="images/green-circle.png">';}
	else {echo '<img width="16" height="16"  alt="offline" title="offline" src="images/transparent-red.png">';}
	echo '</td>';
	echo '</tr>';
}
?>
</tbody>
</table>

</div>
</body>
	
<?php	
if (isset($_POST)){
	if (isset($_POST['ID'])){
		$rid = $_POST['ID'];
		}
	if ($_POST['submitChanges'] == 'delete'){
		$restaurants[$id]->delete();
		?><script>
		var rid = "rid-" + <?php echo $rid ?>;
		$(document).ready(function(){
		        $("#" + rid).remove();
		});	
		</script><?php
	}
	else if ($_POST['submitChanges'] == 'update'){
		$restaurants[$id]->update($_POST['email'],$_POST['restaurant']);
		$email = trim($_POST['email']);
		$restaurant = $_POST['restaurant'];
		?><script>
		var rid = "rid-" + <?php echo $rid; ?>;
		var email = "<?php echo $email ?>";
		var restaurant = "<?php echo $restaurant ?>";
		$(document).ready(function(){
			$("#" + rid ).find(".restaurant").html(restaurant);
			$("#" + rid ).find(".email").html(email);
		});
		
		</script><?php
	}
	else if ($_POST['submitChanges'] == 'insert'){
		$res=new Restaurant($_POST['email'],$_POST['restaurant']);
		$res->insert();
		$restaurants[$res->id] = $res;
		$email = trim($_POST['email']);
		$restaurant = $_POST['restaurant'];
		?><script>
		var link = "<?php echo $link; ?>";
		var rid = "rid-" + <?php echo $rid; ?>;
		var email = "<?php echo $email; ?>";
		var restaurant = "<?php echo $restaurant ?>";
		$(document).ready(function(){
			var new_row ='<tr id="rid-' + rid + '">';
			new_row +='<td><a href ="' + link + '?action=edit&id=' + rid + '">Edit</a><br>';
			new_row += '<a href ="' + link + '?action=delete&id=' + rid + '">Delete</a>';
			new_row += '<td class="restaurant">' + restaurant + '</td>';
			new_row += '<td class="email">' + email +'</td>';
			new_row += '<td class="online"><img width="16" height="16"  alt="offline" title="offline"'; 
			new_row += 'src="images/transparent-red.png"</td></tr>';
			$('#restaurantTable tr:last').after(new_row);
			//$("#" + rid).(".restaurant").html(restaurant);
			//$("#" + rid).(".email").html(email);
		});
		</script><?php
	}
}


