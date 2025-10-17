<?php
/**
 * Formularz konfiguracyjny wtyczki MyDevices.
 *
 * Umożliwia administratorom zarządzanie ustawieniami wtyczki,
 * takimi jak inwentaryzacja, cache i generowanie PDF.
 */

include('../../../inc/includes.php');

use GlpiPlugin\Mydevices\Config;

// Sprawdzenie uprawnień
Session::checkRight('config', UPDATE);

$plugin_config = Config::getConfig();
Html::header(__('Konfiguracja wtyczki Moje Urządzenia', 'mydevices'));

// Obsługa zapisu formularza
if (isset($_POST['save'])) {
    Session::checkCSRF('plugin_mydevices_config');

    $new_config = $_POST;
    unset($new_config['save'], $new_config['_glpi_csrf_token']);

    $new_config['inventory_enabled'] = isset($new_config['inventory_enabled']) ? 1 : 0;
    $new_config['cache_enabled'] = isset($new_config['cache_enabled']) ? 1 : 0;

    if (Config::saveConfig($new_config)) {
        Html::displayRightMessage(__('Zapisano pomyślnie'));
        Html::redirect($_SERVER['PHP_SELF']);
    } else {
        Html::displayErrorMessage(__('Błąd podczas zapisu'));
    }
}
?>

<style>
.config-form-container { max-width: 900px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.form-section { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; }
.form-section:last-child { border-bottom: none; }
.form-section h3 { font-size: 1.2rem; color: #333; margin-bottom: 15px; border-left: 3px solid #007bff; padding-left: 10px; }
.glpi_checkbox { display: flex; align-items: center; }
.glpi_checkbox label { margin-left: 8px; }
</style>

<div class="config-form-container">
    <form method="post" action="">
        <?php echo Html::getCsrfToken('plugin_mydevices_config'); ?>

        <div class="form-section">
            <h3><?php echo __('Ustawienia inwentaryzacji'); ?></h3>
            <table class="tab_cadre_fix">
                <tr>
                    <td><?php echo __('Włącz system inwentaryzacji'); ?></td>
                    <td>
                        <div class="glpi_checkbox">
                            <input type="checkbox" name="inventory_enabled" <?php echo ($plugin_config['inventory_enabled'] ?? 1) ? 'checked' : ''; ?>>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><?php echo __('Częstotliwość kampanii (dni)'); ?></td>
                    <td><input type="number" name="inventory_frequency" value="<?php echo $plugin_config['inventory_frequency'] ?? 180; ?>"></td>
                </tr>
                <tr>
                    <td><?php echo __('Typ powiadomień'); ?></td>
                    <td>
                        <select name="inventory_notification_type">
                            <option value="email" <?php echo (($plugin_config['inventory_notification_type'] ?? 'email') == 'email') ? 'selected' : ''; ?>><?php echo __('E-mail'); ?></option>
                            <option value="glpi" <?php echo (($plugin_config['inventory_notification_type'] ?? 'email') == 'glpi') ? 'selected' : ''; ?>><?php echo __('Powiadomienie GLPI'); ?></option>
                            <option value="both" <?php echo (($plugin_config['inventory_notification_type'] ?? 'email') == 'both') ? 'selected' : ''; ?>><?php echo __('Oba'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><?php echo __('Adresaci e-mail (IT)'); ?></td>
                    <td><input type="text" class="glpi_input" name="inventory_email_recipients" value="<?php echo $plugin_config['inventory_email_recipients'] ?? ''; ?>"></td>
                </tr>
            </table>
        </div>

        <div class="form-section">
            <h3><?php echo __('Ustawienia cache'); ?></h3>
            <table class="tab_cadre_fix">
                <tr>
                    <td><?php echo __('Włącz cache'); ?></td>
                    <td>
                        <div class="glpi_checkbox">
                            <input type="checkbox" name="cache_enabled" <?php echo ($plugin_config['cache_enabled'] ?? 0) ? 'checked' : ''; ?>>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><?php echo __('Czas życia cache (sekundy)'); ?></td>
                    <td><input type="number" name="cache_ttl" value="<?php echo $plugin_config['cache_ttl'] ?? 300; ?>"></td>
                </tr>
            </table>
        </div>

        <div class="form-section">
            <h3><?php echo __('Ustawienia PDF'); ?></h3>
            <table class="tab_cadre_fix">
                <tr>
                    <td><?php echo __('Ścieżka do logo'); ?></td>
                    <td><input type="text" class="glpi_input" name="pdf_logo_path" value="<?php echo $plugin_config['pdf_logo_path'] ?? ''; ?>"></td>
                </tr>
                 <tr>
                    <td><?php echo __('Nagłówek PDF'); ?></td>
                    <td><textarea name="pdf_header" class="glpi_textarea"><?php echo $plugin_config['pdf_header'] ?? ''; ?></textarea></td>
                </tr>
                <tr>
                    <td><?php echo __('Stopka PDF'); ?></td>
                    <td><textarea name="pdf_footer" class="glpi_textarea"><?php echo $plugin_config['pdf_footer'] ?? ''; ?></textarea></td>
                </tr>
            </table>
        </div>

        <div class="center">
            <button type="submit" name="save" class="btn btn-primary"><?php echo __('Zapisz'); ?></button>
        </div>
    </form>
</div>

<?php
Html::footer();
?>