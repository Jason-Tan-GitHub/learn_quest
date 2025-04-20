<?php
session_start();

session_unset();

session_destroy();


die("<script>window.location.href='index.php';</script>");