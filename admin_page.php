<?php
session_start();
include('guard.php');
include('connect.php');
if (isset($_SESSION['id'])) {
    $sessionId = $_SESSION['id'];
    $stmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Assign user details to variables (with XSS protection), which is storing code into a database
    // htmlspecialchars converts those commands to non executable words
    if ($user) {
        $id = htmlspecialchars($user["id"]);
        $name = htmlspecialchars($user["username"]);
        $email = htmlspecialchars($user["email"]);
    }
} else {
    header("Location: index.php");
    exit;
}

$signup_error = false;
$error_message_signup = "";
$signup_success = "";
$show_admin_save = false;

$username_edit = '';
$email_edit = '';
$password_edit = '';
// Handle Admin Signup Process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_btn_signup'])) {
    if (
        !empty($_POST['admin_username']) &&
        !empty($_POST['admin_email']) &&
        !empty($_POST['admin_password']) &&
        !empty($_POST['admin_confirm_password'])
    ) {
        $username = htmlspecialchars($_POST['admin_username']);
        $email = filter_var($_POST['admin_email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['admin_password'];
        $confirm_password = $_POST['admin_confirm_password'];

        if (!$email) {
            $error_message_signup = "Please enter a valid email address.";
            $signup_error = true;
        } elseif ($password !== $confirm_password) {
            $error_message_signup = "Passwords do not match.";
            $signup_error = true;
        } else {
            $emailCheckStmt = $conn->prepare("SELECT email FROM user WHERE email = ?");
            $emailCheckStmt->bind_param("s", $email);
            $emailCheckStmt->execute();
            $emailCheckStmt->store_result();

            if ($emailCheckStmt->num_rows > 0) {
                $error_message_signup = "Email already exists. Please use a different email.";
                $signup_error = true;
            } else {
                // Hash the password and insert new admin
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $is_admin = 1;
                $stmt = $conn->prepare("INSERT INTO user (email, password, username, is_admin) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $email, $hashed_password, $username, $is_admin);

                if ($stmt->execute()) {
                    $signup_success = "Admin signup successful.";
                    $signup_error = false; // Clear the error flag if successful
                } else {
                    $error_message_signup = "Error: Could not execute the query.";
                    $signup_error = true;
                }
                $stmt->close();
            }
            $emailCheckStmt->close();
        }
    } else {
        $error_message_signup = "Please fill in all required fields.";
        $signup_error = true;
    }
}

//delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete-button']) && isset($_POST['id'])) {
        $id = intval($_POST['id']); // Sanitize the input to prevent SQL injection

        // Prepare the DELETE query
        $deleteStmt = $conn->prepare("DELETE FROM user WHERE id = ?");
        $deleteStmt->bind_param("i", $id);

        if ($deleteStmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Record deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error deleting record: ' . $conn->error]);
        }

        $deleteStmt->close();
        exit;
    }
}


//edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit-details-button']) && isset($_POST['id'])) {
        $id = intval($_POST['id']); // Sanitize the input to prevent SQL injection
        $show_admin_save = true;

        // Fetch data from the database based on the ID
        $sql = "SELECT username, email FROM user WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $id); // Bind the ID to the query
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $username_edit = htmlspecialchars($row['username']);
                $email_edit = htmlspecialchars($row['email']);
            } else {
                $error_message = "No user found with this ID.";
            }

            $stmt->close(); // Close the statement
        } else {
            // Handle the case where the statement could not be prepared
            $error_message = "Database error: Could not prepare statement.";
        }
    }
}

//save edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['admin_btn_save']) && isset($_POST['id'])) {
        $id = intval($_POST['id']); // Sanitize the input to prevent SQL injection

        // Check required fields
        if (!empty($_POST['admin_username'])) {
            $username = htmlspecialchars($_POST['admin_username']);
            $password = $_POST['admin_password'] ?? '';

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Update full name and password
                $sql = "UPDATE user SET username = ?, password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $username, $hashed_password, $id);
            } else {
                // Update only full name (keep existing password)
                $sql = "UPDATE user SET username = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $username, $id);
            }

            // Execute the query
            if ($stmt->execute()) {
                $signup_success = "User details updated successfully.";
            } else {
                $error_message_signup = "Error: Could not execute the query.";
            }

            $stmt->close();
        } else {
            $error_message_signup = "Please fill in all required fields.";
        }
    }
}


//cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['admin_btn_cancel'])) {
        $id = intval($_POST['id']); // Sanitize the input to prevent SQL injection
        $show_admin_save = false;

        $username_edit = '';
        $email_edit = '';
        $password_edit = '';
    }
}


$adminListStmt = $conn->prepare("SELECT id, username, email FROM user WHERE is_admin = 1 AND id != ?");
$adminListStmt->bind_param("i", $_SESSION['id']);
$adminListStmt->execute();
$adminListResult = $adminListStmt->get_result();

mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnQuest - Admin Page</title>
    <link rel="icon" type="image/icon" href="image/favicon.svg" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/w3.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body>
    <div class="admin-page">
        <div class="admin-padding-page">
            <?php include('top_nav_admin.php'); ?>
            <div class="admin-page-grid">
                <!-- Left Side: Signup Form -->
                <div class="admin-page-container-left">
                    <form class="form-signup-admin" action="" method="POST">
                        <!-- Display error or success messages -->
                        <?php if ($signup_error): ?>
                            <p class="error-message"><?= htmlspecialchars($error_message_signup) ?></p>
                        <?php elseif (isset($signup_success)): ?>
                            <p class="success-message"><?= htmlspecialchars($signup_success) ?></p>
                        <?php endif; ?>

                        <input type="hidden" name="id" value="<?php echo isset($_POST['id']) ? htmlspecialchars($_POST['id']) : ''; ?>">
                        <input type="text" name="admin_username" placeholder="FULL NAME" value="<?php echo htmlspecialchars($username_edit); ?>">
                        <input type="email" name="admin_email" placeholder="EMAIL" value="<?php echo htmlspecialchars($email_edit); ?>" style="<?php echo $show_admin_save ? 'display:none;' : 'display:block;'; ?>">
                        <input type="password" name="admin_password" placeholder="PASSWORD" value="<?php echo htmlspecialchars($password_edit); ?>">
                        <input type="password" name="admin_confirm_password" placeholder="CONFIRM PASSWORD" style="<?php echo $show_admin_save ? 'display:none;' : 'display:block;'; ?>">
                        <button type="submit" class="admin-btn" name="admin_btn_save" style="<?php echo $show_admin_save ? 'display:block;' : 'display:none;'; ?>">Save</button>
                        <button type="submit" class="admin-btn" name="admin_btn_cancel" style="<?php echo $show_admin_save ? 'display:block;' : 'display:none;'; ?>">Cancel</button>
                        <button type="submit" class="admin-btn" name="admin_btn_signup" style="<?php echo $show_admin_save ? 'display:none;' : 'display:block;'; ?>">Signup Admin</button>
                    </form>
                </div>
                <!-- Right Side: Admin Table -->
                <div class="admin-page-container-right">
                    <h1 class="">Admin List</h1>
                    <div class="scrollable-table-admin">
                        <table class="table-admin">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>FULL NAME</th>
                                    <th>EMAIL</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <?php while ($row = $adminListResult->fetch_assoc()): ?>
                                <tbody>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td>
                                            <form action="" method="POST">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                                <button type="button" onclick="submitDeleteId(<?= $row['id'] ?>)" class="action-quiz-button action-quiz-buttons">DELETE</button>
                                                <button type="submit" class="action-quiz-button action-quiz-buttons" name="edit-details-button">EDIT</button>
                                            </form>
                                        </td>
                                    </tr>
                                </tbody>
                            <?php endwhile; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <form id="idForm" action="" method="POST" style="display: none;">
            <input type="hidden" name="id" id="id">
        </form>
    </div>
</body>
<script src="functions.js"></script>
<script>
    function submitDeleteId(id) {
        document.getElementById('id').value = id;
        document.getElementById('idForm').submit();
        // Prepare the data to send in the request
        const data = new URLSearchParams();
        data.append('id', id);
        data.append('delete-button', true);

        // Make a POST request using fetch
        fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: data.toString(),
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'error') {
                    alert(result.message); // Show the error message
                } else if (result.status === 'success') {
                    alert(result.message); // Show the success message
                    // Remove the corresponding row
                    const row = document.querySelector(`button[onclick="submitDeleteId(${id})"]`).closest('tr');
                    if (row) {
                        row.remove(); // Remove the row from the DOM
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request.');
            });
    }
</script>

</html>