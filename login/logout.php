<?php
session_start();
session_destroy();
header("Location: /library-app/index.php");
exit;
