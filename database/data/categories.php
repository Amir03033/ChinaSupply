<?php

/**
 * Initial taxonomy. Format: [en, nl, icon?, children?]. Children use the same
 * format (icon omitted). Seeded once; afterwards the taxonomy is managed in
 * Filament and this file is no longer the source of truth.
 */
return [
    ['Electronics & Technology', 'Elektronica & Technologie', 'cpu-chip', [
        ['Consumer Electronics', 'Consumentenelektronica', [
            ['Headphones & Earbuds', 'Koptelefoons & oordopjes'], ['Speakers', 'Speakers'], ['Smart Watches & Wearables', 'Smartwatches & wearables'], ['Cameras & Accessories', 'Camera\'s & accessoires'],
        ]],
        ['Phone & Computer Accessories', 'Telefoon- & computeraccessoires', [
            ['Chargers & Cables', 'Opladers & kabels'], ['Power Banks', 'Powerbanks'], ['Phone Cases', 'Telefoonhoesjes'], ['Keyboards & Mice', 'Toetsenborden & muizen'],
        ]],
        ['Smart Home & IoT', 'Smart home & IoT'],
        ['LED & Lighting', 'LED & verlichting', [
            ['LED Bulbs & Tubes', 'LED-lampen & -buizen'], ['LED Strips', 'LED-strips'], ['Commercial Lighting', 'Zakelijke verlichting'],
        ]],
        ['Electronic Components & PCB', 'Elektronische componenten & PCB'],
        ['Audio & Video Equipment', 'Audio- & videoapparatuur'],
    ]],
    ['Clothing, Textiles & Fashion', 'Kleding, Textiel & Mode', 'swatch', [
        ['Women\'s Clothing', 'Dameskleding'],
        ['Men\'s Clothing', 'Herenkleding'],
        ['Children\'s Clothing', 'Kinderkleding'],
        ['Sportswear & Activewear', 'Sportkleding'],
        ['Workwear & Uniforms', 'Werkkleding & uniformen'],
        ['Footwear', 'Schoenen'],
        ['Bags & Luggage', 'Tassen & bagage', [
            ['Tote Bags', 'Draagtassen'], ['Backpacks', 'Rugzakken'], ['Suitcases', 'Koffers'],
        ]],
        ['Fabrics & Textile Materials', 'Stoffen & textielmaterialen'],
        ['Home Textiles', 'Woontextiel'],
    ]],
    ['Home, Living & Interior', 'Wonen, Interieur & Huishouden', 'home-modern', [
        ['Furniture', 'Meubels', [
            ['Indoor Furniture', 'Binnenmeubels'], ['Office Furniture', 'Kantoormeubels'], ['Outdoor Furniture', 'Buitenmeubels'],
        ]],
        ['Kitchen & Dining', 'Keuken & tafelen', [
            ['Cookware', 'Kookgerei'], ['Tableware', 'Servies'], ['Kitchen Tools & Gadgets', 'Keukengerei'], ['Drinkware & Bottles', 'Drinkbekers & flessen'],
        ]],
        ['Home Decor', 'Woondecoratie'],
        ['Bathroom', 'Badkamer'],
        ['Storage & Organisation', 'Opbergen & organiseren'],
        ['Household Cleaning', 'Schoonmaakartikelen'],
        ['Small Home Appliances', 'Kleine huishoudelijke apparaten'],
    ]],
    ['Packaging & Printing', 'Verpakking & Drukwerk', 'archive-box', [
        ['Boxes & Cartons', 'Dozen & kartonnage', [
            ['Shipping Boxes', 'Verzenddozen'], ['Gift Boxes', 'Geschenkdozen'], ['Mailer Boxes', 'Mailerboxen'],
        ]],
        ['Flexible Packaging & Pouches', 'Flexibele verpakkingen & zakjes'],
        ['Labels & Stickers', 'Etiketten & stickers'],
        ['Paper & Shopping Bags', 'Papieren & shoppingtassen'],
        ['Bottles, Jars & Containers', 'Flessen, potten & containers'],
        ['Sustainable Packaging', 'Duurzame verpakkingen'],
        ['Printing Services', 'Drukwerkdiensten'],
    ]],
    ['Beauty, Cosmetics & Personal Care', 'Beauty, Cosmetica & Persoonlijke Verzorging', 'sparkles', [
        ['Skincare', 'Huidverzorging'],
        ['Makeup', 'Make-up'],
        ['Hair Care & Styling Tools', 'Haarverzorging & stylingtools'],
        ['Nail Care', 'Nagelverzorging'],
        ['Personal Hygiene', 'Persoonlijke hygiëne'],
        ['Cosmetic Packaging', 'Cosmeticaverpakkingen'],
        ['Beauty Devices', 'Beauty-apparaten'],
    ]],
    ['Sports, Fitness & Outdoor', 'Sport, Fitness & Outdoor', 'trophy', [
        ['Fitness Equipment', 'Fitnessapparatuur', [
            ['Yoga & Pilates', 'Yoga & pilates'], ['Weights & Strength', 'Gewichten & kracht'], ['Cardio Machines', 'Cardio-apparaten'],
        ]],
        ['Camping & Hiking', 'Kamperen & wandelen'],
        ['Cycling', 'Fietsen'],
        ['Water Sports', 'Watersport'],
        ['Team Sports', 'Teamsporten'],
        ['Fishing', 'Vissen'],
    ]],
    ['Automotive, Motorcycle & Transport', 'Auto, Motor & Transport', 'truck', [
        ['Car Parts', 'Auto-onderdelen'],
        ['Car Accessories', 'Auto-accessoires'],
        ['Motorcycle Parts & Accessories', 'Motoronderdelen & -accessoires'],
        ['E-bikes & E-scooters', 'E-bikes & e-steps'],
        ['Tyres & Wheels', 'Banden & velgen'],
        ['Vehicle Electronics', 'Voertuigelektronica'],
    ]],
    ['Toys, Children & Baby Products', 'Speelgoed, Kinder- & Babyproducten', 'puzzle-piece', [
        ['Toys', 'Speelgoed', [
            ['Educational Toys', 'Educatief speelgoed'], ['Plush Toys', 'Knuffels'], ['Outdoor Toys', 'Buitenspeelgoed'],
        ]],
        ['Baby Care', 'Babyverzorging'],
        ['Strollers & Car Seats', 'Kinderwagens & autostoeltjes'],
        ['Nursery Furniture', 'Babykamermeubels'],
        ['Party Supplies', 'Feestartikelen'],
    ]],
    ['Industrial Products & Machinery', 'Industriële Producten & Machines', 'cog-6-tooth', [
        ['Industrial Machinery', 'Industriële machines'],
        ['Packaging Machinery', 'Verpakkingsmachines'],
        ['Tools & Hardware', 'Gereedschap & ijzerwaren'],
        ['Pumps, Valves & Pipes', 'Pompen, kleppen & leidingen'],
        ['Bearings & Transmission Parts', 'Lagers & aandrijvingsonderdelen'],
        ['Fasteners', 'Bevestigingsmaterialen'],
        ['Electrical Equipment', 'Elektrotechnische apparatuur'],
    ]],
    ['Construction & Building Materials', 'Bouw & Bouwmaterialen', 'building-office-2', [
        ['Flooring', 'Vloeren'],
        ['Tiles & Ceramics', 'Tegels & keramiek'],
        ['Doors & Windows', 'Deuren & ramen'],
        ['Sanitary Ware', 'Sanitair'],
        ['Insulation', 'Isolatie'],
        ['Steel & Metal Structures', 'Staal- & metaalconstructies'],
        ['Prefab & Modular Buildings', 'Prefab & modulaire bouw'],
    ]],
    ['Garden, Agriculture & Pets', 'Tuin, Landbouw & Dieren', 'sun', [
        ['Garden Tools & Equipment', 'Tuingereedschap'],
        ['Planters & Garden Decor', 'Plantenbakken & tuindecoratie'],
        ['Irrigation', 'Irrigatie'],
        ['Agricultural Equipment', 'Landbouwmaterieel'],
        ['Pet Supplies', 'Dierbenodigdheden', [
            ['Pet Toys', 'Dierenspeelgoed'], ['Pet Beds & Furniture', 'Manden & meubels'], ['Leashes & Collars', 'Lijnen & halsbanden'],
        ]],
    ]],
    ['Office, School & Business Supplies', 'Kantoor-, School- & Zakelijke Artikelen', 'briefcase', [
        ['Stationery', 'Schrijfwaren'],
        ['Office Equipment', 'Kantoorapparatuur'],
        ['School Supplies', 'Schoolartikelen'],
        ['Notebooks & Paper Products', 'Notitieboeken & papierwaren'],
        ['Display & Presentation', 'Display & presentatie'],
    ]],
    ['Gifts, Promotional Products & Merchandise', 'Cadeaus, Relatiegeschenken & Merchandise', 'gift', [
        ['Promotional Products', 'Promotieartikelen'],
        ['Corporate Gifts', 'Relatiegeschenken'],
        ['Custom Merchandise', 'Merchandise op maat'],
        ['Seasonal & Holiday Products', 'Seizoens- & feestdagartikelen'],
        ['Trophies & Awards', 'Trofeeën & awards'],
    ]],
    ['Jewelry, Watches & Accessories', 'Sieraden, Horloges & Accessoires', 'star', [
        ['Fashion Jewelry', 'Modesieraden'],
        ['Fine Jewelry', 'Edelsmeedwerk'],
        ['Watches', 'Horloges'],
        ['Sunglasses & Eyewear', 'Zonnebrillen & brillen'],
        ['Hair Accessories', 'Haaraccessoires'],
        ['Belts, Wallets & Small Leather Goods', 'Riemen, portemonnees & kleine lederwaren'],
    ]],
    ['Food, Beverage & Foodservice', 'Voeding, Dranken & Foodservice', 'cake', [
        ['Packaged Food', 'Verpakte voeding'],
        ['Beverages', 'Dranken'],
        ['Tea & Coffee', 'Thee & koffie'],
        ['Food Processing Equipment', 'Voedselverwerkingsapparatuur'],
        ['Disposable Foodservice Products', 'Wegwerpartikelen voor foodservice'],
    ]],
    ['Energy & Sustainable Technology', 'Energie & Duurzame Technologie', 'bolt', [
        ['Solar Panels & Components', 'Zonnepanelen & componenten'],
        ['Batteries & Energy Storage', 'Batterijen & energieopslag'],
        ['EV Charging', 'Laadpalen'],
        ['Heat Pumps', 'Warmtepompen'],
        ['Inverters & Power Electronics', 'Omvormers & vermogenselektronica'],
    ]],
    ['Safety & Security', 'Veiligheid & Beveiliging', 'shield-check', [
        ['Personal Protective Equipment (PPE)', 'Persoonlijke beschermingsmiddelen (PBM)'],
        ['CCTV & Surveillance', 'Camerabewaking'],
        ['Access Control & Locks', 'Toegangscontrole & sloten'],
        ['Fire Safety', 'Brandveiligheid'],
        ['Alarm Systems', 'Alarmsystemen'],
    ]],
    ['Raw Materials & Semi-Finished Products', 'Grondstoffen & Halffabricaten', 'cube', [
        ['Plastics & Polymers', 'Kunststoffen & polymeren'],
        ['Metals & Alloys', 'Metalen & legeringen'],
        ['Chemicals', 'Chemicaliën'],
        ['Wood & Panels', 'Hout & plaatmateriaal'],
        ['Glass', 'Glas'],
        ['Rubber & Silicone', 'Rubber & siliconen'],
    ]],
    ['Hospitality, Retail & Professional Equipment', 'Horeca, Retail & Professionele Apparatuur', 'building-storefront', [
        ['Commercial Kitchen Equipment', 'Professionele keukenapparatuur'],
        ['Hotel Supplies', 'Hotelbenodigdheden'],
        ['Retail Display & Shop Fittings', 'Winkelinrichting & displays'],
        ['Medical & Laboratory Supplies', 'Medische & laboratoriumbenodigdheden'],
        ['Beauty Salon Equipment', 'Salonapparatuur'],
    ]],
    ['Custom Manufacturing & OEM/ODM', 'Productie op Maat & OEM/ODM', 'wrench-screwdriver', [
        ['Injection Moulding', 'Kunststof spuitgieten'],
        ['CNC Machining', 'CNC-bewerking'],
        ['Metal Fabrication & Stamping', 'Metaalbewerking & stansen'],
        ['Die Casting', 'Drukgieten (die casting)'],
        ['3D Printing & Prototyping', '3D-printen & prototyping'],
        ['Private Label Production', 'Private label-productie'],
        ['Product Design & Development', 'Productontwerp & -ontwikkeling'],
    ]],
];
