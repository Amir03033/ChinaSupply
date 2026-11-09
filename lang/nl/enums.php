<?php

return [
    'user_type' => ['buyer' => 'Koper', 'supplier' => 'Leverancier', 'staff' => 'Medewerker'],
    'staff_role' => ['administrator' => 'Beheerder', 'sourcing_manager' => 'Sourcing manager', 'verification_specialist' => 'Verificatiespecialist'],
    'publication_status' => ['draft' => 'Concept', 'active' => 'Actief', 'inactive' => 'Inactief'],
    'contact_topic' => ['sourcing' => 'Sourcing-aanvraag', 'services' => 'Vraag over diensten en prijzen', 'supplier' => 'Aanmelden als leverancier', 'general' => 'Algemene vraag'],
    'enquiry_status' => ['new' => 'Nieuw', 'in_progress' => 'In behandeling', 'closed' => 'Afgehandeld'],
    'pipeline_status' => ['discovered' => 'Gevonden', 'researching' => 'In onderzoek', 'applied' => 'Aangemeld', 'contacted' => 'Benaderd', 'responded' => 'Gereageerd', 'interested' => 'Geïnteresseerd', 'documents_requested' => 'Documenten opgevraagd', 'verification_pending' => 'Verificatie in behandeling', 'verified' => 'Geverifieerd', 'published' => 'Gepubliceerd', 'rejected' => 'Afgewezen', 'inactive' => 'Inactief'],
    'supplier_source' => ['alibaba' => 'Alibaba', '1688' => '1688', 'global_sources' => 'Global Sources', 'made_in_china' => 'Made-in-China', 'canton_fair' => 'Canton Fair', 'factory_website' => 'Website fabrikant', 'directory' => 'Branchedirectory', 'referral' => 'Doorverwijzing', 'self_registration' => 'Zelfregistratie', 'other' => 'Anders'],
    'company_type' => ['manufacturer' => 'Fabrikant', 'trading_company' => 'Handelsbedrijf', 'hybrid' => 'Fabrikant en handel', 'unknown' => 'Nog niet vastgesteld'],
    'check_status' => ['not_checked' => 'Niet gecontroleerd', 'pending' => 'In behandeling', 'passed' => 'Gecontroleerd', 'failed' => 'Niet goedgekeurd', 'expired' => 'Verlopen'],
    'document_type' => ['business_license' => 'Bedrijfsregistratie (business licence)', 'export_license' => 'Exportvergunning', 'certificate' => 'Certificaat', 'audit_report' => 'Auditrapport', 'factory_photo' => 'Fabrieksfoto', 'product_catalog' => 'Productcatalogus', 'evidence' => 'Verificatiebewijs', 'other' => 'Overig'],
    'document_status' => ['pending' => 'Wordt beoordeeld', 'approved' => 'Goedgekeurd', 'rejected' => 'Afgekeurd'],
    'channel' => ['email' => 'E-mail', 'wechat' => 'WeChat', 'whatsapp' => 'WhatsApp', 'phone' => 'Telefoon', 'video_call' => 'Videogesprek', 'marketplace_chat' => 'Marketplace-chat', 'meeting' => 'Afspraak', 'other' => 'Anders'],
];
