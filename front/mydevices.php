<?php
/**
 * MyDevices Plugin for GLPI
 * Main frontend file with proper GLPI layout integration
 */

include('../../../inc/includes.php');

Session::checkLoginUser();

// Start GLPI page with proper header
Html::header(__('Moje urządzenia', 'mydevices'), $_SERVER['PHP_SELF'], 'tools', 'pluginmydevicesview');

require_once __DIR__ . '/../inc/mydevices.class.php';

// Display devices within GLPI content area
echo '<div class="container-fluid">';
$mydevices = new MyDevices();
$mydevices->display();
echo '</div>';

// Close GLPI page with proper footer
Html::footer();
