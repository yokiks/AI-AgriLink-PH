<?php
/**
 * Logout handler for AI-AgriLinkPH
 */
include('includes/session.php');

logout();

header('Location: login.php?logged_out=1');
exit;

