/*
JavaScript/Jquery code for inserting, removing, or updating entries in the table. In the restaurants.php file, I actually have the jQuery
code implemented using php for each function since I also have each function coded as a method in a restaurants class. Just wanted to
include it here as straight JQuery.
*/

$(document).ready(function(){
  $('form').submit(function(event){
    var restaurant = $('input[name=restaurant]').val(); 
    var email = $('select[name=email]').val(); 
    var submit = $('button[name=submitChanges]').val(); 
    var ID = $('input[name=ID]').val(); 
    
    if (submit=='delete') $('#rid-' + ID).remove(); #delete record
    else if (submit=='update'){
	# here, we are updating the restaurant name and email from the record.
    	var oldEmail = $("#rid-" + ID ).find(".email").html();
    	$("#rid-" + ID ).find(".restaurant").html(restaurant); 
	$("#rid-" + ID ).find(".email").html(email);
	$("select[name=email]").find('#' + email).attr('disabled',false);
	$("select[name=email]").find('#' + email).attr('selected',true);
	}
    else if (submit=='insert'){
    	var link = $(location).attr('href');
	var new_row ='<tr id="rid-' + ID + '">';
	new_row +='<td><a href ="' + link + '?action=edit&id=' + ID + '">Edit</a><br>';
	new_row += '<a href ="' + link + '?action=delete&id=' + ID + '">Delete</a>';
	new_row += '<td class="restaurant">' + restaurant + '</td>';
	new_row += '<td class="email">' + email +'</td>';
	new_row += '<td class="online"><img width="16" height="16"  alt="offline" title="offline"'; 
	new_row += 'src="images/transparent-red.png"</td></tr>';
	$('#restaurantTable tr:last').after(new_row);
			//$("#" + rid).(".restaurant").html(restaurant);
			//$("#" + rid).(".email").html(email);
    
    	}

    // stop the form from submitting and refreshing
    event.preventDefault();

  });
});
