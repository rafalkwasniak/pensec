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

    'validation' => [
        'name_required' => 'Podaj nazwę sondy.',
        'name_max' => 'Nazwa sondy może mieć najwyżej :max znaków.',
        'status_required' => 'Wybierz stan sondy.',
        'status_invalid' => 'Nieznany stan sondy.',
        'email_required' => 'Podaj adres e-mail.',
        'email_invalid' => 'To nie wygląda na adres e-mail.',
        'password_required' => 'Podaj hasło.',
    ],

];
