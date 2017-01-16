<?php
/*
for client's food delivery app, where restaurants receive orders from her WordPress installation.
When client associates a restaurant with an order, order status is set as "submitted" in the order_status table (previously does not exist).
When the app downloads the data and this script is accessed, the status is changed to "sent" meaning that it has been sent to the app.
Restaurants then will intention that they received the order (which will be changed in the database in the update_order script to "received")
and finally changed to completed when it has been fullfilled. At this point, it is no longer received on the restaurant's end but still
listed on the backend until client delivery service closes the order.
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

		$order_items .= '*** ' . $addon . ': ';
		while($add = $results->fetch_assoc()){

		    $order_items .=  $add['meta_value'] . ', ';
		    }
	    	$order_items = trim($order_items, " ,");	
            	$order_items .= '<br>';

                }
            }
            return $order_items;
	}
	
function getOrderItems($order_id){
	//receives order information for the restaurant
	global $con;
	
	//receive order items from woocommerce associated with the order id for this order
	$query = "SELECT * FROM wp_woocommerce_order_items
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
		   	$order_items .= '*** Ingredients: ' ;
		        while($row = $results->fetch_assoc()){
		            // ($results=null) continue;
		            //global $row;
		            $order_items .= $row[$i]['meta_value'] . ', ';    
	            		}
	            	$order_items = trim($order_items,',');
	            	$order_items .= '<br>';
	            	}
	            
		//here, we obtain product attributes: spice level, type (i.e., chicken, beef, tofu), etc.
		$order_items .= getAdditionalOrderInfo($product_id,$item_id,'_product_attributes');
		
	        //get addons, fries, sour cream, etc.    
		$order_items .= getAdditionalOrderInfo($product_id,$item_id,'_addons');
		}
	        $query="SELECT * FROM wp_order_status WHERE order_id=$order_id";
	        $results = mysqli_query($con,$query);
	        $requests = mysqli_fetch_assoc($results);
	
	        $order_items .= '<br/>SPECIAL REQUESTS: ' . $requests['requests'];   
		
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
	
	return $firstname . " " . $lastname . "<br>" . $add1 . "<br>" . $add2 . "<br>" . $phone;
}

function getNote($order_id){
	// this function just receives special instructions from the delivery service for the restaurant
	global $con;
	
	$query = "SELECT note FROM wp_order_status WHERE order_id=$order_id";
	$result= mysqli_query($con,$query);
	$row = mysqli_fetch_assoc($result);
	return $row['note'];
	}

function getJSON(){
	//obtains order and customer info from the WordPress database and submits to the app in json format
	global $con;
	
	//get email address from url
    	$email=$_GET['rid'];
    	//add prepare statements and the like

	//get restaurant id for orders from email address
	//did it this way in case email address associated with restaurant changes, and figure they would remember their email address before
	//restaurant id
    	$query= "SELECT id FROM wp_restaurants
    	WHERE email= ?
	";

	$stmt = $con->prepare($query);
	$stmt->bind_param('s', $email);
	$stmt->execute();
    	$stmt->bind_result($rid);
    	$stmt->fetch();
	
	$stmt->close();

	//get open orders for the restaurant that have no been "closed" on client's end 
	$query= "SELECT * FROM wp_order_status
	    	WHERE restaurant_id=?
		AND status <> 'closed'
		ORDER BY 'order_id' ASC
		";
	
	$stmt = $con->prepare($query);

	$stmt->bind_param('s', $rid);
	$stmt->execute();
	$result = $stmt->get_result();
	// Perform Query
	
	$orders = array();
	
	//need to add statement for when no orders exist
	
	while($row = $result->fetch_assoc()) {
		
		$order_id = $row['order_id'];
		
		//get order items
		$order_items = getOrderItems($order_id);	
		
		//get customer info
	
		$customer = getCustomer($order_id);
	 	
		//get notes or special requests associated with the order
		$note = getNote($order_id);
		$status = $row['status'];
		
		array_push($orders, array('id' => $order_id, 'customer' => $customer, 'order'=> $order_items, 'note' => $note));
		date_default_timezone_set('America/Chicago');
		$time = date('H:i:s');
		
		//here we update the order to say that it has been sent and set the time.
		$query= "UPDATE wp_order_status
			SET status='sent',timeOrderSent=NOW()
		    	WHERE order_id = $order_id
					";
					
		if ($status=='submitted') $con->query($query);		
		}
	
	$stmt->close();
	$con->close();
	echo json_encode(array("result"=>$orders),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	
	}

if (isset($_GET["rid"]) && (isset($_GET["loggedin"]))) {
	//checks for restaurant id, here it is the email associated with their account.
	//gets all information regarding orders and submits as json to the app
	getJSON();
}

else if (isset($_GET["rid"]) && (isset($_GET["neworders"]))) {
	//checks for new orders that have not been sent to the restaurant yet. Message pops up on app that alerts restaurant to new orders
	global $con;
    	$email=$_GET['rid'];
    	//add prepare statements and the like

    	$query= "SELECT id FROM wp_restaurants
    	WHERE email= ?
	";

	$stmt = $con->prepare($query);
	$stmt->bind_param('s', $email);
	$stmt->execute();
    	$stmt->bind_result($rid);
    	$stmt->fetch();
	$stmt->close();
	$query= "SELECT * FROM wp_order_status
	    	WHERE restaurant_id=?
		AND status = 'submitted'
		";
	
	$stmt = $con->prepare($query);
	$stmt->bind_param('s', $rid);
	$stmt->execute();
    	$stmt->store_result();

	echo $stmt->num_rows;
	$stmt->close();
	}

else if (isset($_GET["rid"]) && (isset($_GET["lateorders"]))) {
	//checks for orders that have been sent to the restaurant and notifies them that it has been over 5 minutes and they have not acknowledged receipt yet
	global $con;
    	$email=$_GET['rid'];
    	//add prepare statements and the like

    	$query= "SELECT id FROM wp_restaurants
    	WHERE email= ?
	";

	$stmt = $con->prepare($query);
	$stmt->bind_param('s', $email);
	$stmt->execute();
    	$stmt->bind_result($rid);
    	$stmt->fetch();
    	$stmt->close();
    	
    	date_default_timezone_set('America/Chicago');
	$time = date('H:i:s');

	
	$query= "SELECT TIMEDIFF(CURTIME(), timeOrderSent)+0 FROM wp_order_status
	    	WHERE restaurant_id=?
		AND status = 'sent'
		AND TIMEDIFF(CURTIME(), timeOrderSent)+0 > 500";

	$stmt = $con->prepare($query);
	
	$stmt->bind_param('s',$rid);
	$stmt->execute();
	   $result = $stmt->get_result();

    	/* now you can fetch the results into an array - NICE */
    	while ($myrow = $result->fetch_assoc()) {

        	print_r($myrow);

    }
	
	$stmt->close();
}

?>
