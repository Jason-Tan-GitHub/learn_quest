<?php
session_start();
include("connect.php");

$message = ""; // Variable to store inline error message for password mismatch
$success = false; // Flag to indicate success

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if passwords match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match. Please try again.']);
        exit;
    }

    $token = $_POST["token"];
    $password = $_POST["password"];

    // Hash the token and check it in the database
    $token_hash = hash("sha256", $token);

    $sql = "SELECT * FROM user WHERE reset_token_hash = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'An error occurred during the request.']);
        exit;
    }

    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check if the user exists and if the token is valid
    if ($user === null) {
        echo json_encode(['status' => 'error', 'message' => 'Token not found. Please request another.']);
        exit;
    } elseif (strtotime($user["reset_token_expires_at"]) <= time()) {
        echo json_encode(['status' => 'error', 'message' => 'Token has expired. Please request another.']);
        exit;
    } else {
        // Hash the new password before saving
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Update the user's password and reset token information
        $sql = "UPDATE user SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while updating the password.']);
            exit;
        }

        $stmt->bind_param("si", $hashed_password, $user['id']);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.', 'redirect' => 'index.php']);
        exit;
    }
}

$token = $_GET["token"] ?? "";

mysqli_close($conn);
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

        <form id="resetForm" class="reset-page">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="password" id="password" name="password" placeholder="New Password" required>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="button" id="resetButton" onclick="submitPasswordReset()">Reset</button>
        </form>
    </div>

    <!-- Loading Popup -->
    <div id="loadingPopup" style="display: none;">
        <div class="popup-overlay">
            <div class="popup-content">
                <p>Processing your request, please wait...</p>
            </div>
        </div>
    </div>

    <script>
        function submitPasswordReset() {
            const form = document.getElementById('resetForm');
            const formData = new FormData(form);

            // Show loading popup
            const loadingPopup = document.getElementById('loadingPopup');
            loadingPopup.style.display = 'block';

            // Disable the reset button
            const resetButton = document.getElementById('resetButton');
            resetButton.disabled = true;

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    // Hide loading popup
                    loadingPopup.style.display = 'none';

                    // Enable the reset button
                    resetButton.disabled = false;

                    alert(result.message);

                    if (result.status === 'success' && result.redirect) {
                        window.location.href = result.redirect;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);

                    // Hide loading popup
                    loadingPopup.style.display = 'none';

                    // Enable the reset button
                    resetButton.disabled = false;

                    alert('An error occurred while processing your request.');
                });
        }
    </script>
</body>

</html>
