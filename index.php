<?php
require 'includes/init.php';

if (!empty($_SESSION['user'])) {
    header('Location: ' . halaman_role($_SESSION['user']['role']));
} else {
    header('Location: login.php');
}
exit;