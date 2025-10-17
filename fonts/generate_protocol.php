<?php
// Nowy generator protokołu zdawczo-odbiorczego PDF z użyciem Dompdf
include('../../../inc/includes.php');
require_once __DIR__ . '/../inc/mydevices.class.php';

// Dołączenie autoloadera Composera
$autoload_path = realpath(__DIR__ . '/../vendor/autoload.php');
if (!$autoload_path) {
    die("FATAL ERROR: vendor/autoload.php not found. Please run 'composer install' in the plugin directory.");
}
require_once $autoload_path;

use Dompdf\Dompdf;
use Dompdf\Options;

// Bezpieczeństwo: Sprawdzenie uprawnień
if (!Session::haveRight('config', UPDATE)) {
    Html::displayRightError();
}

// Pobranie danych
$uid = (int) Session::getLoginUserID();
$user = new User();
$user->getFromDB($uid);
$username = $user->getName();

$mydevices = new MyDevices();
$all_assets_raw = $mydevices->getAllAssetsForUser($uid);
$assets_to_print = $all_assets_raw;

// Generowanie HTML dla PDF
ob_start();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Protokół Zdawczo-Odbiorczy</title>
    <style>
        @font-face {
            font-family: 'Lato';
            src: url('<?php echo Plugin::getPhpDir('mydevices'); ?>/fonts/Lato-Regular.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        body {
            font-family: 'Lato', sans-serif;
            font-size: 10px;
            color: #333;
            margin: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table, .content-table {
            margin-bottom: 20px;
        }
        .content-table th, .content-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .content-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo {
            max-width: 250px;
            max-height: 100px;
        }
        h1 {
            font-size: 20px;
            margin: 0;
            text-align: right;
        }
        .info {
            font-size: 11px;
            text-align: right;
            line-height: 1.5;
        }
        .signatures {
            position: fixed;
            bottom: 40px;
            left: 40px;
            right: 40px;
            width: auto;
        }
        .signature-col {
            display: inline-block;
            width: 48%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #999;
            margin-top: 70px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <?php
                $logo_path = Plugin::getPhpDir('mydevices') . '/logo/big_uzp.png';
                if (file_exists($logo_path) && is_readable($logo_path)) {
                    $logo_data = base64_encode(file_get_contents($logo_path));
                    echo '<img src="data:image/png;base64,' . $logo_data . '" class="logo" alt="Logo">';
                } else {
                    echo '<div style="color:red; border:1px solid red; padding:10px;">LOGO NOT FOUND at: ' . Html::entities_deep($logo_path) . '</div>';
                }
                ?>
            </td>
            <td style="width: 50%;">
                <h1>Protokół Zdawczo-Odbiorczy</h1>
                <div class="info">
                    <div><strong>Data wygenerowania:</strong> <?php echo date('Y-m-d'); ?></div>
                    <div><strong>Pracownik:</strong> <?php echo Html::entities_deep($username); ?></div>
                </div>
            </td>
        </tr>
    </table>

    <table class="content-table">
        <thead>
            <tr>
                <th>Nazwa</th>
                <th>Model</th>
                <th>Typ</th>
                <th>Numer seryjny</th>
                <th>Status</th>
                <th>Lokalizacja</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($assets_to_print)): ?>
                <tr><td colspan="6" style="text-align: center;">Brak urządzeń do wyświetlenia.</td></tr>
            <?php else: ?>
                <?php foreach ($assets_to_print as $asset): ?>
                <tr>
                    <td><?php echo Html::entities_deep($asset['name']); ?></td>
                    <td><?php echo Html::entities_deep($asset['model']); ?></td>
                    <td><?php echo Html::entities_deep($asset['type']); ?></td>
                    <td><?php echo Html::entities_deep($asset['serial']); ?></td>
                    <td><?php echo Html::entities_deep($asset['state_name']); ?></td>
                    <td><?php echo Html::entities_deep($asset['location_name']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-col">
            <div class="signature-line"></div>
            <p>Podpis Pracownika</p>
        </div>
        <div class="signature-col" style="float: right;">
            <div class="signature-line"></div>
            <p>Podpis Pracownika IT</p>
        </div>
    </div>

</body>
</html>
<?php
$html = ob_get_clean();

// Konfiguracja i renderowanie PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Potrzebne do ładowania czcionek z URL

// Ustawienie zapisywalnego katalogu cache dla Dompdf, aby uniknąć błędów uprawnień
$dompdf_cache_dir = GLPI_CACHE_DIR . '/dompdf';
if (!is_dir($dompdf_cache_dir)) {
    mkdir($dompdf_cache_dir, 0755, true);
}
$options->set('fontCache', $dompdf_cache_dir);
$options->set('tempDir', $dompdf_cache_dir);

$options->set('chroot', Plugin::getPhpDir('mydevices')); // Ustawia katalog główny dla Dompdf

$dompdf = new Dompdf($options);
$dompdf->loadHtml(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Wysłanie PDF do przeglądarki
$dompdf->stream('protokol-zdawczo-odbiorczy.pdf', ['Attachment' => 0]);
exit;
?>