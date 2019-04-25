Öffnungszeitenparser
=====================

This is a PHP class for parsing German opening hours from strings into an associative array.

Usage example:

```php

$string = <<<END

Mo + Do: 9 - 12 u. 14.30 bis 16 Uhr
Mittwoch, Freitag: 9 - 13:30  
Dienstags Ruhetag!

END;
$r = \Wasinger\Oeffnungszeitenparser\Parser::createAndParse($string);
var_export($r);
```

gives the following array:

```php
[
    'mon' =>
        [
            0 =>
                [
                    'begin' => '09:00',
                    'end' => '12:00',
                ],
            1 =>
                [
                    'begin' => '14:30',
                    'end' => '16:00',
                ],
        ],
    'wed' =>
        [
            0 =>
                [
                    'begin' => '09:00',
                    'end' => '13:30',
                ],
        ],
    'thu' =>
        [
            0 =>
                [
                    'begin' => '09:00',
                    'end' => '12:00',
                ],
            1 =>
                [
                    'begin' => '14:30',
                    'end' => '16:00',
                ],
        ],
    'fri' =>
        [
            0 =>
                [
                    'begin' => '09:00',
                    'end' => '13:30',
                ],
        ],
]
```
