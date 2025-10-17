```markdown
# MyDevices — plugin do GLPI (v2.0)

Opis
----
MyDevices to wtyczka dla GLPI, która ułatwia zarządzanie urządzeniami przypisanymi do pracowników oraz wprowadza proces inwentaryzacji half‑automatycznej. Wersja 2.0 dodaje system cyklicznych kampanii inwentaryzacyjnych, eksport raportów, cache i nowy generator PDF (dompdf + czcionka Lato), zachowując kompatybilność z GLPI 10.x.

Najważniejsze nowości w v2.0
---------------------------
- Pełny system inwentaryzacji (tabele, historia, statusy, ticket integration)
- Tryb hybrydowy: admin uruchamia kampanię, GLPI Scheduler wykonuje przypomnienia i eskalacje
- Osobny rekord inwentaryzacyjny na każde urządzenie (dokładne raportowanie)
- Eksport raportów do CSV oraz automatyczna retencja (domyślnie 30 dni)
- Szablon mailowy HTML + CSS (edytowalny w panelu)
- Cache wyników (Toolbox::getGlpiCache) z konfigurowalnym TTL
- Wydzielony generator PDF przy użyciu dompdf i czcionki Lato (zachowany layout)
- Device types przeniesione do bazy (możliwość konfiguracji w panelu)
- Hooki GLPI do invalidacji cache i synchronizacji przy zmianach zasobów

Wymagania
---------
- GLPI 10.x
- PHP 7.4+
- Composer (do instalacji dompdf)
- Uprawnienia zapisu dla serwera WWW w katalogu pluginu (logi/exports/logo)
- Zalecane: dostęp do wysyłania maili z serwera (SMTP lub funkcja mail)

Instalacja
----------
1. Sklonuj repo do katalogu pluginów GLPI:
   git clone https://github.com/kenziak/mydevices-v2.git mydevices

2. Przejdź do katalogu wtyczki i zainstaluj zależności PHP:
   cd /var/www/html/glpi/plugins/mydevices
   composer install --no-dev

3. W GLPI: Administracja → Wtyczki → znajdź "MyDevices" → Instaluj → Aktywuj.

4. Uruchom skrypty SQL:
   - install/mysql/plugin_mydevices_2.0.0.sql
   - install/mysql/upgrade_1.0_to_2.0.sql (jeśli migrujesz z v1.x)
   - install/mysql/upgrade_inventory_add_reminders.sql (dodaje pola do obsługi przypomnień i kampanii)

5. Upewnij się, że katalogi istnieją i mają prawidłowe uprawnienia:
   - plugins/mydevices/logo/  (0755)
   - plugins/mydevices/exports/ (0755)
   - plugins/mydevices/logs/ (0755)
   - fonty: plugins/mydevices/fonts/Lato-Regular.ttf (czytelny dla WWW)

Konfiguracja po instalacji
--------------------------
Menu: Setup → Plugins → Moje Urządzenia → Konfiguracja

Sekcje konfiguracji:
- Ustawienia inwentaryzacji:
  - inventory_enabled (checkbox) — włącz/wyłącz system inwentaryzacji (domyślnie: włączone)
  - inventory_frequency (int) — liczba dni między pełnymi kampaniami (domyślnie 180)
  - inventory_notification_type (enum) — 'email', 'glpi', 'both'
  - inventory_email_recipients (text) — adres(y) IT do powiadomień przy "Nie posiadam" (domyślnie: glpihelpdesk@uzp.gov.pl). Możliwość podania wielu adresów (przecinek/średnik).
  - inventory_email_template (HTML) — edytowalny szablon maila (domyślny jest w templates/email/)

- Ustawienia wyświetlania:
  - visible_columns (JSON) — kolumny widoczne w tabeli użytkownika
  - device types — lista typów urządzeń i ich kolejność (zarządzalna z panelu)

- Ustawienia PDF:
  - pdf_logo_path (domyślnie: /var/www/html/glpi/plugins/mydevices/logo/)
  - pdf_header / pdf_footer — edytowalne (nie zmieniają layoutu domyślnego)
  - font Lato: upload pliku Lato-Regular.ttf do fonts/

- Ustawienia cache:
  - cache_enabled (checkbox), cache_ttl (sekundy, domyślnie 300)

Jak działa system inwentaryzacji (krok po kroku)
-----------------------------------------------
1. Administrator uruchamia kampanię manualnie z panelu (przycisk "Utwórz i wyślij inwentaryzację teraz").
   - System tworzy wpis (campaign_id) i dla każdego urządzenia użytkownika tworzy rekord inwentaryzacyjny (status='pending').

2. Initial send:
   - Po utworzeniu wpisów system wysyła e‑maile do użytkowników (HTML + lista urządzeń + przyciski "Posiadam" / "Nie posiadam").

3. Odpowiedzi użytkowników:
   - "Posiadam" → wpis zostaje ustawiony: user_response='possessed', status='confirmed', confirmed_date = teraz. Użytkownik otrzyma toast + (opcjonalnie) mail potwierdzający.
   - "Nie posiadam" → system tworzy ticket GLPI i/lub wysyła mail do adresów IT (domyślnie glpihelpdesk@uzp.gov.pl), wpis aktualizowany: user_response='not_possessed', status='ticket_created', ticket_id = ID.

4. Przypomnienia:
   - GLPI Scheduler (zadanie plugin_mydevices_sendinventory) automatycznie sprawdza rekordy pending i wysyła przypomnienia:
     - 1. przypomnienie po 5 dniach od initial send
     - 2. przypomnienie po kolejnych 7 dniach (czyli 12 dni od initial)
   - Po 12 dniach bez odpowiedzi rekordy są oznaczane do eskalacji (escalate_flag = 1)

5. Eskalacja:
   - Rekordy z escalate_flag są widoczne na dashboardzie admina: filtr "Wymaga manualnej weryfikacji".
   - Admin/IT mogą wygenerować CSV z listą nierozwiązanych rekordów, przypisać zadanie, kontaktować użytkownika i wykonać manualną korektę (tylko IT może usuwać przypisania).

Eksporty i raporty
------------------
- Eksport CSV kampanii: exports/inventory_report_YYYY-MM-DD_HH-MM-SS_c{campaign_id}.csv
- Retencja: pliki eksportów są automatycznie usuwane po 30 dniach (konfigurowalne).
- Dashboard admina: podgląd kampanii, filtrowanie, sortowanie oraz szybki eksport.

Uprawnienia
-----------
- Edycja Status/Lokalizacja: właściciel zasobu oraz administrator mają możliwość zmiany statusu i lokalizacji (tak jak w wersji main). Użytkownik nie może samodzielnie usuwać przypisań.
- Dostęp do panelu konfiguracji: profily z prawem `config` (UPDATE).
- Dostęp do eksportów/logów: jedynie admin (config rights).

Bezpieczeństwo
--------------
- Nie naprawiamy istniejącej luki CSRF w ajax/asset.update.php (zgodnie z wymaganiami projektu).
- Wszystkie wejścia walidowane przez filter_input() lub przygotowane zapytania SQL.
- Operacje krytyczne (usunięcie przypisania) wymagają działania administratora/IT (ticket workflow).

Instalacja dompdf i czcionki
---------------------------
1. composer install --no-dev (w katalogu pluginu)
2. Umieść Lato-Regular.ttf w folderze plugins/mydevices/fonts/
3. Upewnij się, że katalog cache/temp dla dompdf ma prawa zapisu przez serwer WWW.

Szablony i pliki do edycji
--------------------------
- Szablon e‑maila: templates/email/inventory_notification.html.twig
- Szablon PDF: templates/pdf/protocol.html.twig (layout przeniesiony z v1, nie zmieniać)
- CSS i JS: css/, js/

Migracja z v1.x
---------------
- Dołączony skrypt: install/mysql/upgrade_1.0_to_2.0.sql (wykrywa brakujące wpisy i tworzy domyślne device types)
- Zalecane: wykonać backup bazy przed uruchomieniem skryptów migracyjnych.

Troubleshooting
---------------
- Brak PDF / błędy czcionki: sprawdź obecność fonts/Lato-Regular.ttf i prawa do katalogu fonts/ oraz cache dompdf.
- Brak wysyłki maili: sprawdź konfigurację mailową PHP/GLPI (SMTP).
- Uprawnienia FS: katalogi exports/ i logs/ muszą być zapisywalne podczas instalacji.

Changelog (krótkie)
-------------------
- v2.0: Inwentaryzacja hybrydowa, scheduler przypomnień, eksporty CSV, dompdf, cache, device types w DB, panel admina.

Autor i licencja
----------------
- Autor: Zespół MyDevices / kenziak (adaptacja i rozwój)
- Licencja: MIT (lub inna zgodnie z polityką projektu) — do weryfikacji.

Wsparcie i kontakt
------------------
- Domyślny adres zgłoszeń IT (konfigurowalny): glpihelpdesk@uzp.gov.pl
- Instrukcje instalacji i troubleshooting w tym pliku README.

Deinstalacja / cofnięcie zmian (szybka instrukcja)
--------------------------------------------------
Uwaga: przed wykonaniem jakichkolwiek operacji usuwania wykonaj pełny backup bazy danych i katalogu GLPI.

1. Dezaktywacja i odinstalowanie pluginu w GLPI:
   - Zaloguj się do GLPI jako administrator.
   - Przejdź do Administracja → Wtyczki.
   - Znajdź "MyDevices" i kliknij "Dezaktywuj", a następnie "Odinstaluj".

2. Usunięcie dodatkowych tabel (opcjonalne; wykonaj tylko jeśli chcesz trwale usunąć dane):
   - Uruchom poniższe zapytania SQL w bazie GLPI (upewnij się, że masz backup):

     DROP TABLE IF EXISTS `glpi_plugin_mydevices_configs`;
     DROP TABLE IF EXISTS `glpi_plugin_mydevices_inventory`;
     DROP TABLE IF EXISTS `glpi_plugin_mydevices_device_types`;

3. Usunięcie katalogów plików wygenerowanych przez plugin (exports, logs, logo):
   - Usuń katalogi lub pliki w katalogu pluginu: plugins/mydevices/exports/*, plugins/mydevices/logs/*, plugins/mydevices/logo/*

4. (Opcjonalne) Przywrócenie zmian w bazie danych GLPI:
   - Jeśli wykonano migracje lub zmiany w tabelach GLPI, możesz przywrócić backup bazy.

5. Finalne sprawdzenie:
   - Upewnij się, że plugin nie jest już widoczny w sekcji Wtyczki i że nie ma żadnych zadań schedulera związanych z pluginem.

Dziękujemy za korzystanie z MyDevices.
```
