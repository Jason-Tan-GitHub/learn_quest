<?php
include('guard.php');
include('connect.php');
$show_admin_options = false;
if (isset($_SESSION['id'])) {
    $sessionId = $_SESSION['id'];
    $user = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM user where id = $sessionId"));

    if ($user['is_admin'] == 2 || $user['is_admin'] == 1) {
        $show_admin_options = true;
    }
} else {
    // Handle the case where the user is not logged in
    header("Location: index.php");
    exit;
}
$image = "imageProfile/" . $user["profile_pic"];
?>

<div class="navUser">
    <div class="navUserLeft">
        <img src="image/learn_quest.svg" alt="Learn Quest Logo" />
    </div>
    <div class="navUserRight">
        <?php if ($show_admin_options): ?>
            <a class="link" href="admin_quiz.php" id="top_nav_manage_quiz1">Manage Quizzes</a>
            <h3>|</h3>
        <?php endif; ?>
        <a class="link" href="home.php" id="top_nav_home1">Home</a>
        <h3>|</h3>
        <div class="dropdown">
            <img src="<?php echo $image; ?>" alt="Profile Picture" class="user-img">
            <div id="dropdownMenu">
                <ul>
                    <li id="top_nav_manage_quiz2">
                        <?php if ($show_admin_options): ?>
                            <a class="link" href="admin_quiz.php">
                                <i class='bx bx-task'></i>
                                <span class="nav-item">Manage Quizzes</span>
                            </a>
                        <?php endif; ?>
                    </li>
                    <li id="top_nav_home2">
                        <a class="link" href="home.php">
                            <i class='bx bx-home-alt'></i>
                            <span class="nav-item">Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="profile.php">
                            <i class="bx bx-user-circle"></i>
                            <span class="nav-item">Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="bx bx-log-out"></i>
                            <span class="nav-item">Log out</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<script>
    window.onload = function() {
        setupProfileDropdown();
    };
</script>

<?php mysqli_close($conn); ?>