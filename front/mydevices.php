<?php
/**
 * MyDevices Plugin for GLPI
 * Main frontend file with proper GLPI layout integration
 */

include('../../../inc/includes.php');

Session::checkLoginUser();

require_once __DIR__ . '/../inc/mydevices.class.php';

// Obsługa generowania protokołu
if (isset($_POST['generate_protocol']) && isset($_POST['_glpi_csrf_token'])) {
   Session::checkCSRF('plugin_mydevices_protocol', $_POST['_glpi_csrf_token']);

   $mydevices = new MyDevices();
   $mydevices->generateProtocol();
   exit; // Ważne - generateProtocol() wysyła PDF i kończy skrypt
}

// Start GLPI page with proper header
Html::header(__('Moje urządzenia', 'mydevices'), $_SERVER['PHP_SELF'], 'tools', 'pluginmydevicesview');

// Display devices within GLPI content area
echo '<div class="container-fluid">';
$mydevices = new MyDevices();
$mydevices->display();
echo '</div>';

// Close GLPI page with proper footer
Html::footer();