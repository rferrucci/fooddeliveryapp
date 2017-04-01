// magic.js

$(document).ready(function() {
    // process the form
    $('#restaurant-form').submit(function(event) {

        // get the form data
        // there are many ways to get this data using jQuery (you can use the class or id also)
        var formData = {
            'restaurant'              : $('input[name=restaurant]').val(),
            'email'             : $('input[name=email]').val(),
            'ID'             : $('input[name=id]').val(),

            'submitChanges'    : $('input[name=submitChanges]').val()
        };

	// process the form
	$.ajax({
	    type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
	    url         : 'process.php', // the url where we want to POST
	    data        : formData, // our data object
	    dataType    : 'json' // what type of data do we expect back from the server
	})
	    // using the done promise callback
	    .done(function(data) {
	
	        // log data to the console so we can see
	        console.log(data);
	
	        // here we will handle errors and validation messages
	        if ( ! data.success) {
	            
	            // handle errors for name ---------------
	            if (data.errors.name) {
	                $('#name-group').addClass('has-error'); // add the error class to show red input
	                $('#name-group').append('<div class="help-block">' + data.errors.name + '</div>'); // add the actual error message under our input
	            }
	
	            // handle errors for email ---------------
	            if (data.errors.email) {
	                $('#email-group').addClass('has-error'); // add the error class to show red input
	                $('#email-group').append('<div class="help-block">' + data.errors.email + '</div>'); // add the actual error message under our input
	            }
	
	            // handle errors for superhero alias ---------------
	            if (data.errors.superheroAlias) {
	                $('#superhero-group').addClass('has-error'); // add the error class to show red input
	                $('#superhero-group').append('<div class="help-block">' + data.errors.superheroAlias + '</div>'); // add the actual error message under our input
	            }
	
	        } else {
	
	            // ALL GOOD! just show the success message!
	            $('form').append('<div class="alert alert-success">' + data.message + '</div>');
	
	            // usually after form submission, you'll want to redirect
	            // window.location = '/thank-you'; // redirect a user to another page
	            alert('success'); // for now we'll just alert the user
	
	        }
	        
	        // using the done promise callback
	            .done(function(data) {
	
	                // log data to the console so we can see
	                console.log(data); 
	
	                // here we will handle errors and validation messages
	            });
	
	        // stop the form from submitting the normal way and refreshing the page
	        event.preventDefault();
	    });
	
	});
	
    });

/*works in last version, needs to be added to js file*/    
/*
if (($('input[name=submitChanges]').val() == 'delete'){
	var rid = $('input[name=Id]').val();
	$("#" + rid).remove();
});
	
else if (($('input[name=submitChanges]').val() == 'update'){
	var rid = $('input[name=Id]').val();
	var email = $('input[name=email]').val();
	var restaurant = $('input[name=restaurant]').val();
	$("#" + rid ).find(".restaurant").html(restaurant);
	$("#" + rid ).find(".email").html(email);
});
		
else if (($('input[name=submitChanges]').val() == 'insert'){
	var rid = $('input[name=Id]').val();
	var email = $('input[name=email]').val();
	var restaurant = $('input[name=restaurant]').val();
	var new_row ='<tr id="rid-' + rid + '">';
	new_row +='<td><a href ="' + link + '?action=edit&id=' + rid + '">Edit</a><br>';
	new_row += '<a href ="' + link + '?action=delete&id=' + rid + '">Delete</a>';
	new_row += '<td class="restaurant">' + restaurant + '</td>';
	new_row += '<td class="email">' + email +'</td>';
	new_row += '<td class="online"><img width="16" height="16"  alt="offline" title="offline"'; 
	new_row += 'src="images/transparent-red.png"</td></tr>';
	$('#restaurantTable tr:last').after(new_row);
});*/
