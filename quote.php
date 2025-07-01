<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $to = "cimossupplies@gmail.com"; 
    $subject = "New Quote Request - Cimos Press";
    
    $body = "New Quote Request Received:\n";
    $body .= "First Name: " . $_POST['firstName'] . "\n";
    $body .= "Last Name: " . $_POST['lastName'] . "\n";
    $body .= "Email: " . $_POST['email'] . "\n";
    $body .= "Phone: " . $_POST['phone'] . "\n";
    $body .= "Company: " . $_POST['company'] . "\n";
    $body .= "Project Type: " . $_POST['projectType'] . "\n";
    $body .= "Quantity: " . $_POST['quantity'] . "\n";
    $body .= "Paper Size: " . $_POST['paperSize'] . "\n";
    $body .= "Color Mode: " . $_POST['colorMode'] . "\n";
    $body .= "Deadline: " . $_POST['deadline'] . "\n";
    
    $services = isset($_POST['services']) ? implode(", ", $_POST['services']) : "None";
    $body .= "Services: " . $services . "\n";

    $headers = "From: cimossupplies@gmail.com";

    if (mail($to, $subject, $body, $headers)) {
        echo "Success";
    } else {
        echo "Error sending email";
    }
}
?>
