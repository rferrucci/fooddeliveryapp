<?php
/**
generates a random password of random length between 8 and some maximum number, mails users the password, then hashses it before being stored in 
a database
 */
function random_str(
    $max, $email, $subject, $msg
    $keyspace = 'VnWkSCY75Fys!EL24fUoNguHabv1XPeqQ8pRcM3xz9irIjOGBDmwh@l6JZ0tTKdA'
) {
    $str = '';
    $min = 8;
    //takes maximum length as input and generates a random length for the password with min length of 8.
    //$keyspace has been randomly shuffled in python
    $length = rand($min, $max +1);
    $max = mb_strlen($keyspace, '8bit') - 1;
    if ($max < 1) {
        throw new Exception('$keyspace must be at least two characters long');
    }
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[mt_rand(0, $max)];
    }
    mail($email,$subject,$msg);
    $hash= hash ( "sha256" , $password);
    return $hash;
}


/*$password = random_str(12);

$msg = 'your password for the app is ' .$password .'.';
$msg .= 'use this email for logging in.';
echo $hash */

//

?>
