CREATE TABLE foundations (
    gov_id VARCHAR(200) NOT NULL PRIMARY KEY UNIQUE,
    name TEXT,
    address TEXT,
    zip_code TINYTEXT,
    creation_date TIMESTAMP,
    country TINYTEXT,
    geolocation TINYTEXT,
    video TINYTEXT,
    logo TINYTEXT,
    categorie TINYTEXT,
    legal_type TINYTEXT,
    website TINYTEXT,
    wikidata TINYTEXT,
    minimum_revenue NUMERIC(15,2),
    activated BOOLEAN DEFAULT TRUE
);
