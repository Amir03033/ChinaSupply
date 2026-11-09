<?php

return [
    'level_names' => [
        0 => 'Niet geverifieerd',
        1 => 'Identiteit gecontroleerd',
        2 => 'Bedrijf geverifieerd',
        3 => 'Fabriek geverifieerd',
    ],
    'levels' => [
        ['level' => 0, 'short' => 'Geregistreerd, nog niets gecontroleerd. Alle gegevens zijn door de leverancier zelf opgegeven.', 'checked' => 'Niets. De leverancier heeft een profiel aangemaakt; de gegevens zijn nog niet door ons gecontroleerd.'],
        ['level' => 1, 'short' => 'Bedrijfsnaam, contactgegevens en online aanwezigheid gecontroleerd.', 'checked' => 'Bedrijfsidentiteit (naam, registratienummer en vertegenwoordiger komen overeen tussen bronnen), contactgegevens en website of marketplaceprofielen.'],
        ['level' => 2, 'short' => 'Ook bedrijfsregistratie, adres en type bedrijf (fabrikant of handel) gecontroleerd.', 'checked' => 'Alles van niveau 1, plus de bedrijfsregistratie in het officiële register, het bedrijfsadres en of het bedrijf zelf produceert of handelt.'],
        ['level' => 3, 'short' => 'Ook de fabriek gecontroleerd, via een live videorondleiding of een inspectie ter plaatse.', 'checked' => 'Alles van niveau 2, plus een live videorondleiding door de productie of een inspectie ter plaatse door een externe inspectiepartner.'],
    ],
];
