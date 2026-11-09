<?php

return [
    'level_names' => [
        0 => 'Not verified',
        1 => 'Identity checked',
        2 => 'Business verified',
        3 => 'Factory verified',
    ],
    'levels' => [
        ['level' => 0, 'short' => 'Registered, nothing checked yet. All information was provided by the supplier.', 'checked' => 'Nothing. The supplier created a profile; we have not yet checked the information.'],
        ['level' => 1, 'short' => 'Company name, contact details and online presence checked.', 'checked' => 'Company identity (name, registration number and legal representative match across sources), contact details and website or marketplace profiles.'],
        ['level' => 2, 'short' => 'Also business registration, address and company type (manufacturer or trader) checked.', 'checked' => 'Everything in level 1, plus the business registration in the official register, the business address and whether the company manufactures or trades.'],
        ['level' => 3, 'short' => 'Also the factory checked, through a live video tour or an on-site inspection.', 'checked' => 'Everything in level 2, plus a live video tour of production or an on-site inspection by an external inspection partner.'],
    ],
    'checks' => [
        'company_identity' => ['name' => 'Company identity'],
        'contact' => ['name' => 'Contact details'],
        'website_domain' => ['name' => 'Website and domain'],
        'business_registration' => ['name' => 'Business registration'],
        'business_address' => ['name' => 'Business address'],
        'factory_existence' => ['name' => 'Factory existence'],
        'certifications' => ['name' => 'Certifications'],
        'production_capabilities' => ['name' => 'Production capabilities'],
        'export_documentation' => ['name' => 'Export documentation'],
        'external_inspection' => ['name' => 'External factory inspection'],
    ],
];
