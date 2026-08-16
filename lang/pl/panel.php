<?php

return [

    'auth' => [
        'failed' => 'Te dane logowania nie pasują do żadnego konta.',
        'throttled' => 'Zbyt wiele prób logowania. Spróbuj ponownie za :seconds s.',
        'signed_out' => 'Wylogowano.',
    ],

    'devices' => [
        'created' => 'Sonda :name została dodana.',
        'updated' => 'Zapisano zmiany w sondzie :name.',
        'deleted' => 'Sonda :name została usunięta.',
        'delete_blocked' => 'Nie można usunąć sondy :name, bo są z nią powiązane badania. Zamiast tego wyłącz ją.',
        'token_reissued' => 'Sonda :name ma nowe poświadczenie. Poprzednie przestało działać.',
    ],

    'account' => [
        'updated' => 'Dane konta zostały zapisane.',
        'password_updated' => 'Hasło zostało zmienione. Pozostałe sesje zostały wylogowane.',
    ],

    'validation' => [
        'name_required' => 'Podaj nazwę sondy.',
        'name_max' => 'Nazwa sondy może mieć najwyżej :max znaków.',
        'status_required' => 'Wybierz stan sondy.',
        'status_invalid' => 'Nieznany stan sondy.',
        'email_required' => 'Podaj adres e-mail.',
        'email_invalid' => 'To nie wygląda na adres e-mail.',
        'email_taken' => 'Ten adres e-mail jest już zajęty przez inne konto.',
        'password_required' => 'Podaj hasło.',
        'account_name_required' => 'Podaj imię i nazwisko.',
        'password_not_confirmed' => 'Powtórzone hasło nie jest takie samo.',
        'password_too_short' => 'Hasło musi mieć co najmniej :min znaków.',
    ],

];
