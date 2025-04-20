<?php
session_start();
include("connect.php");

$error_message = ""; // Variable to store inline error message for password mismatch
$message = ""; // Variable to store pop-up messages
$success = false; // Flag to indicate success

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if passwords match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $error_message = "Passwords do not match. Please try again.";
    } else {
        $token = $_POST["token"];
        $password = $_POST["password"];

        // Hash the token and check it in the database
        $token_hash = hash("sha256", $token);

        $sql = "SELECT * FROM user WHERE reset_token_hash = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $message = "An error occurred during the request.";
        } else {
            $stmt->bind_param("s", $token_hash);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            // Check if the user exists and if the token is valid
            if ($user === null) {
                $message = "Token not found. Please request another.";
                $redirect = "index.php"; // Page to redirect to
            } elseif (strtotime($user["reset_token_expires_at"]) <= time()) {
                $message = "Token has expired. Please request another.";
                $redirect = "index.php"; // Page to redirect to
            } else {
                // Hash the new password before saving
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Update the user's password and reset token information
                $sql = "UPDATE user SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    $message = "An error occurred while updating the password.";
                } else {
                    $stmt->bind_param("si", $hashed_password, $user['id']);
                    $stmt->execute();

                    // Set success flag to true after successful password reset
                    $success = true;
                    $message = "Password updated successfully.";
                    $redirect = "index.php"; // Page to redirect to
                }
            }
        }
    }
}

$token = $_GET["token"];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learn Quest - New Password</title>
    <link rel="icon" type="image/icon" href="image/favicon.svg" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body class="bodyReset">
    <img class="imgReset" src="image/learn_quest.svg" alt="">
    <hr class="hr1">
    <div class="form-reset-password">
        <h1 class="reset-page font2">Reset Password</h1>
        <hr class="hr3">

        <!-- Display error message if passwords don't match -->
        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <form class="reset-page" method="post" action="">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="password" id="password" name="password" placeholder="New Password" required>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            <button>Reset</button>
        </form>
    </div>

    <?php if (!empty($message)): ?>
        <script>
            alert("<?= htmlspecialchars($message) ?>");
            window.location.href = "<?= htmlspecialchars($redirect) ?>";
        </script>
    <?php endif; ?>

</body>
</html>