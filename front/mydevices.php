<?php
/**
 * Główny plik frontendu wtyczki MyDevices.
 *
 * Odpowiada za wyświetlanie widoku "Moje Urządzenia" oraz
 * obsługę żądania generowania protokołu PDF.
 */

include('../../../inc/includes.php');

Session::checkLoginUser();

require_once __DIR__ . '/../inc/mydevices.class.php';

// Obsługa żądania generowania protokołu PDF
if (isset($_POST['generate_protocol']) && isset($_POST['_glpi_csrf_token'])) {
   Session::checkCSRF('plugin_mydevices_protocol', $_POST['_glpi_csrf_token']);

   $mydevices = new MyDevices();
   $mydevices->generateProtocol();
   exit;
}

// Wyświetlanie strony w standardowym layoucie GLPI
Html::header(__('Moje urządzenia', 'mydevices'), $_SERVER['PHP_SELF'], 'tools', 'pluginmydevicesview');

echo '<div class="container-fluid">';
$mydevices = new MyDevices();
$mydevices->display();
echo '</div>';

Html::footer();