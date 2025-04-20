<?php
session_start();
include('guard.php');
include('connect.php');

if (isset($_SESSION['id'])) {
    $sessionId = $_SESSION['id'];

    // Fetch user information securely
    $stmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    $stmtLeaderboard = $conn->prepare("SELECT username, is_admin, total_points FROM user ORDER BY total_points DESC");
    $stmtLeaderboard->execute();
    $resultLeaderboard = $stmtLeaderboard->get_result();
    $userLeaderboard = $resultLeaderboard->fetch_all(MYSQLI_ASSOC);
    $stmtLeaderboard->close();

    if ($user) {
        $id = htmlspecialchars($user["id"], ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($user["username"], ENT_QUOTES, 'UTF-8');
        $points = htmlspecialchars($user["total_points"], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($user["email"], ENT_QUOTES, 'UTF-8');
    } else {
        header("Location: index.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}

// Handle form submission to update user details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_details_button'])) {
    $newName = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
    $newEmail = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');

    $stmt = $conn->prepare("UPDATE user SET username = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $newName, $newEmail, $sessionId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Details Updated Successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update details: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Fetch quiz history
$quizHistory = fetchQuizHistory($conn, $sessionId);

// Function to fetch user's quiz history
function fetchQuizHistory($conn, $userId)
{
    $stmt = $conn->prepare("SELECT h.quiz_id, h.total_correct_answers, c.quiz_name 
                            FROM history h 
                            JOIN quiz c ON h.quiz_id = c.quiz_id 
                            WHERE h.id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $history;
}

// reset password
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];

    // Validate the email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }

    // Fetch user by email
    $sql_user = "SELECT username FROM user WHERE email = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $email);
    $stmt_user->execute();
    $result = $stmt_user->get_result();
    $user = $result->fetch_assoc();

    // Check if user exists
    if ($user) {
        $name = $user['username'] ?? 'User'; // Default to "User" if username is missing

        // Generate token and expiration time
        $token = bin2hex(random_bytes(16)); // Generate a secure random token
        $token_hash = hash("sha256", $token); // Hash the token for storage
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // 30-minute expiration

        // Update the user's reset token and expiry
        $sql = "UPDATE user SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $token_hash, $expiry, $email);

        if ($stmt->execute() && $conn->affected_rows > 0) {
            // Email the user with the reset link
            $mail = include("mailer.php"); // Assume mailer.php returns a $mail object
            $mail->setFrom("noreply@example.com", "Learn Quest");
            $mail->addAddress($email);
            $mail->Subject = "Password Reset";
            $mail->Body = <<<END
                <p>Hello, $name!</p>
                <p>It seems that you have forgotten your password. Do not worry, we are here to help.</p>
                <p>Click <a href="http://localhost/RWDD_Assignment_Website/reset-password.php?token=$token">here</a> to reset your password.</p>
                END;

            try {
                if ($mail->send()) {
                    echo json_encode(['status' => 'success', 'message' => 'Reset link sent, please check your inbox.']);
                } else {
                    error_log("Mailer Error: " . $mail->ErrorInfo);
                    echo json_encode(['status' => 'error', 'message' => 'An error occurred while sending the email. Please try again later.']);
                }
            } catch (Exception $e) {
                error_log("Mailer Exception: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'An error occurred while sending the email. Please try again later.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update reset token.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No user found with that email address.']);
    }
    exit;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnQuest - Profile Page</title>
    <link rel="icon" type="image/icon" href="image/favicon.svg" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/w3.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body>
    <?php include('top_nav.php'); ?>
    <div class="containerProfileBackground">
        <div class="containerProfile">
            <div class="profileContainer">
                <div class="profileContainerProfile">
                    <form class="formProfilePic" id="formProfilePic" action="" enctype="multipart/form-data" method="post">
                        <div>
                            <?php if ($user !== null) { ?>
                                <img class="user-img-settings" src="imageProfile/<?= htmlspecialchars($user["profile_pic"]); ?>" alt="">
                                <div class="round-settings">
                                    <i class='bx bxs-camera bx-md'></i>
                                    <input type="file" name="image" id="image" accept=".jpg, .jpeg, .png">
                                </div>
                            <?php } else {
                                header("Location: login_signup.php");
                                exit;
                            } ?>
                        </div>
                    </form>
                </div>
                <div class="profileContainer1">
                    <div class="container1profile">
                        <form id="detailsForm" class="container1profile-left">
                            <h5>Name:</h5>
                            <input type="text" name="name" value="<?= $name; ?>">
                            <h5>Email:</h5>
                            <input type="email" name="email" value="<?= $email; ?>">
                            <div class="container1profile-left-buttons">
                                <button type="button" onclick="submitDetailsChange()" class="action-profile-button action-profile-buttons">Save Details</button>
                            </div>
                        </form>
                        <div class="container1profile-right">
                            <div class="backgroundPoints">
                                <h3>Points: <?= $points; ?></h3>
                            </div>
                            <form id="resetPasswordForm" method="POST" action="reset-page.php" class="resetForm">
                                <input type="hidden" name="email" value="<?= $email; ?>">
                                <button type="button" onclick="submitResetRequest()" class="action-profile-button action-profile-buttons">Reset Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="profileContainer2">
                <h1>Leaderboard</h1>
                <div class="scrollable-table-leaderboard">
                    <table class="table-leaderboard">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student Username</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($userLeaderboard)) { ?>
                                <?php $rank = 1; ?>
                                <?php foreach ($userLeaderboard as $user) { ?>
                                    <?php if ($user['is_admin'] != 1 && $user['is_admin'] != 2) { ?>
                                        <tr>
                                            <td><?= $rank; ?></td>
                                            <td><?= htmlspecialchars($user['username']); ?></td>
                                            <td><?= htmlspecialchars($user['total_points']); ?></td>
                                        </tr>
                                        <?php $rank++; ?>
                                    <?php } ?>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="3">No leaderboard data available.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="profileContainer3">
                <h1>Quiz History</h1>
                <div class="background-quiz-history">
                    <?php if (!empty($quizHistory)) { ?>
                        <?php foreach ($quizHistory as $history) { ?>
                            <form action="view_history_quiz.php" method="GET">
                                <input type="hidden" name="quiz_id" value="<?= htmlspecialchars($history['quiz_id']); ?>">
                                <button type="submit" class="quiz-history-button quiz-history-buttons">
                                    <strong><?= htmlspecialchars($history['quiz_name']); ?></strong> - Score: <?= htmlspecialchars($history['total_correct_answers']); ?>
                                </button>
                            </form>
                        <?php } ?>
                    <?php } else { ?>
                        <p>No quiz history found.</p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <script src="functions.js"></script>
    <script>
        function submitResetRequest() {
            const form = document.getElementById('resetForm');
            const button = document.getElementById('resetButton');
            const formData = new FormData(form);

            // Disable the button to prevent multiple clicks
            button.disabled = true;

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    alert(result.message);

                    if (result.status === 'success') {
                        window.location.href = "index.php";
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request.');
                })
                .finally(() => {
                    // Re-enable the button
                    button.disabled = false;
                });
        }

        function submitDetailsChange() {
            const form = document.getElementById('detailsForm');
            const formData = new FormData(form);
            formData.append('save_details_button', true);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'error') {
                    alert(result.message);
                } else if (result.status === 'success') {
                    alert(result.message);
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request.');
            });
        }
    </script>
</body>

</html>