<?php
require_once '../includes/auth';

logoutUser();
header('Location: login.php?logout=1');
exit;
?>