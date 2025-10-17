<?php
/**
 * Widok inwentaryzacji dla użytkownika.
 *
 * Wyświetla baner inwentaryzacyjny oraz listę urządzeń,
 * które użytkownik musi potwierdzić lub zgłosić ich brak.
 */

include('../../../inc/includes.php');
require_once __DIR__ . '/../src/Inventory.php';
require_once __DIR__ . '/../inc/mydevices.class.php';

Session::checkLoginUser();
Html::header(__('Moje urządzenia - Inwentaryzacja','mydevices'));

$inv = new \GlpiPlugin\Mydevices\Inventory();
$userId = (int)Session::getLoginUserID();
$entries = $inv->getInventoryForUser($userId);

echo '<div class="mydevices-inventory-banner">';
if (!empty($entries)) {
    echo '<h3>Wymagana inwentaryzacja</h3>';
    echo '<p>Prosimy potwierdzić posiadanie urządzeń lub zgłosić brak.</p>';
    echo '<table id="mydevices-inventory-table" class="mydevices-table">';
    echo '<thead><tr><th>Urządzenie</th><th>Typ</th><th>Data powiadomienia</th><th>Twoja odpowiedź</th><th>Akcja</th></tr></thead><tbody>';
    foreach ($entries as $e) {
        $name = Html::entities_deep($e['itemtype'] . ' #' . $e['items_id']);
        $date = $e['request_date'];
        $resp = $e['user_response'];
        echo '<tr data-entry-id="'.(int)$e['id'].'">';
        echo '<td>'.$name.'</td>';
        echo '<td>'.Html::entities_deep($e['itemtype']).'</td>';
        echo '<td>'.$date.'</td>';
        echo '<td>'.$resp.'</td>';
        echo '<td>';
        echo '<button class="btn btn-success btn-possess" data-id="'.(int)$e['id'].'">Posiadam</button> ';
        echo '<button class="btn btn-danger btn-not-possess" data-id="'.(int)$e['id'].'">Nie posiadam</button>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-info">Brak oczekujących potwierdzeń.</div>';
}
echo '</div>';

echo "<script src='".Plugin::getWebDir('mydevices')."/js/mydevices.js'></script>";
Html::footer();