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

// Retrieve the quiz_id from the URL query string
if (isset($_GET['quiz_id'])) {
    $quizId = intval($_GET['quiz_id']);

    // Fetch quiz details
    $stmt = $conn->prepare("SELECT * FROM quiz WHERE quiz_id = ?");
    $stmt->bind_param("i", $quizId);
    $stmt->execute();
    $quizResult = $stmt->get_result();
    $quiz = $quizResult->fetch_assoc();
    $stmt->close();

    if ($quiz) {
        // Fetch questions for the quiz
        $stmt = $conn->prepare("SELECT * FROM question WHERE quiz_id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $questionsResult = $stmt->get_result();
        $questions = $questionsResult->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        echo "Quiz not found.";
        exit;
    }
} else {
    echo "No quiz selected.";
    exit;
}

//Retrieves Option based on corresponding question_id
function fetchOptions($conn, $questionId)
{
    $stmt = $conn->prepare("SELECT * FROM options WHERE question_id = ?");
    $stmt->bind_param("i", $questionId);
    $stmt->execute();
    $optionsResult = $stmt->get_result();
    $options = $optionsResult->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $options;
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
    <div class="quiz-history-container">
        <div class="quiz-history-padding">
            <div class="view-history-table">
                <!-- $index is a temporary variable that indicates the current question's position in the $questions array, starting from 0 -->
                <?php foreach ($questions as $index => $question): ?>
                    <h3><?= htmlspecialchars($question['question_text']) ?></h3>
                    <?php $options = fetchOptions($conn, $question['question_id']); ?>
                    <?php foreach ($options as $optionIndex => $option): ?>
                        <label>
                            <input type="radio" name="question_<?= $index ?>" value="Option <?= $optionIndex + 1 ?>" required>
                            <?= htmlspecialchars($option['option_text']) ?>
                        </label><br>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <button class="quiz-history-button quiz-history-buttons quiz-history-back" onclick="window.location.href='profile.php'">Back</button>
            </div>
        </div>
    </div>
    <script>
        const correctAnswers = <?= json_encode(array_column($questions, 'correct_answer')) ?>;
        let currentQuestion = 0;

        // Automatically check the answer when an option is selected
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input[type="radio"]').forEach(input => {
                input.addEventListener('change', function() {
                    const questionName = input.name; 
                    const index = questionName.split('_')[1]; 
                    checkAnswer(index);
                });
            });
        });

        function checkAnswer(index) {
            const selectedOption = document.querySelector(`input[name="question_${index}"]:checked`);
            if (!selectedOption) {
                alert("Please select an answer.");
                return;
            }

            const selectedValue = selectedOption.value;
            const correctAnswer = correctAnswers[index];

            // Highlight the correct answer
            document.querySelectorAll(`input[name="question_${index}"]`).forEach(input => {
                const label = input.parentElement;
                if (input.value === correctAnswer) {
                    label.classList.add('correct');
                } else if (input.checked) {
                    label.classList.add('incorrect');
                }
                // Disable all radio buttons
                input.disabled = true;
            });
        }
    </script>
</body>

</html>