<?php
include('guard.php');
include('connect.php');
$show_admin_options = false;
if (isset($_SESSION['id'])) {
    $sessionId = $_SESSION['id'];
    $user = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM user where id = $sessionId"));

    if ($user['is_admin'] == 2) {
        $show_admin_options = true;
    } else {
        $show_admin_options = false;
    }
} else {
    // Handle the case where the user is not logged in
    header("Location: index.php");
    exit;
}

// Determine the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Set the text based on the current page
$page_title = '';
switch ($current_page) {
    case 'admin_quiz.php':
        $page_title = 'Manage Quizzes';
        break;
    case 'admin_page.php':
        $page_title = 'Manage Admins';
        break;
}


?>

<div class="admin-header">
    <div class="navLeft-admin">
        <h1><?= htmlspecialchars($page_title) ?></h1>
    </div>
    <div class="hiddenAdmin">
        <div class="navRight-admin">
            <a href="home.php" class="b2 solid">
                <span>User Home Page</span>
            </a>
            <a href="admin_page.php" class="b2 solid" style="<?php echo $show_admin_options ? 'display:block;' : 'display:none;'; ?>">
                <span>Manage Admins</span>
            </a>
            <a href="admin_quiz.php" class="b2 solid" style="<?php echo $show_admin_options ? 'display:block;' : 'display:none;'; ?>">
                <span>Manage Quizzes</span>
            </a>
            <a href="logout.php" class="b2 solid">
                <span>Log out</span>
            </a>
        </div>
    </div>
    <div class="hiddenAdminDropdown">
        <div class="navRight-admin">
            <div class="dropdown">
                <i class='bx bx-menu bx-lg hamburger'></i>
                <div id="dropdownMenuAdmin">
                    <ul>
                        <li>
                            <a href="home.php">
                                <i class="bx bx-home-alt bx-md"></i>
                                <span class="nav-item">User Home Page</span>
                            </a>
                        </li>
                        <li>
                            <a href="admin_page.php" style="<?php echo $show_admin_options ? 'display:block;' : 'display:none;'; ?>">
                                <i class="bx bx-user-circle bx-md"></i>
                                <span class="nav-item">Manage Admins</span>
                            </a>
                        </li>
                        <li>
                            <a href="admin_quiz.php" style="<?php echo $show_admin_options ? 'display:block;' : 'display:none;'; ?>">
                                <i class='bx bx-task bx-md'></i>
                                <span class="nav-item">Manage Quizzes</span>
                            </a>
                        </li>
                        <li>
                            <a href="logout.php">
                                <i class="bx bx-log-out bx-md"></i>
                                <span class="nav-item">Log out</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    window.onload = function() {
        dropDownAdmin();
    };
</script>

<?php mysqli_close($conn); ?>