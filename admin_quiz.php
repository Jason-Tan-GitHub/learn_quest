<?php
session_start();
include('guard.php');
include('connect.php');
$show_admin_options = false;
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

        if ($user['is_admin'] == 2) {
            $show_admin_options = true;
        } else {
            $show_admin_options = false;
        }
    }
} else {
    header("Location: index.php");
    exit;
}

$quizListStmt = $conn->prepare("SELECT quiz_id, quiz_name FROM quiz");
$quizListStmt->execute();
$quizListResult = $quizListStmt->get_result();

//delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_button_quizzes']) && isset($_POST['quiz_id'])) {
        $quizId = intval($_POST['quiz_id']); // Sanitize the input to prevent SQL injection

        // Start a transaction
        $conn->begin_transaction();

        try {
            // Delete options related to the questions of the quiz
            $deleteOptionsStmt = $conn->prepare("DELETE FROM options WHERE question_id IN (SELECT question_id FROM question WHERE quiz_id = ?)");
            $deleteOptionsStmt->bind_param("i", $quizId);
            if (!$deleteOptionsStmt->execute()) {
                throw new Exception("Error deleting options: " . $deleteOptionsStmt->error);
            }
            $deleteOptionsStmt->close();

            // Delete questions related to the quiz
            $deleteQuestionsStmt = $conn->prepare("DELETE FROM question WHERE quiz_id = ?");
            $deleteQuestionsStmt->bind_param("i", $quizId);
            if (!$deleteQuestionsStmt->execute()) {
                throw new Exception("Error deleting questions: " . $deleteQuestionsStmt->error);
            }
            $deleteQuestionsStmt->close();

            // Delete the quiz
            $deleteQuizStmt = $conn->prepare("DELETE FROM quiz WHERE quiz_id = ?");
            $deleteQuizStmt->bind_param("i", $quizId);
            if (!$deleteQuizStmt->execute()) {
                throw new Exception("Error deleting quiz: " . $deleteQuizStmt->error);
            }
            $deleteQuizStmt->close();

            // Commit the transaction
            $conn->commit();

            echo "<script>alert('Record deleted successfully'); window.location.href = 'admin_quiz.php';</script>";
        } catch (Exception $e) {
            // Rollback the transaction if any query fails
            $conn->rollback();
            echo "<script>alert('Error deleting record: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}


//edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_details_button_quizzes']) && isset($_POST['quiz_id'])) {
        $quizId = intval($_POST['quiz_id']); // Sanitize the input to prevent SQL injection
        header("Location: edit_quiz.php?quiz_id=$quizId");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnQuest - Quiz Admin Page</title>
    <link rel="icon" type="image/icon" href="image/favicon.svg" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/w3.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body>
    <div class="admin-page">
        <div class="admin-padding">
            <?php include('top_nav_admin.php'); ?>
            <div class="admin-box-quiz">
                <div class="container-admin-quiz">
                    <div class="scrollable-table-quiz-admin">
                        <table class="table-admin-quiz">
                            <thead>
                                <tr>
                                    <th>Quiz Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <?php while ($row = $quizListResult->fetch_assoc()): ?>
                                <tbody>
                                    <tr>
                                        <td><?= htmlspecialchars($row['quiz_name']) ?></td>
                                        <td>
                                            <form action="" method="POST">
                                                <input type="hidden" name="quiz_id" value="<?= htmlspecialchars($row['quiz_id']) ?>">
                                                <button type="submit" class="action-quiz-button action-quiz-buttons" name="delete_button_quizzes" style="<?php echo $show_admin_options ? ' ' : 'display:none;'; ?>">DELETE</button>
                                                <button type="submit" class="action-quiz-button action-quiz-buttons" name="edit_details_button_quizzes">EDIT</button>
                                            </form>
                                        </td>
                                    </tr>
                                </tbody>
                            <?php endwhile; ?>
                        </table>
                    </div>
                    <button class="Create action-quiz-button action-quiz-buttons" onclick="window.location.href='create_quiz.php';">Create</button>
                </div>
            </div>
        </div>
    </div>
</body>
<script src="functions.js"></script>

</html>