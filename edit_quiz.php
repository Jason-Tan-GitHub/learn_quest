<?php
include('connect.php');
session_start();

$quizId = null;
$quizName = '';
$quizDescription = '';
$category = '';
$questions = [];
$buttonText = 'Save';
$showClearButton = false;

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
        $quizName = htmlspecialchars($quiz['quiz_name'], ENT_QUOTES, 'UTF-8');
        $quizDescription = htmlspecialchars($quiz['description'], ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars($quiz['category'], ENT_QUOTES, 'UTF-8');

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

// Function to fetch options based on question_id
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

// Handle Quiz Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['quiz_name']) && isset($_POST['description']) && isset($_POST['category'])) {
        $quizName = $_POST['quiz_name'];
        $quizDescription = $_POST['description'];
        $category = $_POST['category'];

        // Validate if the quiz name already exists (excluding the current quiz)
        $stmt = $conn->prepare("SELECT quiz_id FROM quiz WHERE quiz_name = ? AND quiz_id != ?");
        $stmt->bind_param("si", $quizName, $quizId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            echo "<script>
                    alert('Quiz name already exists. Please choose a different name.');
                    window.history.back();
                  </script>";
            exit;
        }

        // Update quiz details
        $stmt = $conn->prepare("UPDATE quiz SET quiz_name = ?, description = ?, category = ? WHERE quiz_id = ?");
        $stmt->bind_param("sssi", $quizName, $quizDescription, $category, $quizId);
        $stmt->execute();
        $stmt->close();

        // Update questions and options
        foreach ($_POST['question_text'] as $index => $questionText) {
            //retrieve question_id and correct_answer based on the current question that is being updated
            $questionId = $_POST['question_id'][$index];
            $correctAnswer = $_POST['correct_answer'][$index];

            // Update question
            $stmt = $conn->prepare("UPDATE question SET question_text = ?, correct_answer = ? WHERE question_id = ?");
            $stmt->bind_param("ssi", $questionText, $correctAnswer, $questionId);
            $stmt->execute();
            $stmt->close();

            // Update options
            foreach ($_POST['option_text'][$index] as $optionIndex => $optionText) {
                //retrieve option_id based on the current question that is being updated
                $optionId = $_POST['option_id'][$index][$optionIndex];

                $stmt = $conn->prepare("UPDATE options SET option_text = ? WHERE option_id = ?");
                $stmt->bind_param("si", $optionText, $optionId);
                $stmt->execute();
                $stmt->close();
            }
        }

        echo "<script>
                alert('Quiz updated successfully.');
                window.location.href = 'admin_quiz.php';
              </script>";
        exit;
    } else {
        echo "<script>alert('Please fill in all required fields.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnQuest - Edit Quiz</title>
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
                    <input type="text" class="textBox1" name="quiz_name" placeholder="QUIZ NAME" value="<?php echo $quizName; ?>" required>
                    <textarea class="textarea-box" name="description" placeholder="DESCRIPTION" required><?php echo $quizDescription; ?></textarea>
                    <h3>Categories</h3>
                    <div class="textBox4">
                        <input type="radio" id="html" name="category" value="html" <?php echo $category == 'html' ? 'checked' : ''; ?> required>
                        <label for="html">HTML</label>

                        <input type="radio" id="css" name="category" value="css" <?php echo $category == 'css' ? 'checked' : ''; ?> required>
                        <label for="css">CSS</label>

                        <input type="radio" id="java" name="category" value="java" <?php echo $category == 'java' ? 'checked' : ''; ?> required>
                        <label for="java">JAVA</label>

                        <input type="radio" id="python" name="category" value="python" <?php echo $category == 'python' ? 'checked' : ''; ?> required>
                        <label for="python">PYTHON</label>

                        <input type="radio" id="javascript" name="category" value="javascript" <?php echo $category == 'javascript' ? 'checked' : ''; ?> required>
                        <label for="javascript">JAVASCRIPT</label>

                        <input type="radio" id="php" name="category" value="php" <?php echo $category == 'php' ? 'checked' : ''; ?> required>
                        <label for="php">PHP</label>

                        <input type="radio" id="sql" name="category" value="sql" <?php echo $category == 'sql' ? 'checked' : ''; ?> required>
                        <label for="sql">SQL</label>
                    </div>
                </div>

                <!-- Right Side: Questions and Options -->
                <div class="quiz-right-side">
                    <div class="container-quiz-questions">
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="question">
                                <input type="hidden" name="question_id[]" value="<?php echo $question['question_id']; ?>">
                                <input type="text" class="textBox2" name="question_text[]" placeholder="Question <?php echo $index + 1; ?>" value="<?php echo htmlspecialchars($question['question_text'], ENT_QUOTES, 'UTF-8'); ?>" required>

                                <?php
                                $options = fetchOptions($conn, $question['question_id']);
                                if (!empty($options)):
                                    foreach ($options as $optionIndex => $option):
                                ?>
                                        <label>
                                            <input type="radio" name="correct_answer[<?php echo $index; ?>]" value="<?php echo "Option " . $optionIndex + 1; ?>" <?php echo $question['correct_answer'] == ("Option " . $optionIndex + 1) ? 'checked' : ''; ?> required>
                                            <input type="hidden" name="option_id[<?php echo $index; ?>][]" value="<?php echo $option['option_id']; ?>">
                                            <input class="textBox3" type="text" name="option_text[<?php echo $index; ?>][]" placeholder="Option <?php echo $optionIndex + 1; ?>" value="<?php echo htmlspecialchars($option['option_text'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </label>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="quiz-right-side-bottom">
                    <button type="button" class="action-quiz-button action-quiz-buttons" onclick="window.location.href='admin_quiz.php'">Back</button>
                    <button type="submit" name="submit_form" class="action-quiz-button action-quiz-buttons">Save</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>

<?php mysqli_close($conn); ?>