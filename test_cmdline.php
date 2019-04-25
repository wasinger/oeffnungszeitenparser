<?php
require __DIR__ . "/vendor/autoload.php";

$test_oeffnungszeiten = [
    "Mo, Mi, Fr 8.00-14.00 Uhr, Di, Do 8.00-16.30 Uhr",
    "Mo, Do 7.30-12.30 Uhr, Mi 7.30-13.00 Uhr",
    "Mo-Fr 7.30-14.30 Uhr ",
    "Di, Fr 7.30-12.30 Uhr, Mi 7.30-13.00 Uhr ",
    "Mo, Fr 7.30-13.00 Uhr, Di, Do 7.30-15.00 Uhr, Mi 7.30-16.00 Uhr ",
    "Mo+Fr 7.00-13.30 Uhr, Di-Do 7.00-16.00 Uhr ",
    "Mo,Mi,Fr  7.30-16.30 Uhr, Di+Fr 7.30-14.30 Uhr",
    "Mo+Fr 7.30-13.30 Uhr, Di-Do 7.30-15.30",
    "Mo, Mi, Fr 8.00-14.00 Uhr, Di, Do 8.00-16.30 Uhr ",
    "Montag bis Mittwoch 7:45 bis 13 und 14 bis 18, Donnerstag u. Freitag 7:45 bis 14 Uhr",
    <<<END
Mo + Do: 9 - 12 u. 14.30 bis 16 Uhr
Mittwoch, Freitag: 9 - 13:30  
Dienstags Ruhetag!
END
];

foreach ($test_oeffnungszeiten as $teststring) {
    $r = \Wasinger\Oeffnungszeitenparser\Parser::createAndParse($teststring);
    echo $teststring . "\n";
    var_export($r);
    echo "\n\n";
}
