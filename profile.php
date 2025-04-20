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

    $stmtLeaderboard = $conn->prepare("SELECT username, total_points FROM user ORDER BY total_points DESC");
    $stmtLeaderboard->execute();
    $resultLeaderboard = $stmtLeaderboard->get_result();
    $userLeaderboard = $resultLeaderboard->fetch_all(MYSQLI_ASSOC); // Fetch all rows as an associative array
    $stmtLeaderboard->close();

    if ($user) {
        // Apply XSS protection
        $id = htmlspecialchars($user["id"], ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($user["username"], ENT_QUOTES, 'UTF-8');
        $points = htmlspecialchars($user["total_points"], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($user["email"], ENT_QUOTES, 'UTF-8');
    } else {
        // User does not exist
        header("Location: index.php");
        exit;
    }
} else {
    // User is not logged in
    header("Location: index.php");
    exit;
}

// Handle form submission to update user details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_details_button'])) {
    $newName = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $newEmail = htmlspecialchars(trim($_POST['email']), ENT_QUOTES, 'UTF-8');

    // Check if the new username or email already exists for another user
    $stmtCheck = $conn->prepare("SELECT id FROM user WHERE username = ? OR email = ? AND id != ?");
    $stmtCheck->bind_param("ssi", $newName, $newEmail, $sessionId);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        $stmtCheck->bind_result($existingId);
        $stmtCheck->fetch();

        // Check which field is causing the conflict
        $userCheckStmt = $conn->prepare("SELECT id FROM user WHERE username = ? AND id != ?");
        $userCheckStmt->bind_param("si", $newName, $sessionId);
        $userCheckStmt->execute();
        $userCheckStmt->store_result();

        if ($userCheckStmt->num_rows > 0) {
            // Conflict with username
            $userCheckStmt->close();
            echo "<script>
                    alert('Username already exists. Please use a different username.');
                    window.location.href = 'profile.php';
                  </script>";
            exit;
        }
        $userCheckStmt->close();

        // Check if the email is causing the conflict
        $emailCheckStmt = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
        $emailCheckStmt->bind_param("si", $newEmail, $sessionId);
        $emailCheckStmt->execute();
        $emailCheckStmt->store_result();

        if ($emailCheckStmt->num_rows > 0) {
            $emailCheckStmt->close();
            echo "<script>
                    alert('Email already exists. Please use a different email.');
                    window.location.href = 'profile.php';
                  </script>";
            exit;
        }
        $emailCheckStmt->close();
    }

    $stmtCheck->close();

    // Proceed with updating the user's details
    $stmt = $conn->prepare("UPDATE user SET username = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $newName, $newEmail, $sessionId);

    if ($stmt->execute()) {
        // Update successful, refresh the page to show updated details
        echo "<script>
                alert('Details Updated Successfully');
                window.location.href = 'profile.php';
              </script>";
        exit;
    } else {
        $message = "Failed to update details: " . $stmt->error;
    }

    $stmt->close();
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

//Update Profile Pic
if (isset($_FILES["image"]["name"])) {
    $id = $_POST["id"];
    $name = $_POST["name"];
    $imageName = $_FILES["image"]["name"];
    $imageSize = $_FILES["image"]["size"];
    $tmpName = $_FILES["image"]["tmp_name"];
    $validImageExtension = ['jpg', 'jpeg', 'png'];
    $imageExtension = explode('.', $imageName);
    $imageExtension = strtolower(end($imageExtension));
    if (!in_array($imageExtension, $validImageExtension)) {
        echo
        "<script>
      alert('Invalid image extension');
      document.location.href = '../updateimageprofile';
      </script>";
    } elseif ($imageSize > 12000000) {
        echo
        "<script>
      alert('Image size is too large');
      document.location.href = '../updateimageprofile';
      </script>";
    } else {
        $newImageName = $name . " - " . date("Y.m.d") . " - " . date("h.i.sa"); //generate new image name
        $newImageName .= "." . $imageExtension;
        $query = "UPDATE user SET profile_pic = '$newImageName' WHERE id = $id";
        mysqli_query($conn, $query);
        move_uploaded_file($tmpName, 'imageProfile/' . $newImageName);
    }
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
                            <?php
                            if ($user !== null) {
                                $id = $user["id"];
                                $name = $user["username"];
                                $image = $user["profile_pic"];
                            ?>
                                <img class="user-img-settings" src="imageProfile/<?php echo $image; ?>" alt="Your Profile">
                                <div class="round-settings">
                                    <i class='bx bxs-camera bx-md'></i>
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="name" value="<?php echo $name; ?>">
                                    <input type="file" name="image" id="image" accept=".jpg, .jpeg, .png" title="">
                                </div>
                            <?php } else {
                                header("Location: login_signup.php");
                                exit;
                            } ?>
                        </div>
                    </form>
                    <script>
                        // profile changer
                        document.getElementById("image").onchange = function() {
                            document.getElementById('formProfilePic').submit();
                        }
                    </script>
                </div>
                <div class="profileContainer1">
                    <div class="container1profile">
                        <form action="" method="POST" class="container1profile-left">
                            <h5>Name:</h5>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
                            <h5>Email:</h5>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            <div class="container1profile-left-buttons">
                                <input type="submit" value="Save Details" name="save_details_button" class="action-profile-button action-profile-buttons">
                            </div>
                        </form>
                        <div class="container1profile-right">
                            <div class="backgroundPoints">
                                <h3>Points: <?php echo $points ?></h3>
                            </div>
                            <form id="resetPasswordForm" method="POST" action="reset-page.php" class="resetForm">
                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                                <button type="submit" class="action-profile-button action-profile-buttons">Reset Password</button>
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
                                <th>Username</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <div class="background-leaderboard">
                                <?php if (!empty($userLeaderboard)): ?>
                                    <?php foreach ($userLeaderboard as $rank => $user): ?>
                                        <tr>
                                            <td><?= $rank + 1 ?></td>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td><?= htmlspecialchars($user['total_points']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3">No leaderboard data available.</td>
                                    </tr>
                                <?php endif; ?>
                            </div>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="profileContainer3">
                <h1>Quiz History</h1>
                <div class="background-quiz-history">
                    <?php if (!empty($quizHistory)): ?>
                        <?php foreach ($quizHistory as $history): ?>
                            <form action="view_history_quiz.php" method="GET">
                                <input type="hidden" name="quiz_id" value="<?= htmlspecialchars($history['quiz_id']) ?>">
                                <button type="submit" class="quiz-history-button quiz-history-buttons">
                                    <strong><?= htmlspecialchars($history['quiz_name']) ?></strong>
                                    - Score: <?= htmlspecialchars($history['total_correct_answers']) ?>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No quiz history found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="functions.js"></script>
</body>

</html>