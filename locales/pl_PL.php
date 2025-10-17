<?php
$plurals = [
    // 'String' => 'Plural form'
];

$dictionary = [
    // Karta 'Moje urządzenia'
    'Moje urządzenia' => 'Moje urządzenia',
    'Brak przypisanych urządzeń' => 'Brak przypisanych urządzeń',
    'Nazwa' => 'Nazwa',
    'Model' => 'Model',
    'Typ' => 'Typ',
    'Numer seryjny' => 'Numer seryjny',
    'Status' => 'Status',
    'Lokalizacja' => 'Lokalizacja',
    'Brak przypisanych elementów' => 'Brak przypisanych elementów',

    // Filtry
    'Szukaj' => 'Szukaj',
    'Nazwa lub numer seryjny' => 'Nazwa lub numer seryjny',
    'Typ urządzenia' => 'Typ urządzenia',
    'Wszystkie typy' => 'Wszystkie typy',
    'Wszystkie statusy' => 'Wszystkie statusy',

    // Komunikaty
    'Item updated successfully' => 'Element zaktualizowany pomyślnie',
    'An error occurred while updating the asset.' => 'Wystąpił błąd podczas aktualizacji zasobu.',
    'Invalid or missing parameters.' => 'Nieprawidłowe lub brakujące parametry.',
    'Permission denied. You are not the owner of this asset or do not have sufficient rights.' => 'Odmowa dostępu. Nie jesteś właścicielem tego zasobu lub nie masz wystarczających uprawnień.',
    'Invalid CSRF token' => 'Nieprawidłowy token CSRF',
    'Method Not Allowed' => 'Metoda niedozwolona',
    'Unsupported itemtype.' => 'Nieobsługiwany typ elementu.',

    // Generator PDF
    'Generuj protokół zdawczo-odbiorczy' => 'Generuj protokół zdawczo-odbiorczy',
    'Protokół Zdawczo-Odbiorczy' => 'Protokół Zdawczo-Odbiorczy',
    'Data wygenerowania' => 'Data wygenerowania',
    'Pracownik' => 'Pracownik',
    'Podpis Pracownika' => 'Podpis Pracownika',
    'Podpis Pracownika IT' => 'Podpis Pracownika IT',
    'Brak urządzeń do wyświetlenia.' => 'Brak urządzeń do wyświetlenia.',

    // Uprawnienia
    'Edycja własnych urządzeń (Lokalizacja i Status)' => 'Edycja własnych urządzeń (Lokalizacja i Status)',

    // Typy urządzeń (dla spójności)
    'Komputer' => 'Komputer',
    'Monitor' => 'Monitor',
    'Peryferia' => 'Peryferia',
    'Telefon' => 'Telefon',
    'SIM Card' => 'Karta SIM',
];

return [
    'plurals' => $plurals,
    'dictionary' => $dictionary,
];