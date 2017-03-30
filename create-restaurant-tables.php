<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<style>
 form#restaurant-form fieldset {
     width: 350px;
     display: inline-block;
 }
form#restaurant-form input, select, button{
	float:right;
}
 fieldset label{
     margin-right: 10px;
     position: relative;
 }
 
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

include_once("getemails.php");
include_once("getRestaurants.php");


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



?>

<script>

/*$(document).ready(function () {
	 
	$.urlParam = function(name){
	    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
	    if (results==null){
	       return null;
	    }
	    else{
	       return results[1] || 0;
	    }
	}

  	$.getJSON('getemails.php', function (data) {
	   	var selected = $.urlParam('email');
	
	
	      	var users = data.emails.map(function (user) {
	      		if (selected != null){var email = '<option selected value =' + user.email  +'>' +  user.email + '</option>'; }
		        else if (user.disabled == 'yes') {var email = '<option disabled value =' + user.email  +'>' +  user.email + '</option>';}
      			else {var email = '<option value =' + user.email  +'>' +  user.email + '</option>';}
		        return email;
	});

      if (users.length) {
        var content = '<li>' + users.join('</li><li>') + '</li>';
        var list = $('<ul />').html(content);
        $("#email").append(users.join());
      }
    });

});*/

 
</script>

<script>
// Attach a submit handler to the form
$(document).ready(function(){
	$( "#restaurant-form" ).submit(function( event ) {
		// Stop form from submitting normally
		event.preventDefault();
		alert("testing");
		// Get some values from elements on the page:
		alert("again");
		// Send the data using post	
		data = { 
			restaurant: $("#restaurant").val(), 
			email: $("#email").val(), 
			submitChanges: $("#submitChanges").val(),
			};
		
		var href = window.location.href;
		var dir = href.substring(0, href.lastIndexOf('/')) + "/";
		var url =  dir + "restaurant-changes.php";
		alert(url); 		
		$.ajax(url, {
		    method: 'PUT',
		    contentType: 'application/json',
		    processData: false,
		    data: JSON.stringify(data)
		})
		.then(
		    function success(userInfo) {
		    	alert("success");
		        // userInfo will be a JavaScript object containing properties such as
		        // name, age, address, etc
		    });

	});
});
</script>

<div id="result"></div>

<h2>Restaurant Form</h2>
<form name="submit_restaurant_info" method="post" id="restaurant-form">
<fieldset>
<legend>Insert or update restaurant</legend>
<label for="restaurant">Restaurant: </label><input type="text" name="restaurant" id="restaurant" value="<?php echo $row['restaurant'] ?> "></input><br>
<label for"email">Email: <label><select name="email" id="email" >
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
<input type="hidden" name="ID" value = <?php echo $id ?> >
<br>
<?php
if ($button == 'edit') echo '<button id="submit" name="submitChanges" value="update">Update</button>';
else if ($button == 'delete') echo '<button id="submit" name="submitChanges" value="delete">Delete</button>';
else echo '<button id="submit" name="submitChanges" value="insert">New</button>';
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
	
	echo '<td><a href =' . $link . '?action=edit&id=' . $r->id . '&email=' . $r->email . '>Edit</a><br>';
	echo '<a href =' . $link . '?action=delete&id=' . $r->id . '&email=' . $r->email .'>Delete</a><br>';
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


<?php

/*if (isset($_POST)){
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
}*/
$con->close();


