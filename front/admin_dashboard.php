<?php
include('../../../inc/includes.php');
Session::checkRight('config','w');

Html::header(__('MyDevices - Panel Inwentaryzacji','mydevices'), $_SERVER['PHP_SELF'], 'tools', 'pluginmydevicesconfig');

echo '<div class="container-fluid">';
echo '<h2>Panel Inwentaryzacji - MyDevices</h2>';

echo '<div style="margin-bottom:1rem;">';
echo '<form method="post" action="'.Plugin::getWebDir('mydevices').'/ajax/admin.actions.php">';
echo Html::hidden('action', ['value' => 'create_campaign']);
echo '<button class="btn btn-primary" type="submit">Utwórz i wyślij inwentaryzację teraz</button>';
echo '</form>';
echo '</div>';

// Tutaj: lista kampanii (przykładowa tabela)
echo '<h4>Ostatnie kampanie</h4>';
echo '<table class="table table-sm">';
echo '<thead><tr><th>Id</th><th>Data utworzenia</th><th>Rekordy</th><th>Potwierdzone</th><th>Tickety</th><th>Akcje</th></tr></thead><tbody>';

// TODO: wczytać rzeczywiste kampanie z DB
echo '<tr><td>1</td><td>2025-10-01</td><td>120</td><td>98</td><td>5</td><td><a class="btn btn-sm btn-outline-secondary" href="'.Plugin::getWebDir('mydevices').'/ajax/admin.actions.php?action=export_campaign&id=1">Pobierz CSV</a></td></tr>';

echo '</tbody></table>';

echo '<h4>Rekordy wymagające weryfikacji (escalated)</h4>';
echo '<p>Możesz filtrować i pobrać raport lub przypisać zadanie dla technika.</p>';
echo '<!-- tu dynamiczny grid z filtrem/sortowaniem -->';

echo '</div>';
Html::footer();