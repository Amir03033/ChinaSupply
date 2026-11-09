<?php

return [
    'how' => [
        'title' => 'How ChinaSupply works',
        'meta' => 'How to find a manufacturer in China through ChinaSupply: search the directory yourself or submit a managed sourcing request.',
        'lead' => 'There are two ways to find a manufacturer with ChinaSupply. You can also combine them.',
        'self' => [
            'title' => 'Search yourself',
            'lead' => 'For those who know what they need and want to compare suppliers themselves.',
            'steps' => [
                ['title' => 'Choose a category', 'text' => 'Browse the categories or search by product name.'],
                ['title' => 'View supplier profiles', 'text' => 'For each supplier you see what they make, minimum order quantities and what we checked.'],
                ['title' => 'Request a quotation via ChinaSupply', 'text' => 'We forward your request and keep you informed.'],
            ],
        ],
        'managed' => [
            'title' => 'Let us search',
            'lead' => 'For those who want to save time or are importing for the first time.',
            'steps' => [
                ['title' => 'Describe your product', 'text' => 'What, how many, which budget and which requirements. Photos or examples help.'],
                ['title' => 'We review your request', 'text' => 'Within two working days we tell you what is feasible and which approach we recommend.'],
                ['title' => 'We find and approach manufacturers', 'text' => 'We request quotations and documents and check the key information.'],
                ['title' => 'You receive a comparison', 'text' => 'Prices, minimum quantities, lead times and our advice, side by side.'],
            ],
        ],
        'role' => [
            'title' => 'What ChinaSupply does and does not do',
            'points' => [
                'We find, approach and check suppliers and advise you on your choice.',
                'You agree the purchase and payment for products directly with the supplier. ChinaSupply is not a party to that agreement.',
                'You are the importer and responsible for product safety, CE marking and customs. We point you in the right direction and refer you to specialists.',
                'Physical factory inspections are carried out by external inspection partners; we coordinate them.',
            ],
        ],
        'faq_title' => 'Frequently asked questions',
        'faq' => [
            ['q' => 'What does a request cost?', 'a' => 'Your first request is free. If you want us to actively search for suppliers and compare quotations, we offer paid packages. You always know the price in advance.'],
            ['q' => 'What is an MOQ?', 'a' => 'MOQ stands for minimum order quantity: the minimum number of units a manufacturer is willing to produce per order. It varies widely by product and manufacturer.'],
            ['q' => 'Is a verified supplier a guarantee?', 'a' => 'No. Verification means we checked specific information on a specific date. It is not a guarantee of product quality or delivery.'],
            ['q' => 'Do I need an account?', 'a' => 'Not for a first request. An account is useful to follow the progress of your requests.'],
        ],
    ],
    'verification' => [
        'title' => 'How verification works',
        'meta' => 'What ChinaSupply checks for suppliers, what the verification levels mean and why verification is never for sale.',
        'lead' => 'We do not hand out a generic badge. Every supplier receives a level that follows automatically from individual, dated checks with evidence.',
        'levels_title' => 'The levels',
        'col_level' => 'Level',
        'col_checked' => 'What was checked',
        'means' => [
            'title' => 'What verification means',
            'points' => [
                'We checked the stated information on the stated date.',
                'Every check has a date and, where relevant, an expiry date.',
                'For each supplier you can see which checks were and were not done.',
            ],
        ],
        'not_means' => [
            'title' => 'What verification does not mean',
            'points' => [
                'No guarantee of product quality, safety or delivery time.',
                'No certification that your product complies with European rules.',
                'No judgement about the supplier\'s future conduct.',
            ],
        ],
        'independence' => [
            'title' => 'Independent from payment',
            'rules' => [
                'Payment never affects a verification level or the outcome of a check.',
                'Sponsored listings are always clearly marked as sponsored.',
                'In managed requests we advise based on fit, not on a subscription.',
            ],
        ],
        'expiry' => 'Checks expire over time. If a check expires and is not renewed, the level drops automatically. Also read the',
    ],
    'suppliers' => [
        'title' => 'For suppliers and manufacturers',
        'meta' => 'Chinese manufacturers and suppliers: reach European business buyers through ChinaSupply. Listing and verification are free.',
        'lead' => 'ChinaSupply connects Chinese manufacturers with serious business buyers from the Netherlands and Europe.',
        'benefits' => [
            ['title' => 'Qualified requests', 'text' => 'We review buyer requests before forwarding them.'],
            ['title' => 'Free verification', 'text' => 'Listing and verification are free. Verification is never for sale.'],
            ['title' => 'Honest presentation', 'text' => 'Your profile shows what you make and what we checked — no more, no less.'],
        ],
        'process_title' => 'How joining works',
        'process' => [
            'You register your company with the basic details.',
            'You upload your business registration and, if available, certificates and factory photos.',
            'We check the information and contact you with any questions.',
            'After approval your profile is published.',
        ],
        'registration_notice' => 'Online self-registration for suppliers is coming soon. Until then you can apply through the contact form.',
        'cta' => 'Register your company',
    ],
    'about' => [
        'title' => 'About ChinaSupply',
        'meta' => 'ChinaSupply is a Dutch service that helps businesses find reliable manufacturers in China.',
        'lead' => 'We make sourcing in China clear and honest.',
        'paragraphs' => [
            'Many entrepreneurs want to manufacture in China but get stuck in cluttered marketplaces, unclear badges and intermediaries who do not show what they earn.',
            'ChinaSupply does it differently: we show exactly what we know about a supplier and how we checked it, we charge prices known in advance and we accept no hidden commissions from suppliers.',
            'ChinaSupply is a service of a Dutch private limited company (BV).',
        ],
        'company' => 'Company',
        'address' => 'Address',
        'email' => 'Email',
    ],
    'cms' => [
        'updated' => 'Last updated: :date',
        'legal_review_notice' => 'This document is a draft and has not yet been reviewed by a lawyer. The final text will follow.',
    ],
];
