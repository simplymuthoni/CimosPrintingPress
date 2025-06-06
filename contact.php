<?php
// Replace with your email
$toEmail = "cimossupplies@gmail.com";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and store form data
    $name = htmlspecialchars(trim($_POST["name"] ?? ''));
    $email = filter_var(trim($_POST["email"] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST["phone"] ?? ''));
    $message = htmlspecialchars(trim($_POST["message"] ?? ''));

    // Validate input
    if (empty($name) || empty($email) || empty($message)) {
        $response = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = "Please enter a valid email address.";
    } else {
        // Compose email
        $subject = "New Contact Message from $name";
        $body = "Name: $name\nEmail: $email\nPhone: $phone\n\nMessage:\n$message";
        $headers = "From: $email";

        // Send email
        if (mail($toEmail, $subject, $body, $headers)) {
            $response = "Thank you! Your message has been sent.";
        } else {
            $response = "Oops! Something went wrong. Please try again.";
        }
    }
} else {
    $response = "Invalid request.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contact Response</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; padding: 2em; background: #f9f9f9; }
        .response { max-width: 600px; margin: auto; padding: 1.5em; background: white; border-radius: 6px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        a { display: block; margin-top: 1em; color: #007BFF; text-decoration: none; }
    </style>
</head>
<body>
    <div class="response">
        <h2>Contact Form Submission</h2>
        <p class="<?= strpos($response, 'Thank you') !== false ? 'success' : 'error' ?>"><?= $response ?></p>
        <a href="index.html">← Back to Home</a>
    </div>
</body>
</html>
