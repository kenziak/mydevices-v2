# MyDevices — plugin do GLPI (v2.0)

## Opis
MyDevices to wtyczka dla GLPI, która ułatwia zarządzanie urządzeniami przypisanymi do pracowników oraz wprowadza proces inwentaryzacji półautomatycznej. Wersja 2.0 dodaje system cyklicznych kampanii inwentaryzacyjnych, eksport raportów, cache i nowy generator PDF (dompdf + czcionka Lato), zachowując kompatybilność z GLPI 10.x.

## Najważniejsze nowości w v2.0
- **Pełny system inwentaryzacji**: tabele, historia, statusy, integracja z ticketami.
- **Tryb hybrydowy**: administrator uruchamia kampanię, a GLPI Scheduler wykonuje przypomnienia i eskalacje.
- **Osobny rekord inwentaryzacyjny** na każde urządzenie, co umożliwia dokładne raportowanie.
- **Eksport raportów** do CSV oraz automatyczna retencja (domyślnie 30 dni).
- **Szablon mailowy HTML + CSS**, edytowalny w panelu konfiguracyjnym.
- **Cache wyników** z konfigurowalnym czasem życia (TTL).
- **Wydzielony generator PDF** przy użyciu dompdf i czcionki Lato.
- **Typy urządzeń** przeniesione do bazy danych, z możliwością konfiguracji w panelu.
- **Hooki GLPI** do invalidacji cache i synchronizacji przy zmianach zasobów.

## Wymagania
- GLPI 10.x
- PHP 7.4+
- Composer (do instalacji dompdf)
- Uprawnienia zapisu dla serwera WWW w katalogach: `plugins/mydevices/logs/`, `plugins/mydevices/exports/`, `plugins/mydevices/logo/`.
- Dostęp do wysyłania maili z serwera (SMTP lub funkcja `mail()`).

## Instalacja
1. Sklonuj repozytorium do katalogu pluginów GLPI:
   ```bash
   git clone https://github.com/kenziak/mydevices-v2.git mydevices
   ```
2. Przejdź do katalogu wtyczki i zainstaluj zależności PHP:
   ```bash
   cd /var/www/html/glpi/plugins/mydevices
   composer install --no-dev
   ```
3. W GLPI przejdź do `Administracja` → `Wtyczki`, znajdź "MyDevices", a następnie kliknij `Instaluj` i `Aktywuj`.
4. Uruchom skrypty SQL (jeśli to konieczne):
   - `install/mysql/plugin_mydevices_2.0.0.sql` (przy pierwszej instalacji)
   - `install/mysql/upgrade_1.0_to_2.0.sql` (przy migracji z v1.x)
   - `install/mysql/upgrade_inventory_add_reminders.sql` (przy aktualizacji do wersji z kampaniami)

## Konfiguracja po instalacji
Menu: `Ustawienia` → `Moje Urządzenia`

W panelu konfiguracyjnym można zarządzać następującymi opcjami:
- **Ustawienia inwentaryzacji**: włączanie/wyłączanie, częstotliwość, typ powiadomień, adresaci e-mail.
- **Ustawienia cache**: włączanie/wyłączanie, czas życia (TTL).
- **Ustawienia PDF**: ścieżka do logo, nagłówek, stopka.

## Jak działa system inwentaryzacji
1. **Administrator uruchamia kampanię** z panelu.
2. **System wysyła e-maile** do użytkowników z listą urządzeń.
3. **Użytkownicy odpowiadają**:
   - **"Posiadam"**: status wpisu zmienia się na `confirmed`.
   - **"Nie posiadam"**: tworzony jest ticket w GLPI, a status wpisu zmienia się na `ticket_created`.
4. **Przypomnienia**: GLPI Scheduler wysyła przypomnienia po 5 i 12 dniach.
5. **Eskalacja**: Po 12 dniach bez odpowiedzi, rekordy są oznaczane do manualnej weryfikacji.

## Deinstalacja
1. W GLPI przejdź do `Administracja` → `Wtyczki`, znajdź "MyDevices", a następnie kliknij `Dezaktywuj` i `Odinstaluj`.
2. (Opcjonalnie) Usuń tabele z bazy danych:
   ```sql
   DROP TABLE IF EXISTS `glpi_plugin_mydevices_configs`;
   DROP TABLE IF EXISTS `glpi_plugin_mydevices_inventory`;
   DROP TABLE IF EXISTS `glpi_plugin_mydevices_device_types`;
   ```
3. (Opcjonalnie) Usuń katalogi `exports`, `logs` i `logo` z folderu wtyczki.

## Autor i licencja
- **Autor**: Zespół MyDevices / kenziak (adaptacja i rozwój)
- **Licencja**: MIT (do weryfikacji)