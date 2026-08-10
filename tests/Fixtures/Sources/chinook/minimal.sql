-- Minimal Chinook source fixture for transform testing
-- Uses upstream-shaped schema (integer PKs, original column names)

-- Artists
INSERT INTO chinook_source.artist (artist_id, name) VALUES (1, 'AC/DC');
INSERT INTO chinook_source.artist (artist_id, name) VALUES (2, 'Accept');

-- Albums
INSERT INTO chinook_source.album (album_id, title, artist_id) VALUES (1, 'For Those About To Rock We Salute You', 1);
INSERT INTO chinook_source.album (album_id, title, artist_id) VALUES (2, 'Balls to the Wall', 2);

-- Genres
INSERT INTO chinook_source.genre (genre_id, name) VALUES (1, 'Rock');
INSERT INTO chinook_source.genre (genre_id, name) VALUES (2, 'Metal');

-- Media Types
INSERT INTO chinook_source.media_type (media_type_id, name) VALUES (1, 'MPEG audio file');
INSERT INTO chinook_source.media_type (media_type_id, name) VALUES (2, 'Protected AAC audio file');

-- Employees (with self-reference)
INSERT INTO chinook_source.employee (employee_id, last_name, first_name, title, reports_to, birth_date, hire_date, address, city, state, country, postal_code, phone, fax, email)
VALUES (1, 'Adams', 'Andrew', 'General Manager', NULL, '1962-02-18', '2002-08-14', '11120 Jasper Ave NW', 'Edmonton', 'AB', 'Canada', 'T5K 2N1', '+1 (780) 428-9482', '+1 (780) 428-3457', 'andrew@chinookcorp.com');
INSERT INTO chinook_source.employee (employee_id, last_name, first_name, title, reports_to, birth_date, hire_date, address, city, state, country, postal_code, phone, fax, email)
VALUES (2, 'Edwards', 'Nancy', 'Sales Manager', 1, '1958-12-08', '2002-05-01', '825 8 Ave SW', 'Calgary', 'AB', 'Canada', 'T2P 2T3', '+1 (403) 262-3443', '+1 (403) 262-3322', 'nancy@chinookcorp.com');

-- Customers
INSERT INTO chinook_source.customer (customer_id, first_name, last_name, company, address, city, state, country, postal_code, phone, fax, email, support_rep_id)
VALUES (1, 'Luís', 'Gonçalves', 'Embraer - Empresa Brasileira de Aeronáutica S.A.', 'Av. Brigadeiro Faria Lima, 2170', 'São José dos Campos', 'SP', 'Brazil', '12227-000', '+55 (12) 3923-5555', '+55 (12) 3923-5566', 'luisg@embraer.com.br', 2);

-- Tracks
INSERT INTO chinook_source.track (track_id, name, album_id, media_type_id, genre_id, composer, milliseconds, bytes, unit_price)
VALUES (1, 'For Those About To Rock (We Salute You)', 1, 1, 1, 'Angus Young, Malcolm Young, Brian Johnson', 343719, 11170334, 0.99);
INSERT INTO chinook_source.track (track_id, name, album_id, media_type_id, genre_id, composer, milliseconds, bytes, unit_price)
VALUES (2, 'Balls to the Wall', 2, 2, 2, 'U. Dirkschneider, W. Hoffmann, H. Frank, J. Kaufeld, G. Kolf', 342564, 10815372, 0.99);

-- Playlists
INSERT INTO chinook_source.playlist (playlist_id, name) VALUES (1, 'Music');
INSERT INTO chinook_source.playlist (playlist_id, name) VALUES (2, 'Movies');

-- Playlist Tracks
INSERT INTO chinook_source.playlist_track (playlist_id, track_id) VALUES (1, 1);
INSERT INTO chinook_source.playlist_track (playlist_id, track_id) VALUES (1, 2);

-- Invoices
INSERT INTO chinook_source.invoice (invoice_id, customer_id, invoice_date, billing_address, billing_city, billing_state, billing_country, billing_postal_code, total)
VALUES (1, 1, '2009-01-01 00:00:00', 'Av. Brigadeiro Faria Lima, 2170', 'São José dos Campos', 'SP', 'Brazil', '12227-000', 1.98);

-- Invoice Lines
INSERT INTO chinook_source.invoice_line (invoice_line_id, invoice_id, track_id, unit_price, quantity)
VALUES (1, 1, 1, 0.99, 1);
INSERT INTO chinook_source.invoice_line (invoice_line_id, invoice_id, track_id, unit_price, quantity)
VALUES (2, 1, 2, 0.99, 1);
