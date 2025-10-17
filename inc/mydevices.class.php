<?php

/**
 * Główna klasa wtyczki MyDevices.
 *
 * Odpowiada za wyświetlanie interfejsu użytkownika, pobieranie danych
 * oraz integrację z innymi komponentami wtyczki.
 */
class MyDevices extends CommonGLPI {
   public static $rightname = 'pluginmydevices';

   /**
    * Zwraca nazwę wtyczki wyświetlaną w menu.
    */
   public static function getMenuName($formatted = true) {
      return __('Moje urządzenia', 'mydevices');
   }

   /**
    * Sprawdza, czy użytkownik ma uprawnienia do wyświetlenia strony.
    */
   public static function canView(): bool {
      return Session::getLoginUserID() > 0;
   }

   /**
    * Wyświetla główny widok wtyczki.
    */
   public function display($options = []) {
      $uid = (int) Session::getLoginUserID();
      $all_assets = $this->getAllAssetsForUser($uid);

      // Przycisk generowania PDF - używa formularza POST
      echo '<div style="margin-bottom: 1rem;">';
      echo '<form method="POST" action="' . Plugin::getWebDir('mydevices') . '/front/mydevices.php" style="display: inline;">';
      echo '<input type="hidden" name="generate_protocol" value="1">';
      echo '<input type="hidden" name="_glpi_csrf_token" value="' . Session::getNewCSRFToken('plugin_mydevices_protocol') . '">';
      echo '<button type="submit" class="btn btn-primary" style="background-color: #0d6efd; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 0.25rem; display: inline-block; border: none; cursor: pointer;">';
      echo '<i class="fas fa-file-pdf" style="margin-right: 0.5rem;"></i>' . __('Generuj protokół zdawczo-odbiorczy', 'mydevices');
      echo '</button>';
      echo '</form>';
      echo '</div>';

      if (empty($all_assets)) {
         echo '<div class="alert alert-info">'.__('Brak przypisanych urządzeń','mydevices').'</div>';
         return;
      }

      $all_states = $this->getAvailableStates();
      
      $this->renderTable($all_assets, $all_states, $this->getAvailableLocations());
      $this->renderToastContainer();
      $this->renderStyles();
      $this->renderScript();
   }

   /**
    * Generuje protokół PDF z listą urządzeń użytkownika.
    */
   public function generateProtocol() {
      if (!Session::getLoginUserID()) {
         return false;
      }

      $uid = (int) Session::getLoginUserID();
      $all_assets = $this->getAllAssetsForUser($uid);

      if (empty($all_assets)) {
         Session::addMessageAfterRedirect(__('Brak urządzeń do wygenerowania protokołu', 'mydevices'), false, ERROR);
         return false;
      }

      require_once(__DIR__ . '/../vendor/autoload.php');

      $mpdf = new \Mpdf\Mpdf([
         'mode' => 'utf-8',
         'format' => 'A4',
         'margin_left' => 15,
         'margin_right' => 15,
         'margin_top' => 20,
         'margin_bottom' => 20,
      ]);

      $user = new User();
      $user->getFromDB($uid);

      $user_name = $user->fields['realname'] . ' ' . $user->fields['firstname'];
      $date = date('Y-m-d H:i:s');

      $html = '<h1 style="text-align: center;">Protokół zdawczo-odbiorczy</h1>';
      $html .= '<p><strong>Użytkownik:</strong> ' . htmlspecialchars($user_name) . '</p>';
      $html .= '<p><strong>Data:</strong> ' . htmlspecialchars($date) . '</p>';
      $html .= '<hr>';

      $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">';
      $html .= '<thead>';
      $html .= '<tr style="background-color: #f0f0f0;"><th>Nazwa</th><th>Model</th><th>Typ</th><th>Numer seryjny</th><th>Status</th><th>Lokalizacja</th></tr>';
      $html .= '</thead>';
      $html .= '<tbody>';

      foreach ($all_assets as $asset) {
         $html .= '<tr>';
         $html .= '<td>' . htmlspecialchars($asset['name']) . '</td>';
         $html .= '<td>' . htmlspecialchars($asset['model'] ?? '-') . '</td>';
         $html .= '<td>' . htmlspecialchars($asset['type'] ?? '-') . '</td>';
         $html .= '<td>' . htmlspecialchars($asset['serial'] ?? '-') . '</td>';
         $html .= '<td>' . htmlspecialchars($asset['state_name'] ?? '-') . '</td>';
         $html .= '<td>' . htmlspecialchars($asset['location_name'] ?? '-') . '</td>';
         $html .= '</tr>';
      }

      $html .= '</tbody></table>';

      $mpdf->WriteHTML($html);

      $filename = 'protokol_' . $uid . '_' . date('Y-m-d_His') . '.pdf';
      $mpdf->Output($filename, 'D');

      exit;
   }

   /**
    * Pobiera dostępne statusy z GLPI.
    */
   private function getAvailableStates(): array {
       global $DB;
       $iterator = $DB->request(['FROM' => 'glpi_states', 'ORDERBY' => 'name']);
       return array_column(iterator_to_array($iterator), 'name', 'id');
   }

   /**
    * Pobiera dostępne lokalizacje z GLPI.
    */
   private function getAvailableLocations(): array {
       global $DB;
       $iterator = $DB->request(['FROM' => 'glpi_locations', 'ORDERBY' => 'name']);
       return array_column(iterator_to_array($iterator), 'name', 'id');
   }

   /**
    * Pobiera wszystkie zasoby przypisane do danego użytkownika.
    */
    public function getAllAssetsForUser(int $uid): array {
        global $DB;
        $all_assets = [];
        $map = $this->getDeviceMap();

        foreach ($map as $itemtype => $cfg) {
            if (!$DB->tableExists($cfg['table'])) {
                continue;
            }
            $assets = $this->fetchByUsersId($uid, $cfg, $itemtype);
            $all_assets = array_merge($all_assets, $assets);
        }
        return $all_assets;
   }

   /**
    * Pobiera zasoby danego typu przypisane do użytkownika.
    */
    private function fetchByUsersId(int $uid, array $cfg, string $itemtype): array {
        global $DB;

        $t = $cfg['table'];
        $tm = $cfg['model_tbl'] ?? '';
        $tfm = $cfg['model_fk'] ?? '';
        $tt = $cfg['type_tbl'] ?? '';
        $tftc = $cfg['type_fkcol'] ?? '';
        $ser = $cfg['serial_col'];

        $select = [
            'i.id', 'i.name', 'i.users_id', 'i.locations_id', 'i.states_id',
            "i.$ser AS serial", 'l.name AS location_name', 's.name AS state_name'
        ];
        $joins = [
            'glpi_locations AS l' => ['ON' => ['i' => 'locations_id', 'l' => 'id']],
            'glpi_states AS s'    => ['ON' => ['i' => 'states_id', 's' => 'id']]
        ];

        if ($tm && $DB->tableExists($tm) && $tfm) {
            $select[] = 'm.name AS model';
            $joins[$tm.' AS m'] = ['ON' => ['i' => $tfm, 'm' => 'id']];
        }
        if ($tt && $DB->tableExists($tt) && $tftc) {
            $select[] = 'tp.name AS type';
            $joins[$tt.' AS tp'] = ['ON' => ['i' => $tftc, 'tp' => 'id']];
        }

        $req = [
            'SELECT' => $select, 'FROM' => $t.' AS i', 'LEFT JOIN' => $joins,
            'WHERE' => ['i.is_deleted' => 0, 'i.users_id' => $uid], 'ORDER' => 'i.name'
        ];

        $out = [];
        foreach ($DB->request($req) as $row) {
            $row['itemtype'] = $itemtype;
            $row['type_label'] = $cfg['label'];
            $row['form_path'] = $cfg['form'];
            $row['model'] = $row['model'] ?? '';
            $row['type'] = $row['type'] ?? $cfg['label'];
            $row['serial'] = $row['serial'] ?? '';
            $row['location_name'] = $row['location_name'] ?? '';
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Renderuje tabelę z zasobami.
     */
    private function renderTable(array $rows, array $states, array $locations): void {
        global $CFG_GLPI;
        $is_admin = Session::haveRight('config', UPDATE);
        $uid = (int)Session::getLoginUserID();

        echo '<div class="mydevices-table-container">';
        echo '<table id="mydevices-table" class="mydevices-table">';
        echo '<thead><tr><th>'.__('Nazwa').'</th><th>'.__('Model').'</th><th>'.__('Typ').'</th><th>'.__('Numer seryjny').'</th><th>'.__('Status').'</th><th>'.__('Lokalizacja').'</th></tr></thead><tbody>';

        if (empty($rows)) {
            echo '<tr><td colspan="6" style="text-align: center;">'.__('Brak przypisanych elementów').'</td></tr>';
        }

        foreach ($rows as $r) {
            $href = $CFG_GLPI['root_doc'] . $r['form_path'] . '?id=' . (int)$r['id'];
            $is_owner = ($r['users_id'] ?? 0) == $uid;
            $can_edit = $is_admin || $is_owner;

            $status_select = '<select class="editable-select" data-itemtype="'.Html::entities_deep($r['itemtype']).'" data-items_id="'.(int)$r['id'].'" data-field="states_id" '.(!$can_edit ? 'disabled' : '').'>';
            foreach ($states as $id => $name) {
                $selected = ($id == $r['states_id']) ? ' selected' : '';
                $status_select .= '<option value="'.(int)$id.'"'.$selected.'>'.Html::entities_deep($name).'</option>';
            }
            $status_select .= '</select>';

            $location_select = '<select class="editable-select" data-itemtype="'.Html::entities_deep($r['itemtype']).'" data-items_id="'.(int)$r['id'].'" data-field="locations_id" '.(!$can_edit ? 'disabled' : '').'>';
            foreach ($locations as $id => $name) {
                $selected = ($id == $r['locations_id']) ? ' selected' : '';
                $location_select .= '<option value="'.(int)$id.'"'.$selected.'>'.Html::entities_deep($name).'</option>';
            }
            $location_select .= '</select>';

            echo '<tr data-type="'.Html::entities_deep($r['type_label']).'" data-status-id="'.(int)$r['states_id'].'">';
            echo '<td><a href="'.Html::entities_deep($href).'">'.Html::entities_deep($r['name']).'</a></td>';
            echo '<td>'.Html::entities_deep((string)$r['model']).'</td>';
            echo '<td>'.Html::entities_deep((string)$r['type']).'</td>';
            echo '<td>'.Html::entities_deep((string)$r['serial']).'</td>';
            echo '<td>'.$status_select.'</td>';
            echo '<td>'.$location_select.'</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    /**
     * Renderuje kontener na powiadomienia "toast".
     */
    private function renderToastContainer(): void {
        echo '<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>';
    }

    /**
     * Renderuje style CSS.
     */
    private function renderStyles(): void {
        echo '<style>
        .mydevices-table-container { overflow-x: auto; border: 1px solid #dee2e6; border-radius: 0.25rem; font-size: 0.8125rem; }
        .mydevices-table { width: 100%; border-collapse: collapse; background: white; }
        .mydevices-table thead { background: #e9ecef; }
        .mydevices-table th { padding: 0.5rem; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; font-size: 0.8125rem; }
        .mydevices-table td { padding: 0.5rem; border-bottom: 1px solid #dee2e6; font-size: 0.8125rem; }
        .mydevices-table tbody tr:hover { background: #f8f9fa; }
        .editable-select { width: 100%; padding: 0.25rem; border: 1px solid #ced4da; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem; transition: background-color 0.3s; }
        .editable-select:hover:not(:disabled) { background: #e8f4f8; border-color: #0d6efd; }
        .editable-select:disabled { background: #e9ecef; cursor: not-allowed; }
        .editable-select.saving { background: #fff3cd !important; border-color: #ffc107 !important; }
        .toast-success { background: #d1e7dd; border: 1px solid #badbcc; color: #0f5132; padding: 0.75rem; border-radius: 0.25rem; margin-bottom: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); font-size: 0.8125rem; }
        .toast-error { background: #f8d7da; border: 1px solid #f5c2c7; color: #842029; padding: 0.75rem; border-radius: 0.25rem; margin-bottom: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); font-size: 0.8125rem; }
        </style>';
    }

    /**
     * Renderuje skrypt JavaScript.
     */
    private function renderScript(): void {
        $ajax_url = Plugin::getWebDir('mydevices').'/ajax/asset.update.php';
        ?>
        <script>
        (function() {
            const CSRF_TOKEN = (typeof _glpi_csrf_token !== 'undefined') ? _glpi_csrf_token : 
                              (document.querySelector('meta[property="glpi:csrf_token"]')?.getAttribute('content') || '');
            const AJAX_URL = <?php echo json_encode($ajax_url); ?>;
            
            const table = document.getElementById('mydevices-table');
            if (!table) { return; }

            let isUpdating = false;
            const updateQueue = [];

            function showToast(message, isError = false) {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = isError ? 'toast-error' : 'toast-success';
                toast.textContent = message;
                container.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }

            async function processQueue() {
                if (isUpdating || updateQueue.length === 0) return;
                
                isUpdating = true;
                const request = updateQueue.shift();
                
                try {
                    await updateAsset(request.itemtype, request.items_id, request.field, request.value, request.selectElement);
                } catch (error) {
                    console.error('Queue processing error:', error);
                } finally {
                    isUpdating = false;
                    if (updateQueue.length > 0) {
                        setTimeout(processQueue, 200);
                    }
                }
            }

            async function updateAsset(itemtype, items_id, field, value, selectElement) {
                if (selectElement) {
                    selectElement.classList.add('saving');
                    selectElement.disabled = true;
                }

                const body = new URLSearchParams({
                    itemtype: itemtype,
                    items_id: items_id,
                    field: field,
                    value: value,
                    _glpi_csrf_token: CSRF_TOKEN
                });

                try {
                    const response = await fetch(AJAX_URL, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: body.toString(),
                        cache: 'no-cache'
                    });
                    
                    const result = await response.json();

                    if (response.ok && result.status === 'ok') {
                        showToast(result.message || 'Zaktualizowano - odświeżanie...', false);
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        throw new Error(result.message || 'Błąd serwera');
                    }
                } catch (error) {
                    showToast('Błąd: ' + error.message, true);
                    if (selectElement) {
                        selectElement.classList.remove('saving');
                        selectElement.disabled = false;
                        if (selectElement.dataset.previousValue) {
                            selectElement.value = selectElement.dataset.previousValue;
                        }
                    }
                    setTimeout(() => window.location.reload(), 2000);
                }
            }

            table.addEventListener('change', function(e) {
                if (e.target && e.target.classList.contains('editable-select')) {
                    const select = e.target;
                    const {itemtype, items_id, field} = select.dataset;
                    
                    if (!itemtype || !items_id || !field) {
                        showToast('Błąd: Brak wymaganych atrybutów', true);
                        return;
                    }
                    
                    updateQueue.push({itemtype, items_id, field, value: select.value, selectElement: select});
                    processQueue();
                }
            });
            
            table.querySelectorAll('.editable-select').forEach(select => {
                select.dataset.previousValue = select.value;
            });
        })();
        </script>
        <?php
    }

   /**
    * Zwraca mapę typów urządzeń.
    */
   public function getDeviceMap(): array {
       return [
         'Computer'  => ['table' => 'glpi_computers', 'model_tbl' => 'glpi_computermodels', 'model_fk'  => 'computermodels_id', 'type_tbl'  => 'glpi_computertypes', 'type_fkcol'=> 'computertypes_id', 'serial_col'=> 'serial', 'form' => '/front/computer.form.php', 'label' => __('Komputer', 'mydevices')],
         'Monitor'   => ['table' => 'glpi_monitors', 'model_tbl' => 'glpi_monitormodels', 'model_fk'  => 'monitormodels_id', 'type_tbl'  => 'glpi_monitortypes', 'type_fkcol'=> 'monitortypes_id', 'serial_col'=> 'serial', 'form' => '/front/monitor.form.php', 'label' => __('Monitor', 'mydevices')],
         'Peripheral'=> ['table' => 'glpi_peripherals', 'model_tbl' => 'glpi_peripheralmodels', 'model_fk'  => 'peripheralmodels_id', 'type_tbl'  => 'glpi_peripheraltypes', 'type_fkcol'=> 'peripheraltypes_id', 'serial_col'=> 'serial', 'form' => '/front/peripheral.form.php', 'label' => __('Peryferia', 'mydevices')],
         'Phone'     => ['table' => 'glpi_phones', 'model_tbl' => 'glpi_phonemodels', 'model_fk'  => 'phonemodels_id', 'type_tbl'  => 'glpi_phonetypes', 'type_fkcol'=> 'phonetypes_id', 'serial_col'=> 'serial', 'form' => '/front/phone.form.php', 'label' => __('Telefon', 'mydevices')],
         'Simcard'   => ['table' => 'glpi_simcards', 'model_tbl' => 'glpi_simcardmodels', 'model_fk'  => 'simcardmodels_id', 'type_tbl'  => 'glpi_simcardtypes', 'type_fkcol'=> 'simcardtypes_id', 'serial_col'=> 'serial', 'form' => '/front/simcard.form.php', 'label' => __('Karta SIM', 'mydevices')],
      ];
   }
}