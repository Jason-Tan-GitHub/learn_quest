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
    $name = htmlspecialchars($user["username"], ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($user["email"], ENT_QUOTES, 'UTF-8');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_id'])) {
  $quizId = intval($_POST['quiz_id']);
  // Process the ID or redirect to another page
  header("Location: quiz.php?id=$quizId");
  exit;
}


$id = $user["id"];
$name = $user["username"];
$quizListStmt = $conn->prepare("SELECT quiz_id, quiz_name, category FROM quiz");
$quizListStmt->execute();
$quizListResult = $quizListStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LearnQuest - quiz Page</title>
  <link rel="icon" type="image/icon" href="image/favicon.svg" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/w3.css">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body>
  <?php include('top_nav.php'); ?>
  <div class="userPageCont">
    <div class="userPagePad">
      <div class="userContainer">
        <div class="containerQuiz">
          <a id="change-text1">HTML & CSS</a>
          <a id="change-text2">Java</a>
          <a id="change-text3">Python</a>
          <a id="change-text4">Javascript</a>
          <a id="change-text5">PHP & SQL</a>
        </div>
        <?php
        // Fetch all quizzes once
        $allQuiz = [];
        while ($row = $quizListResult->fetch_assoc()) {
          $allQuiz[] = $row;
        }
        ?>
        <div class="containerSubjects">
          <h1 id="headerTable">Pick a programming language and start learning!</h1>

          <!-- HTML & CSS Section -->
          <div id="html_css" class="HTML_CSS" style="display: none;">
            <h1>HTML & CSS</h1>
            <?php foreach ($allQuiz as $row): ?>
              <?php if (strtolower($row['category']) === 'html' || strtolower($row['category']) === 'css'): ?>
                <a href="#" onclick="submitQuizId(<?= $row['quiz_id'] ?>)"><?= htmlspecialchars($row['quiz_name']) ?></a>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>

          <!-- Java Section -->
          <div id="java" class="Java" style="display: none;">
            <h1>Java</h1>
            <?php foreach ($allQuiz as $row): ?>
              <?php if (strtolower($row['category']) === 'java'): ?>
                <a href="#" onclick="submitQuizId(<?= $row['quiz_id'] ?>)"><?= htmlspecialchars($row['quiz_name']) ?></a>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>

          <!-- Python Section -->
          <div id="python" class="Python" style="display: none;">
            <h1>Python</h1>
            <?php foreach ($allQuiz as $row): ?>
              <?php if (strtolower($row['category']) === 'python'): ?>
                <a href="#" onclick="submitQuizId(<?= $row['quiz_id'] ?>)"><?= htmlspecialchars($row['quiz_name']) ?></a>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>

          <!-- JavaScript Section -->
          <div id="javascript" class="Javascript" style="display: none;">
            <h1>JavaScript</h1>
            <?php foreach ($allQuiz as $row): ?>
              <?php if (strtolower($row['category']) === 'javascript'): ?>
                <a href="#" onclick="submitQuizId(<?= $row['quiz_id'] ?>)"><?= htmlspecialchars($row['quiz_name']) ?></a>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>

          <!-- PHP & SQL Section -->
          <div id="php_sql" class="PHP_SQL" style="display: none;">
            <h1>PHP & SQL</h1>
            <?php foreach ($allQuiz as $row): ?>
              <?php if (strtolower($row['category']) === 'php' || strtolower($row['category']) === 'sql'): ?>
                <a href="#" onclick="submitQuizId(<?= $row['quiz_id'] ?>)"><?= htmlspecialchars($row['quiz_name']) ?></a>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <form id="quizForm" action="" method="POST" style="display: none;">
    <input type="hidden" name="quiz_id" id="quiz_id">
  </form>
  <script src="functions.js"></script>
  <script>
    function submitQuizId(quizId) {
      document.getElementById('quiz_id').value = quizId;
      document.getElementById('quizForm').submit();
    }

    //Handle quiz boxes in home page for user
    function setupBoxDisplay() {
      const changeText1 = document.querySelector("#change-text1");
      const changeText2 = document.querySelector("#change-text2");
      const changeText3 = document.querySelector("#change-text3");
      const changeText4 = document.querySelector("#change-text4");
      const changeText5 = document.querySelector("#change-text5");
      const boxDefault = document.querySelector("#headerTable");
      const box1 = document.querySelector("#html_css");
      const box2 = document.querySelector("#java");
      const box3 = document.querySelector("#python");
      const box4 = document.querySelector("#javascript");
      const box5 = document.querySelector("#php_sql");

      // Function to hide all boxes and show the selected one
      function showBox(selectedBox) {
        // Hide all boxes
        boxDefault.style.display = "none";
        box1.style.display = "none";
        box2.style.display = "none";
        box3.style.display = "none";
        box4.style.display = "none";
        box5.style.display = "none";

        // Show the selected box
        selectedBox.style.display = "grid";
      }

      // Attach event listeners if elements exist
      if (changeText1 && box1) {
        changeText1.addEventListener("click", function() {
          showBox(box1);
        });
      }

      if (changeText2 && box2) {
        changeText2.addEventListener("click", function() {
          showBox(box2);
        });
      }

      if (changeText3 && box3) {
        changeText3.addEventListener("click", function() {
          showBox(box3);
        });
      }

      if (changeText4 && box4) {
        changeText4.addEventListener("click", function() {
          showBox(box4);
        });
      }

      if (changeText5 && box5) {
        changeText5.addEventListener("click", function() {
          showBox(box5);
        });
      }
    }

    // Example usage: Call the function after the DOM is fully loaded
    document.addEventListener("DOMContentLoaded", function() {
      setupBoxDisplay();
    });
  </script>
</body>

</html>