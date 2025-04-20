<?php
session_start();
include('guard.php');
include('connect.php');

if (isset($_SESSION['id'])) {
    $sessionId = $_SESSION['id'];

    // Use a prepared statement to avoid SQL injection
    $stmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Apply XSS protection to user details
        $id = htmlspecialchars($user["id"], ENT_QUOTES, 'UTF-8');
    } else {
        // Handle the case where the user does not exist
        header("Location: index.php");
        exit;
    }
} else {
    // Handle the case where the user is not logged in
    header("Location: index.php");
    exit;
}


if (isset($_GET['id'])) {
    $quizId = intval($_GET['id']);

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

// Handle AJAX request to save the score
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_score') {
    if (!isset($_SESSION['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $userId = $_SESSION['id'];
    $quizId = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
    $totalCorrect = isset($_POST['score']) ? intval($_POST['score']) : 0;
    $totalPoints = $totalCorrect * 10;

    $stmtFetchTotalPoints = $conn->prepare("SELECT total_points FROM user WHERE id = ?");
    $stmtFetchTotalPoints->bind_param("i", $userId);
    $stmtFetchTotalPoints->execute();
    $resultTotalPoints = $stmtFetchTotalPoints->get_result();

    $currentPoints = 0;

    if ($row = $result->fetch_assoc()) {
        $currentPoints = $row['total_points']; // Current total points
    }

    $stmtFetchTotalPoints->close();

    if ($quizId <= 0 || $totalCorrect < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        exit;
    }

    $finalPoints = $currentPoints + $totalPoints;

    // Insert the score into the history table
    $stmt = $conn->prepare("INSERT INTO history (total_correct_answers, id, quiz_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $totalCorrect, $userId, $quizId);

    $stmtPoints = $conn->prepare("UPDATE user SET total_points = total_points + ? WHERE id = ?");
    $stmtPoints->bind_param("ii", $finalPoints, $userId);


    if ($stmt->execute() && $stmtPoints->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Score saved successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }

    $stmtPoints->close();
    $stmt->close();
    exit;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnQuest - Quiz Page</title>
    <link rel="icon" type="image/icon" href="image/favicon.svg" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/w3.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">

</head>

<body>
    <div class="userPageCont">
        <div class="quiz-page-padding">
            <div class="quiz-container-placement">
                <h1><?= htmlspecialchars($quiz['quiz_name']) ?></h1>
                <div class="quiz-description">
                    <p><?= htmlspecialchars($quiz['description']) ?></p>
                </div>
                <button onclick="startQuiz()" class="btn-action-quiz-button action-quiz-button action-quiz-buttons">Start Quiz</button>
                <div class="quiz-container">
                    <!-- $index is a temporary variable that indicates the current question's position in the $questions array, starting from 0 -->
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-frame" id="question_<?= $index ?>" style="display: <?= $index === 0 ? 'block' : 'none'; ?>;">
                            <h3><?= htmlspecialchars($question['question_text']) ?></h3>
                            <?php $options = fetchOptions($conn, $question['question_id']); ?>
                            <?php foreach ($options as $optionIndex => $option): ?>
                                <label>
                                    <input type="radio" name="question_<?= $index ?>" value="Option <?= $optionIndex + 1 ?>" required>
                                    <?= htmlspecialchars($option['option_text']) ?>
                                </label><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        function startQuiz() {
            const QD = document.querySelector(".quiz-description");
            const QC = document.querySelector(".quiz-container");
            const btn = document.querySelector(".btn-action-quiz-button");

            QD.classList.toggle("active");
            QC.classList.toggle("active");
            btn.classList.toggle("active");
        }

        let currentQuestion = 0;
        const totalQuestions = <?= count($questions) ?>;
        const correctAnswers = <?= json_encode(array_column($questions, 'correct_answer')) ?>;
        let score = 0;

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
                    if (input.checked) {
                        score++;
                    }
                } else if (input.checked) {
                    label.classList.add('incorrect');
                }
                // Disable all radio buttons
                input.disabled = true;
            });

            // Move to the next question after a short delay
            setTimeout(() => {
                document.getElementById(`question_${index}`).style.display = 'none';
                currentQuestion++;
                if (currentQuestion < totalQuestions) {
                    document.getElementById(`question_${currentQuestion}`).style.display = 'block';
                } else {
                    completeQuiz();
                    // alert(`Quiz completed! Your score is ${score} out of ${totalQuestions}.`);
                    // window.location.href = "home.php";
                }
            }, 1500);
        }

        function completeQuiz() {
            alert(`Quiz completed! Your score is ${score} out of ${totalQuestions}.`);
            saveScore(score, <?= $quizId ?>);
        }

        function saveScore(score, quizId) {
            const data = new URLSearchParams();
            data.append('action', 'save_score');
            data.append('score', score);
            data.append('quiz_id', quizId);

            //Fetch json message from server
            fetch("", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: data.toString(),
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        alert(result.message);
                        window.location.href = "home.php";
                    } else {
                        alert(`Error: ${result.message}`);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred while saving your score.");
                });
        }

        // Automatically check the answer when an option is selected
        document.querySelectorAll('input[type="radio"]').forEach(input => {
            input.addEventListener('change', function() {
                checkAnswer(currentQuestion);
            });
        });
    </script>
</body>

</html>