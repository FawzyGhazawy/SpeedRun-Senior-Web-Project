<?php
require_once 'connection.php';
extract($_POST);
$recaptcha_response = $_POST['g-recaptcha-response'];
$recaptcha_secret = '6LcKXUAmAAAAANJrDtZklxuRxjZytaxwxQNB_J3r'; // Replace with your secret key obtained from the reCAPTCHA admin console
$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';

$recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
$recaptcha_data = json_decode($recaptcha);

if (!$recaptcha_data->success) {
    echo 'Failed to verify reCAPTCHA. Please try again.';
    exit;
}
// Proceed with your signup logic if reCAPTCHA is verified successfully
//check checkboxes
$fragile = isset($_POST['fragile']) ? true : false;
$pay_at_delivery = isset($_POST['pay_at_delivery']) ? true : false;
$urgent = isset($_POST['urgent']) ? true : false;
$sender_pays = isset($_POST['receiver_pays']) ? false : true;

$to_district = str_replace("%20", " ", $_POST['to_district']);

$c_id = 0;
$c_name = $_POST['c_name'];
$c_phone = $_POST['c_phone'];
$c_address = $_POST['c_address'];
$c_address = mysqli_real_escape_string($link, $c_address);
$c_district = str_replace("%20", " ", $_POST['c_district']);
$guest = true;
$query = "SELECT * FROM clients WHERE c_name='$c_name' AND c_phone='$c_phone' AND c_district='$c_district' AND c_address='$c_address'"; //check duplicate
$result = mysqli_query($link, $query);
if (($result) && (mysqli_num_rows($result) < 1)) {
    $query = "INSERT INTO clients(c_name,c_phone,c_address,c_district,guest) 
    VALUES ('$c_name','$c_phone','$c_address','$c_district','$guest')";

    if (mysqli_query($link, $query)) {
        $c_id = mysqli_insert_id($link);
    } else {
        echo "error try again later";
        return;
        exit;
    }
} else {
    $row = mysqli_fetch_assoc($result);
    $c_id = $row['c_id'];
}


$cost = 5; //standard cost on us
$charge = 5; //charge on customer

if ($fragile) { //extra for fragile
    $cost = $cost + 5;
    $charge = $charge + 5;
}
if ($urgent) { //extra for urgent
    $cost = $cost + 10;
    $charge = $charge + 10;
}

//if price not set 
if ($o_price == "") {
    $o_price = 0;
}

$f_price = $o_price + $cost + $charge;


$query = "INSERT into packages(width, height, weight, message, to_name, to_phone, to_address,to_district, fragile, urgent, sender_pays, o_price, cost, charge, f_price, pay_at_delivery) 
values('$width','$height','$weight', '$message', '$to_name', '$to_phone', '$to_address', '$to_district', '$fragile', '$urgent', '$sender_pays', '$o_price', '$cost', '$charge', '$f_price', '$pay_at_delivery')";

if (mysqli_query($link, $query)) {
    //echo mysqli_insert_id($link); //debug
    $p_id = mysqli_insert_id($link);

    //insert to orders too if successful
    $query = "INSERT INTO orders (p_id, c_id) VALUES ($p_id, $c_id);";
    if (mysqli_query($link, $query)) {
        //echo mysqli_insert_id($link); //debug
        $o_id = mysqli_insert_id($link);

        //insert to deliveries too if successful
        $query = "INSERT INTO deliveries (o_id) VALUES ('$o_id');";
        if (mysqli_query($link, $query)) {
            //echo mysqli_insert_id($link);
            http_response_code(200);
            echo "OK $o_id";
            if ($urgent) {
                include_once('../notification.php');
                notif($o_id);
            }
            exit;
        } else {

            echo "Failed to insert into deliveries";
        }
    } else {

        echo "Failed to insert into orders";
    }
} else {
    echo "Failed to insert into packages";
}
/*code with GPS
$query = "INSERT into packages(width, height, weight, message, to_address, p_longitude, p_latitude, to_district, fragile, o_price, cost, f_price, pay_at_delivery) 
values('$width','$height','$weight', '$message', '$to_address', '$p_longitude', '$p_latitude', '$to_district', '$fragile', '$o_price', '$cost', '$f_price', '$pay_at_delivery')";
*/