<?php
session_start();
require("connect.php");

$message = '';
$redirect = '';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];

    // Validate the email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
    } else {
        // Fetch user by email
        $sql_user = "SELECT username FROM user WHERE email = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("s", $email);
        $stmt_user->execute();
        $result = $stmt_user->get_result();
        $user = $result->fetch_assoc();

        // Check if user exists
        if ($user) {
            $name = $user['username'] ?? 'User';  // Default to "User" if username is missing

            // Generate token and expiration time
            $token = bin2hex(random_bytes(16));  // Generate a secure random token
            $token_hash = hash("sha256", $token);  // Hash the token for storage
            $expiry = date("Y-m-d H:i:s", time() + 60 * 30);  // 30-minute expiration

            // Update the user's reset token and expiry
            $sql = "UPDATE user
                    SET reset_token_hash = ?, reset_token_expires_at = ?
                    WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $token_hash, $expiry, $email);

            if ($stmt->execute()) {
                if ($conn->affected_rows > 0) {
                    // Email the user with the reset link
                    $mail = include("mailer.php");  // Assume mailer.php returns a $mail object
                    $mail->setFrom("noreply@example.com", "Capital Edge");
                    $mail->addAddress($email);
                    $mail->Subject = "Password Reset";
                    $mail->Body = <<<END
                    <p>Hello, $name!</p>
                    <p>It seems that you have forgotten your password. Do not worry, we are here to help.</p>
                    <p>Click <a href="http://localhost/RWDD_Assignment_Website/reset-password.php?token=$token">here</a> to reset your password.</p>
                    END;

                    try {
                        if ($mail->send()) {
                            $message = "Reset link sent, please check your inbox.";
                            $redirect = 'index.php';
                        } else {
                            error_log("Mailer Error: " . $mail->ErrorInfo);
                            $message = "An error occurred while sending the email. Please try again later.";
                        }
                    } catch (Exception $e) {
                        error_log("Mailer Exception: " . $e->getMessage());
                        $message = "An error occurred while sending the email. Please try again later.";
                    }
                } else {
                    $message = "Failed to update reset token.";
                }
            } else {
                $message = "Failed to execute query: " . $stmt->error;
            }
        } else {
            $message = "No user found with that email address.";
        }
    }

    // Check for session ID after processing the form
    if (isset($_SESSION['id'])) {
        // Redirect to logout.php if session ID is detected
        echo "<script>alert('Reset password link sent. Logging you out for security reasons.'); window.location.href = 'logout.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learn Quest - Forgot Password</title>
    <link rel="icon" type="image/icon" href="image/favicon.svg" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">

</head>

<body class="bodyReset">
    <img class="imgReset" src="image/learn_quest.svg" alt="">
    <hr class="hr1">
    <div class="form-request-reset-password">
        <h1 class="reset-page font">Password Reset</h1>
        <hr class="hr2">
        <form class="reset-page" method="post" action="">
            <input type="email" name="email" id="email" placeholder="Email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
            <button type="submit">Request Reset Link</button>
        </form>
    </div>

    <?php if (!empty($message)): ?>
        <script>
            alert("<?= htmlspecialchars($message) ?>");
            <?php if (!empty($redirect)): ?>
                window.location.href = "<?= htmlspecialchars($redirect) ?>";
            <?php endif; ?>
        </script>
    <?php endif; ?>
</body>

</html>
