<!DOCTYPE html>
<html lang="en">
<head>
<title>Restaurant Forme</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<link href="https://fonts.googleapis.com/css?family=Bellefair|Fresca" rel="stylesheet">
</head>

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>

<style type="text/css">  
.center-block {  
    width:250px;  
    padding:10px;  
}  

header h1 {
	font-family: Fresca;
	text-align:center;
}

header h2 {
	font-family: Bellefair;
	text-align:center;
}

.addons{
	font-size: 0.75em;
}

 form#order-form fieldset {
     width: 350px;
     display: inline-block;
 }

form#order-form input, select, button, textarea{
	float:right;
}


 fieldset label{
     margin-right: 10px;
     position: relative;
 }
 
 table#orderTable{
	width: 900px;
 }
 
  table#orderTable th{
	text-align:left;
 }

 table#orderTable td{
	padding: 5px;
	margin: 5px 25px;
	vertical-align: top;
 }

</style>

<?php
/**
 * WP_orders.php
 *
 * for client's food delivery app, where restaurants receive orders from her WordPress installation.
When client associates a restaurant with an order, order status is set as "submitted" in the order_status table (previously does not exist).
When the app downloads the data and this script is accessed, the status is changed to "sent" meaning that it has been sent to the app.
Restaurants then will intention that they received the order (which will be changed in the database in the update_order script to "received")
and finally changed to completed when it has been fullfilled. At this point, it is no longer received on the restaurant's end but still
listed on the backend until client delivery service closes the order. This code is from the plugin to WordPress but adapted as standalone code. Here, they associate the restaurant with an order.
 *
 * @author     Ronald R. Ferrucci
 * @copyright  2017 Ronald R. Ferrucci
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 */
 
//connect to WordPress database
require_once('db_file.php');
$con = new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
if (mysqli_connect_errno($con))
{
   echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
$con->set_charset("utf8");
function getAdditionalOrderInfo($product_id,$item_id,$add1){
	// Will look for other information regarding the order, $add1 is how it is found in wp_postmeta (addons, product attributes, etc)
	// $add2 is where it is found in the order
	global $con;
	$order_items = "";
        $query="SELECT meta_value FROM wp_postmeta WHERE post_id=?
		AND meta_key='" . $add1 . "'";
	
	$stmt = $con->prepare($query);
  
	$stmt->bind_param("d",$product_id);
	$stmt->execute();
	$results = $stmt->get_result();
	$stmt->close();
	$atts = $results->fetch_assoc();	
    
        $array = unserialize($atts['meta_value']);
        
        if (is_array($array) || is_object($array))
            {
            foreach ($array as $a){
            //for ($i=0; $i< sizeof($array); $i++){
            	
            	//$addon = $array[$i]['name'];
		$addon = $a['name'];
		if ($addon=='pa_refrestaurant') continue;
		
                $query="SELECT meta_value FROM wp_woocommerce_order_itemmeta WHERE order_item_id=$item_id
    	        AND meta_key LIKE '%$addon%'";
                
                $results = mysqli_query($con,$query);
                
                //$add = mysqli_fetch_array($results);
                //$order_items .= '*** ' . $addon . '-' . $add['meta_value'] . '<br>';
		$order_items .= '<span class="addons"><i>' . $addon . '</i>: ';
		while($add = $results->fetch_assoc()){
		    $order_items .=  $add['meta_value'] . ', ';
		    }
	    	$order_items = trim($order_items, " ,");	
            	$order_items .= '</span><br>';
                }
            }
            return $order_items;
	}
	
function getOrderItems($order_id){
	//receives order information for the restaurant
	global $con;
	
	//receive order items from woocommerce associated with the order id for this order
	$query = "SELECT order_item_id, order_item_name FROM wp_woocommerce_order_items
	WHERE order_id=?";
	
	$stmt = $con->prepare($query);	
		
 	/* bind parameters for markers */
	$stmt->bind_param("d", $order_id);
	/* execute query */
	$stmt->execute();
	/* bind result variables */
	$items = $stmt->get_result();
	
	//$items= mysqli_query($con,$query);
	$order_items = '';
	while($item = $items->fetch_assoc()) {
	
		//receive information for each order item
		$item_id=$item['order_item_id'];
		$item_name=$item['order_item_name'];
				
		//ignore taxes and fees
	        if ($item_name=='US-LA-TAX-1' || $item_name=='Delivery Fee' || $item_name=='Credit Card Processing Fee') continue;	
		// quantity, or number of each item
		$query = "SELECT meta_value FROM wp_woocommerce_order_itemmeta
			WHERE order_item_id=?
			AND meta_key='_qty'
			";		
		
		$stmt = $con->prepare($query);
		$stmt->bind_param("d", $item_id);
		$stmt->execute();
		$stmt->bind_result($qty);
		$stmt->fetch();
		$stmt->close();
		//$qty = mysqli_query($con,$query);	
		//$qty = mysqli_fetch_assoc($qty);
		$order_items .= $qty . ' ' . $item_name . "<br />";	
        	
        	//get product id
	        $query = "SELECT meta_value FROM wp_woocommerce_order_itemmeta
	            WHERE order_item_id=? 
	            AND meta_key='_product_id'
	            ";    	
		$stmt = $con->prepare($query);
		$stmt->bind_param("d", $item_id);
		$stmt->execute();
		$stmt->bind_result($product_id);
		$stmt->fetch();
		$stmt->close();
		        
        	if (is_null($product_id)==1) continue;
		//some order items have lists of ingredients, this part fetches them        
	        $query="SELECT meta_value FROM wp_woocommerce_order_itemmeta WHERE order_item_id=?
	    	        AND meta_key LIKE '%Ingredients%'";
	        
        	$stmt = $con->prepare($query);
		$stmt->bind_param("d", $item_id);
		$stmt->execute();
		$results = $stmt->get_result();
		$stmt->close();
	        $results = mysqli_query($con,$query);
	        
	        if ($results->num_rows!=0){
		   	$order_items .= '<span class="addons"><i>Ingredients</i>: ' ;
		        while($row = $results->fetch_assoc()){
		            // ($results=null) continue;
		            //global $row;
		            $order_items .= $row[$i]['meta_value'] . ', ';    
	            		}
	            	$order_items = trim($order_items,',');
	            	$order_items .= '</span><br>';
	            	}
	            
		//here, we obtain product attributes: spice level, type (i.e., chicken, beef, tofu), etc.
		$order_items .= getAdditionalOrderInfo($product_id,$item_id,'_product_attributes');
		
	        //get addons, fries, sour cream, etc.    
		$order_items .= getAdditionalOrderInfo($product_id,$item_id,'_product_addons');
		}
	        $query="SELECT requests FROM wp_order_status WHERE order_id=$order_id";
	        $results = mysqli_query($con,$query);
	        $requests = mysqli_fetch_assoc($results);
	
		if ($requests['requests'] != ""){
		        $order_items .= '<span class="addons"><i>SPECIAL REQUESTS</i>: ' . $requests['requests'];   
			$order_items .= '</span>';
		}
	        return $order_items;
	}
	
function getCustomer($order_id){
	//receives customer information for restaurant, including address and phone number
	global $con;
	$query = "SELECT meta_value FROM wp_postmeta WHERE post_id=$order_id AND meta_key='_billing_last_name'";
	$result= mysqli_query($con,$query);
	$row = mysqli_fetch_assoc($result);
	$lastname = $row['meta_value'];
	
	$query = "SELECT meta_value FROM wp_postmeta WHERE post_id=$order_id AND meta_key='_billing_first_name'";
	$result= mysqli_query($con,$query);
	$row = mysqli_fetch_assoc($result);
	$firstname = $row['meta_value'];
	
	$query = "SELECT meta_value FROM wp_postmeta WHERE post_id=$order_id AND meta_key='_billing_address_1'";
	$result= mysqli_query($con,$query);
	$row = mysqli_fetch_assoc($result);
	$add1 = $row['meta_value'];
	
	$query = "SELECT meta_value FROM wp_postmeta WHERE post_id=$order_id AND meta_key='_billing_address_2'";
	$result= mysqli_query($con,$query);
	$row = mysqli_fetch_assoc($result);
	$add2 = $row['meta_value'];
	
	$query = "SELECT meta_value FROM wp_postmeta WHERE post_id=$order_id AND meta_key='_billing_phone'";
	$result= mysqli_query($con,$query);
	$row = mysqli_fetch_assoc($result);
	$phone = $row['meta_value'];
	
	if ($add2 == "") $add = $add1;
	else $add= $add1 . "<br>" . $add2;
	
	return $firstname . " " . $lastname . "<br>" . $add . "<br>" . $phone;
}

function getRestaurant($id){
	//get restaurant associated with the order	
	global $con;			
	$query = "SELECT restaurant_id FROM wp_order_status WHERE order_id=?";
	$stmt = $con->prepare($query);
	$stmt->bind_param('d',$id);
	$stmt->execute();
	$stmt->bind_result($rid);
	$stmt->fetch();
	$stmt->close();	

	$query = "SELECT restaurant FROM wp_restaurants WHERE id=?";
	$stmt = $con->prepare($query);
	$stmt->bind_param('d',$rid);
	$stmt->execute();
	$stmt->bind_result($restaurant);
	$stmt->fetch();
	$stmt->close();	
		
	return $restaurant;
	
}

function getNote($order_id){
	// this function just receives special instructions from the delivery service for the restaurant
	global $con;
	
	$query = "SELECT note FROM wp_order_status WHERE order_id=$order_id";
	$result= mysqli_query($con,$query);
	$row = mysqli_fetch_assoc($result);
	return $row['note'];
	}


/**
 * Delete an order.
 *
 * @param int $id order ID
 */
 
function delete_order( $id ) {
	global $con;

	$sql = "DELETE from wp_order_status WHERE order_id = ?";
	$stmt = $con->prepare($sql);
	$stmt-> bind_param('d', $id);
	
	if ($stmt->execute()) echo "order " . $id . " deleted";
	$stmt->close();
	}

/**
 * Close a customer order.
 *
 * @param int $id order ID
 */
function close_order( $id ) {
	global $con;
	$sql = 'SELECT * FROM wp_order_status WHERE order_id=?';
	$stmt = $con->prepare($sql);
	$stmt->bind_param('d',$id ); 
	$stmt->execute();
	/* store result */
	$stmt->store_result();
	$row_count = $stmt->num_rows;
	$stmt->close();

	/* close statement */
	if ($row_count == 0){
		$query = "INSERT INTO wp_order_status (status, order_id) VALUES ('closed',?)";
		
		$stmt = $con->prepare($query);

		$stmt->bind_param('d',$id);
		if ($stmt->execute()) echo "order " . $id . " closed";
		}
	else {
		$query = "UPDATE wp_order_status SET status='closed' 
			WHERE order_id=?)";
		$query = "UPDATE wp_order_status SET status='closed' WHERE order_id=?";
		$stmt = $con->prepare($query);

		$stmt->bind_param('d',$id);
		if ($stmt->execute()) echo "order " . $id . " closed";
		}
	$stmt->close();
	}

	
/**
 * Returns the count of records in the database.
 *
 */
function record_count() {
	global $con;
	$sql = "SELECT COUNT(*) FROM wp_posts p
	LEFT JOIN wp_order_status os ON(p.ID = os.order_id)
	WHERE p.post_type = 'shop_order'
	AND os.order_id is null
	OR (os.order_id is not null 
	AND os.status <> 'completed')";
	
	$stmt = $con->prepare($sql);

	$stmt->execute();
	    /* store result */
    	$stmt->store_result();

    	$row_count = $stmt->num_rows;

    	/* close statement */
    	$stmt->close();
    	return $row_count;
	}

?>

<body>

<header>

<h1>Restaurant Delivery Service</h1>
<h2>Interface for food delivery app</h2>
</header>

<nav class="navbar navbar-inverse">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="#">Food Delivery Service</a>
    </div>
    <ul class="nav navbar-nav">
      <li class="active"><a href="#">Home</a></li>
      <li><a href="create-restaurant-tables.php">Restaurant Form</a></li>
      <li><a href="order-table.php">Delivery Order Form</a></li>
    </ul>
  </div>
</nav>

<div class="jumbotron text-center">

<h1>Order Form</h1>
<p>Use this form to submit orders to restaurants</p>
</div>

<div class="container">

<h2>Order Status</h2>

<?php

if (isset ($_GET['id']) && $_GET['action']=='confirm') 
	$id = $_GET['id'];
if (isset ($_GET['id']) && $_GET['action']=='close' )
	close_order( $_GET['id'] );
	
if ( isset( $_POST["submit_form"] ) && $_POST["restaurant"] != "") {
	//this form submits restaurant information to the wp_order_status table
	//$order = $wpdb->get_results($wpdb->prepare("SELECT * from wp_order_status WHERE ID=%s", $id));
	
	global $con;
    	$table = "wp_order_status";
	//$table = "wp_restaurants";
	$id = $_POST["order_id"];
    	$requests = $_POST["requests"];
	$restaurant_id= $_POST["restaurant"];
	
	$query = 'SELECT COUNT(*) FROM wp_order_status
	        WHERE order_id=?';
	$stmt = $con-> prepare($query);
	$stmt->bind_param('d',$id);
	$stmt->execute(); 
	$stmt->store_result();
	$query = 'SELECT * FROM wp_order_status
	        WHERE order_id=?';
	$stmt = $con-> prepare($query);
	$stmt->bind_param('d',$id);
	$stmt->execute(); 
	$stmt->store_result();
	$row_count = $stmt->num_rows;
	$stmt->close();
	if ($row_count!=0){
	    	$query="UPDATE wp_order_status
	    		SET requests=?, restaurant_id=?, status='submitted'
	    		WHERE order_id=?"; 
 	
	    	$stmt = $con->prepare($query);
	    	$stmt->bind_param('sdd',$requests,$restaurant_id, $id);
	     	$stmt->execute();
	    	if ($stmt->errno) {
		echo "FAILURE!!! " . $stmt->error;
		}
		else echo "Order #" . $id . " updated<br><br>" ;
		$stmt->close();
	}
	else{
		$query = "INSERT INTO wp_order_status (requests, restaurant_id, status, order_id) VALUES (?,?,'submitted',?)";
		$stmt = $con->prepare($query);
	    	$stmt->bind_param('sdd',$requests,$restaurant_id, $id);
	       	
	        $stmt->execute;
	    	if ($stmt->errno) {
		echo "FAILURE!!! " . $stmt->error;
		}
		else echo "Order #" . $id . " updated<br><br>" ;
		$stmt->close();
	}
	
	$url = site_url(). "/wp-admin/admin.php?page=add-restaurants-database";
    
}
// if the form is submitted but the name is empty
if ( isset( $_POST["submit_form"] ) && $_POST["restaurant"] == "") 
    $html .= "<p>You need to fill the required fields.</p>";
echo $html;



?>
  
<form action="#v_hash" method="post" name="submit_order_info" id="order-form">
<fieldset>
<legend>Submit or Update order</legend>
	<p><label for="order_id">Order Number: </label>
	<input type="text" name="order_id" required id="order_id" value="<?php echo $id; ?>" readonly></p>

	<p><label for "restaurant">Restaurant: <label><select name="restaurant" form="order-form"><option required value="">Select Restaurant</option>


<?php

$query = "SELECT restaurant, ID FROM wp_restaurants";
$stmt = $con->prepare($query);
$stmt-> execute();
$restaurants = $stmt->get_result();
$stmt->close();

$query = "SELECT restaurant_id FROM wp_order_status WHERE order_id=?";
$stmt=$con->prepare($query);
$stmt->bind_param('d',$id);
$stmt->execute();
$stmt->bind_result($selected);
$stmt->fetch();
$stmt->close();

#$rows = $wpdb->get_col( "SELECT COUNT(*) as num_rows FROM wp_order_status WHERE order_id=$id");

while ($restaurant = $restaurants->fetch_array()){
	if ($restaurant['ID'] == $selected)
		echo '<option selected value =' . $restaurant['ID'] . '>' . $restaurant['restaurant'] . '</option>';
	else
		echo '<option value =' . $restaurant['ID'] . '>' . $restaurant['restaurant'] . '</option>';
	}
	
?>
</select></label></p>
<p><label for="requests">Requests: <label><textarea form="order-form" name="requests" rows="4" cols="25" placeholder="input special instructions"></textarea>	</p>
<p><input type="submit" name="submit_form" value="Submit" /></p>
</fieldset>
</form>

<?php
//Table of orders with restaurant, customer, and order info

$sql = "SELECT p.ID, os.status, os.note, os.restaurant_id FROM wp_posts p
	LEFT JOIN wp_order_status os ON(p.ID = os.order_id)
	WHERE p.post_type = 'shop_order'
	AND os.order_id is null
	OR (os.order_id is not null 
	AND os.status <> 'closed')
	LIMIT 10";

$stmt = $con->prepare($sql);

$stmt->execute();
$results = $stmt->get_result();
// Perform Query

$orders = array();

//need to add statement for when no orders exist

while($row = $results->fetch_assoc()) {
	$order_id = $row['ID'];

	//get order items
	$order_items = getOrderItems($order_id);	
	
	//get customer info
	$customer = getCustomer($order_id);
	
	//get notes or special requests associated with the order
	$note = getNote($order_id);
	$restaurant = getRestaurant($order_id);
	$status = $row['status'];
	$order = array('order_id' => $order_id, 'order_items'=> $order_items, 'customer'=> $customer, 'note'=>$note, 'status'=>$status, 'restaurant' => $restaurant);
	array_push($orders, $order);
	}

$stmt->close();


echo '<h2>Delivery Orders</h2><p>';



?>
<table id="orderTable">
<thead><tr><th>Order ID</th><th>Customer</th><th>Purchases</th><th>Restaurant</th><th>Notes</th><th>Status</th></tr></thead>
<tbody>
<?php
$link = "http://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";

foreach ($orders as $o){

	$order = '<tr class="oid" id="oid-'. $o['order_id'] .'">';
	
	$order .= '<td>' . $o['order_id'] . '<br>';
	$order .= '<a href =' . $link . '?action=confirm&id=' . $o['order_id'] . '>Confirm Order</a><br>';
	$order .= '<a href =' . $link . '?action=close&id=' . $o['order_id'] . '>Close</a>';
	$order .= '</td>';
	$order .= '<td class="customer">' . $o['customer'] . '</td>';
	$order .= '<td class="email">' . $o['order_items'] . '</td>';
	$order .= '<td class="restaurant">' .  $o['restaurant'] . '</td>';
	$order .= '<td class="notes">' . $o['note'] . '</td>';
	$order .= '<td class="status">' . $o['status'] . '</td>';
	
	$order .= '</tr>';
	echo $order;

}
?>
</tbody>
</table>
</div>

</body>
</html>
<?php
$con->close();
?>
