<?php
if($_SESSION['status'] != "logged")
{
    die("<script>alert('Please login');window.location.href='index.php';</script>");
 
}

