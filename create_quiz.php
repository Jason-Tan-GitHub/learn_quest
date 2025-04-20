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

    if ($user) {
        $id = htmlspecialchars($user["id"]);
    }
} else {
    header("Location: index.php");
    exit;
}


// Handle submission of quiz form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_form'])) {
    if (!empty($_POST['quiz_name']) && !empty($_POST['description']) && !empty($_POST['category']) && !empty($_POST['quantity'])) {
        $quizName = $_POST['quiz_name'];
        $quizDescription = $_POST['description'];
        $category = $_POST['category'];
        $stmt = $conn->prepare("SELECT COUNT(*) FROM quiz WHERE quiz_name = ?");
        $stmt->bind_param("s", $quizName);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            echo "<script>alert('Quiz name already exists. Please choose a different name.');window.location.href = 'create_quiz.php';</script>";
            return;
        }

        $stmt = $conn->prepare("INSERT INTO quiz (quiz_name, description, category) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $quizName, $quizDescription, $category);
        $stmt->execute();
        $quizId = $stmt->insert_id;
        $stmt->close();

        $numQuestions = intval($_POST['quantity']);
        $numOptions = 4;

        for ($q = 1; $q <= $numQuestions; $q++) {
            $question = $_POST["question_$q"] ?? '';
            $correctAnswer = $_POST["correct_answer_$q"] ?? '';

            if (empty($question) || empty($correctAnswer)) {
                echo "<script>alert('Please fill in all question and correct answer fields for Question $q.');</script>";
                return;
            }

            $stmt = $conn->prepare("INSERT INTO question (question_text, correct_answer, quiz_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $question, $correctAnswer, $quizId);
            $stmt->execute();
            $questionId = $stmt->insert_id;
            $stmt->close();

            for ($o = 1; $o <= $numOptions; $o++) {
                $option = $_POST["answer_{$q}_option_$o"] ?? '';

                if (empty($option)) {
                    echo "<script>alert('Please fill in all options for Question $q.');</script>";
                    return;
                }

                $stmt = $conn->prepare("INSERT INTO options (option_text, question_id) VALUES (?, ?)");
                $stmt->bind_param("si", $option, $questionId);
                $stmt->execute();
                $stmt->close();
            }
        }

        echo "<script>
        alert('Quiz and questions added successfully.');
        window.location.href = 'admin_quiz.php';
        </script>";
        exit;
    } else {
        echo "<script>alert('Please fill in the quiz name, description, category, and number of questions.');</script>";
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnQuest - Create Quiz</title>
    <link rel="icon" type="image/icon" href="image/favicon.svg" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/w3.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body>
    <div class="admin-page">
        <div class="admin-padding2">
            <form action="" method="POST" id="quizForm" class="quiz-create-container">
                <!-- Left Side: Quiz Name and Description -->
                <div class="quiz-left-side">
                    <input type="text" class="textBox1" name="quiz_name" placeholder="QUIZ NAME" required>
                    <textarea class="textarea-box" name="description" placeholder="DESCRIPTION" required></textarea>
                    <h3>Categories</h3>
                    <div class="textBox4">
                        <input type="radio" id="html" name="category" value="html" required>
                        <label for="html">HTML</label>

                        <input type="radio" id="css" name="category" value="css" required>
                        <label for="css">CSS</label>

                        <input type="radio" id="java" name="category" value="java" required>
                        <label for="java">JAVA</label>

                        <input type="radio" id="python" name="category" value="python" required>
                        <label for="python">PYTHON</label>

                        <input type="radio" id="javascript" name="category" value="javascript" required>
                        <label for="javascript">JAVASCRIPT</label>

                        <input type="radio" id="php" name="category" value="php" required>
                        <label for="php">PHP</label>

                        <input type="radio" id="sql" name="category" value="sql" required>
                        <label for="sql">SQL</label>
                    </div>
                    <label for="quantity">Number of questions:
                        <input class="number-box" type="number" id="quantity" name="quantity" min="1" value="1" required>
                    </label>
                </div>

                <!-- Right Side: Questions and Options -->
                <div class="quiz-right-side">
                    <div class="container-quiz-questions">
                        <!-- Questions will be generated here -->
                    </div>
                </div>
                <div class="quiz-right-side-bottom">
                    <button type="button" class="action-quiz-button action-quiz-buttons" onclick="window.location.href='admin_quiz.php'">Back</button>
                    <button type="button" id="clearButton" class="action-quiz-button action-quiz-buttons">Clear</button>
                    <button type="submit" name="submit_form" class="action-quiz-button action-quiz-buttons">Submit</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('clearButton').addEventListener('click', function() {
            document.getElementById('quizForm').reset();
            generateQuestions(); // Regenerate questions after reset
        });

        /* Add Questions */
        function generateQuestions() {
            const totalQuestions = document.getElementById("quantity").value;
            const containerQuiz = document.querySelector(".container-quiz-questions");
            containerQuiz.innerHTML = ""; // Clear existing questions

            for (let q = 1; q <= totalQuestions; q++) {
                const questionDiv = document.createElement("div");
                questionDiv.classList.add("question");

                const questionInput = document.createElement("input");
                questionInput.type = "text";
                questionInput.classList.add("textBox2");
                questionInput.name = `question_${q}`;
                questionInput.placeholder = `Question ${q}`;
                questionInput.required = true;
                questionDiv.appendChild(questionInput);

                for (let o = 1; o <= 4; o++) {
                    const label = document.createElement("label");

                    const radioInput = document.createElement("input");
                    radioInput.type = "radio";
                    radioInput.name = `correct_answer_${q}`;
                    radioInput.value = `Option ${o}`;
                    radioInput.required = true;
                    label.appendChild(radioInput);

                    const optionInput = document.createElement("input");
                    optionInput.type = "text";
                    optionInput.classList.add("textBox3");
                    optionInput.name = `answer_${q}_option_${o}`;
                    optionInput.placeholder = `Option ${o}`;
                    optionInput.required = true;
                    label.appendChild(optionInput);

                    questionDiv.appendChild(label);
                }

                containerQuiz.appendChild(questionDiv);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('quantity').addEventListener('input', generateQuestions);
            generateQuestions(); // Generate initial set of questions
        });
    </script>
</body>

</html>

<?php mysqli_close($conn); ?>