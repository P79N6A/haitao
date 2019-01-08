<?php
session_start();
$_SESSION['isMobile']=1;
header("Location: /index.php");