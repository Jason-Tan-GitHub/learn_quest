<?php
session_start();
include("connect.php");

$countQuery = "SELECT COUNT(id) as total_count FROM user where is_admin = 0";
$result = $conn->query($countQuery);
$count = 0;
if ($result) {
  $row = $result->fetch_assoc();
  $count = $row['total_count'];
}


// Initialize variables
$error_message_login = "";  // For login form errors
$error_message_signup = ""; // For signup form errors
$signup_error = false;      // To track if there's an error on signup
$show_login_form = false;   // To show login form, if true, keeps the form opened
$show_signup_form = false;  // To show signup form, if true, keeps the form opened

// Handle Login Process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loginBtn'])) {
  if (!empty($_POST['emailLogin']) && !empty($_POST['passwordLogin'])) {
    $email = $_POST['emailLogin'];
    $password = $_POST['passwordLogin'];

    // Search user table
    $query_login = "SELECT * FROM user WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($query_login);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      // Found in user table
      $user = $result->fetch_assoc();
      if (password_verify($password, $user['password'])) {
        $_SESSION['id'] = $user['id'];
        $_SESSION['status'] = "logged";

        // Check the is_admin flag
        if ($user['is_admin'] == 1 || $user['is_admin'] == 2) {
          header('Location: admin_quiz.php');
          exit();
        } else {
          // Regular user
          header('Location: home.php');
          exit();
        }
      } else {
        $error_message_login = "Invalid email or password.";
        $show_login_form = true;
      }
    } else {
      $error_message_login = "Invalid email or password.";
      $show_login_form = true;
    }
  } else {
    $error_message_login = "Please fill in all fields.";
    $show_login_form = true;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signupBtn'])) {
  // Check required fields
  if (
    !empty($_POST['username']) &&
    !empty($_POST['email']) &&
    !empty($_POST['password']) &&
    !empty($_POST['confirm_password'])
  ) {
    // Sanitize and validate inputs
    $username = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Initialize variables
    $fullName = $username; // Assuming fullName is same as username

    if ($email === false) {
      $error_message_signup = "Please enter a valid email address.";
      $signup_error = true;
      $show_signup_form = true; // Keep signup form open
    } elseif ($password !== $confirm_password) {
      $error_message_signup = "Passwords do not match.";
      $signup_error = true;
      $show_signup_form = true;
    } else {
      // Check if username or email already exists
      $userCheckStmt = $conn->prepare("SELECT username, email FROM user WHERE username = ? OR email = ?");
      if ($userCheckStmt) {
        $userCheckStmt->bind_param("ss", $username, $email);
        $userCheckStmt->execute();
        $userCheckStmt->store_result();

        if ($userCheckStmt->num_rows > 0) {
          $userCheckStmt->bind_result($existingUsername, $existingEmail);
          $userCheckStmt->fetch();

          if ($existingUsername === $username) {
            $error_message_signup = "Username already exists. Please choose a different username.";
          }
          if ($existingEmail === $email) { // Changed to 'if' to allow multiple errors
            $error_message_signup .= " Email already exists. Please use a different email.";
          }

          $signup_error = true;
          $show_signup_form = true; // Keep signup form open
        } else {
          // Insert new user
          $hashed_password = password_hash($password, PASSWORD_BCRYPT);
          $insertStmt = $conn->prepare("INSERT INTO user (email, password, username) VALUES (?, ?, ?)");
          if ($insertStmt) {
            // Corrected bind_param without extra $
            $insertStmt->bind_param("sss", $email, $hashed_password, $username);

            if ($insertStmt->execute()) {
              $_SESSION['username'] = $username;
              $_SESSION['id'] = $conn->insert_id; // Get the newly inserted ID
              $_SESSION['status'] = "logged";
              header('Location: home.php');
              exit();
            } else {
              // Log detailed error for debugging
              error_log("Database Insert Error: " . $insertStmt->error);
              $error_message_signup = "An error occurred during registration. Please try again.";
              $signup_error = true;
              $show_signup_form = true;
            }
            $insertStmt->close();
          } else {
            // Log detailed error for debugging
            error_log("Database Prepare Error: " . $conn->error);
            $error_message_signup = "An internal error occurred. Please try again later.";
            $signup_error = true;
            $show_signup_form = true;
          }
        }
        $userCheckStmt->close();
      } else {
        // Log detailed error for debugging
        error_log("Database Prepare Error: " . $conn->error);
        $error_message_signup = "An internal error occurred. Please try again later.";
        $signup_error = true;
        $show_signup_form = true;
      }
    }
  } else {
    $error_message_signup = "Please fill in all required fields.";
    $signup_error = true;
    $show_signup_form = true;
  }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Learn Quest</title>
  <link rel="icon" type="image/icon" href="image/favicon.svg" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body>
  <div class="nav">
    <div class="navLeft">
      <img src="image/learn_quest.svg" alt="Learn Quest Logo" />
    </div>
    <div class="navRight">
      <!-- Navigation links -->
      <a class="link" href="index.php">Home</a>
      <h3>|</h3>
      <a class="link" href="#subjects">Subjects</a>
      <h3>|</h3>
      <a class="link" href="#aboutUs">About Us</a>
      <h3>|</h3>
      <a class="link" onclick="openLoginForm()" id="login">Login</a>
      <!-- Login and Signup Popup -->
    </div>
    <div class="navRightSidebar">
      <i class='bx bx-menu bx-lg' onclick="openNav()"></i>
      <div id="mySidenav" class="sidenav">
        <i class='bx bx-x bx-lg' onclick="closeNav()"></i>
        <img src="image/home.svg" alt="">
        <a href="#" class="b1">Home</a>
        <img src="image/subjects.svg" alt="A Coding Symbol">
        <a href="#" class="b1">Subjects</a>
        <img src="image/about_us.svg" alt="About Us">
        <a href="#" class="b1">About us</a>
        <img src="image/login.svg" alt="Login">
        <a onclick="openLoginForm()" class="b1">Login</a>
      </div>
    </div>
    <div class="form-popup-login" id="myLoginForm" style="<?php echo $show_login_form ? 'display:block;' : 'display:none;'; ?>">
      <form class="form-container-login" action="" method="POST">
        <i onclick="closeLoginForm()" class='bx bx-x bx-lg' id="exitLogin"></i>
        <h1>Welcome Back Learner!</h1>
        <h6>Enter your credentials to log in</h6>

        <!-- Error message for login -->
        <?php if (!empty($error_message_login)): ?>
          <p class="error-message"><?= htmlspecialchars($error_message_login) ?></p>
        <?php endif; ?>

        <input type="email" placeholder="EMAIL" name="emailLogin" value="<?php echo isset($_POST['emailLogin']) ? htmlspecialchars($_POST['emailLogin'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        <input type="password" placeholder="PASSWORD" name="passwordLogin">
        <a href="reset-page.php"><span>Forgot Password?</span></a>
        <button type="submit" class="btn" name="loginBtn">Login</button>
        <a onclick="openSignupForm()">Don't have an account? <span>Sign up now!</span></a>
      </form>
    </div>
  </div>
  <!-- Section 1 container -->
  <div class="ScrollContainer back">
    <section1>
      <h1 class="large sec1Header">
        Unleash your knowledge, Master your skills
      </h1>
      <p class="big sec1P">
        Embark on your coding journey with easy to learn coding topics
      </p>
      <button class="sec1Button b1 solid" onclick="openSignupForm()" id="signup">
        Sign up today!
      </button>
      <!-- Login and Signup Popup -->
      <div class="form-popup-signup" id="mySignupForm" style="<?php echo $show_signup_form ? 'display:block;' : 'display:none;'; ?>">
        <form action="" class="form-container-signup" method="POST">
          <i onclick="closeSignupForm()" class='bx bx-x bx-lg' id="exitLogin"></i>
          <h1>Sign up</h1>

          <!-- Error message for signup -->
          <?php if (!empty($error_message_signup)): ?>
            <p class="error-message"><?= htmlspecialchars($error_message_signup) ?></p>
          <?php endif; ?>

          <input type="text" placeholder="USERNAME" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>">
          <input type="email" placeholder="EMAIL" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>">
          <input type="password" placeholder="PASSWORD" name="password">
          <input type="password" placeholder="CONFIRM PASSWORD" name="confirm_password">

          <button type="submit" class="btn" name="signupBtn">Signup</button>
        </form>
      </div>
      <div class="sec1Image">
        <img class="Img1" src="gif/learn_quest_1.gif" alt="" />
      </div>
    </section1>
    <!-- Section 2 container -->
    <section2>
      <div class="sec2Header1">
        <h1 class="large">
          <b><?php echo $count ?></b> active users!
        </h1>
        <p class="big sec2P">
          Creating brighter futures and nurturing better understandings in the world of code.
        </p>
      </div>
      <div class="sec2Header2">
        <img src="gif/users.gif" alt="Picture of a computer">
      </div>
    </section2>
    <!-- Section 3 container -->
    <section3 id="subjects">
      <div class="sec3Grids">
        <div class="sec3Placement1">
          <div class="sec3Title">
            <h1>Learn and Master....</h1>
          </div>
          <div class="tag-list">
            <div class="loop-slider" style="--duration:15951ms; --direction:normal;">
              <div class="inner">
                <div class="tag"><span>#</span> JavaScript</div>
                <div class="tag"><span>#</span> HTML</div>
                <div class="tag"><span>#</span> CSS</div>
                <div class="tag"><span>#</span> PHP</div>
                <div class="tag"><span>#</span> Java</div>
                <div class="tag"><span>#</span> JavaScript</div>
                <div class="tag"><span>#</span> HTML</div>
                <div class="tag"><span>#</span> CSS</div>
                <div class="tag"><span>#</span> PHP</div>
                <div class="tag"><span>#</span> Java</div>
              </div>
            </div>
            <div class="loop-slider" style="--duration:19260ms; --direction:reverse;">
              <div class="inner">
                <div class="tag"><span>#</span> JavaScript</div>
                <div class="tag"><span>#</span> HTML</div>
                <div class="tag"><span>#</span> CSS</div>
                <div class="tag"><span>#</span> PHP</div>
                <div class="tag"><span>#</span> Java</div>
                <div class="tag"><span>#</span> JavaScript</div>
                <div class="tag"><span>#</span> HTML</div>
                <div class="tag"><span>#</span> CSS</div>
                <div class="tag"><span>#</span> PHP</div>
                <div class="tag"><span>#</span> Java</div>
              </div>
            </div>
            <div class="fade"></div>
          </div>
        </div>
        <div class="sec3Placement2">
          <img src="gif/learn_quest_2.gif" alt="">
          <h1>Anytime... Anywhere...</h1>
        </div>
      </div>
    </section3>
    <footer>
      <div class="containerFooter">
        <div class="containerFoot1">
          <img src="image/learn_quest.svg" alt="">
          <hr>
          <div class="flexFoot1">
            <i class="fa-regular fa-envelope"></i>
            <p>learnquest@gmail.com</p>
          </div>
          <div class="flexFoot1">
            <i class="fa-solid fa-phone"></i>
            <p> (+601) 1234567890</p>
          </div>
        </div>
        <div class="containerFoot2">
          <div class="containerSocials">
            <a href="https://www.w3schools.com/html/default.asp" target="_blank"><i class="fa-brands fa-instagram fa-2x"></i></a>
            <a href="https://www.w3schools.com/sql/default.asp" target="_blank"><i class="fa-brands fa-facebook fa-2x"></i></a>
            <a href="https://www.w3schools.com/js/default.asp" target="_blank"><i class="fa-brands fa-x-twitter fa-2x"></i></a>
          </div>
          <hr>
          <div class="linksIndex">
            <div class="linksContainer">
              <a href="index.php" id="home">Home</a>
              <a href="#subjects">Subjects</a>
              <a href="#aboutUs">About us</a>
            </div>
            <div class="linksContainer">
              <a onclick="openLoginForm()">Login</a>
              <a onclick="openSignupForm()">Signup</a>
            </div>
          </div>
        </div>
      </div>
      <hr class="lengthHr">
      <p>LearnQuest © 2024</p>
    </footer>
  </div>
  <script>
    /* Index page */
    function openLoginForm() {
      document.getElementById("myLoginForm").style.display = "block";
      closeNav();
    }

    function closeLoginForm() {
      document.getElementById("myLoginForm").style.display = "none";
    }

    function openSignupForm() {
      document.getElementById("myLoginForm").style.display = "none";
      document.getElementById("mySignupForm").style.display = "block";
    }

    function closeSignupForm() {
      document.getElementById("mySignupForm").style.display = "none";
    }

    /* Sidebar */
    function openNav() {
      document.getElementById("mySidenav").style.width = "200px";
      document.getElementById("mySidenav").style.border = "2px solid #000000";
    }

    function closeNav() {
      document.getElementById("mySidenav").style.width = "0";
      document.getElementById("mySidenav").style.border = "0px";
    }

    document.addEventListener("click", function(event) {
      // Check if the click is outside the sidenav and the menu button
      if (
        !event.target.closest("#mySidenav") &&
        !event.target.closest(".bx-menu")
      ) {
        closeNav();
      }
    });
  </script>
</body>

</html>
