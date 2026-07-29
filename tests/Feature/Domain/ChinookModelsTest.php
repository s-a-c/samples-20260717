<?php

declare(strict_types=1);

use App\Enums\SamplesProduct;
use App\Models\Chinook\Album;
use App\Models\Chinook\Artist;
use App\Models\Chinook\Customer;
use App\Models\Chinook\Employee;
use App\Models\Chinook\Genre;
use App\Models\Chinook\Invoice;
use App\Models\Chinook\InvoiceLine;
use App\Models\Chinook\MediaType;
use App\Models\Chinook\Playlist;
use App\Models\Chinook\PlaylistTrack;
use App\Models\Chinook\Track;

covers(
    Album::class,
    Customer::class,
    Employee::class,
    Genre::class,
    Invoice::class,
    InvoiceLine::class,
    MediaType::class,
    Playlist::class,
    PlaylistTrack::class,
    Track::class,
);

test('chinook models report their product domain', function () {
    expect((new Album)->getProductDomain())->toBe(SamplesProduct::Chinook)
        ->and((new Customer)->getProductDomain())->toBe(SamplesProduct::Chinook)
        ->and((new Employee)->getProductDomain())->toBe(SamplesProduct::Chinook)
        ->and((new Genre)->getProductDomain())->toBe(SamplesProduct::Chinook)
        ->and((new Invoice)->getProductDomain())->toBe(SamplesProduct::Chinook)
        ->and((new InvoiceLine)->getProductDomain())->toBe(SamplesProduct::Chinook)
        ->and((new MediaType)->getProductDomain())->toBe(SamplesProduct::Chinook)
        ->and((new Playlist)->getProductDomain())->toBe(SamplesProduct::Chinook)
        ->and((new PlaylistTrack)->getProductDomain())->toBe(SamplesProduct::Chinook)
        ->and((new Track)->getProductDomain())->toBe(SamplesProduct::Chinook);
});

test('chinook album relations resolve correctly', function () {
    $artist = Artist::create(['name' => 'Pink Floyd']);
    $album = Album::create(['title' => 'The Wall', 'artist_id' => $artist->id]);
    $genre = Genre::create(['name' => 'Rock']);
    $mediaType = MediaType::create(['name' => 'MPEG audio file']);
    Track::create([
        'name' => 'Another Brick',
        'album_id' => $album->id,
        'media_type_id' => $mediaType->id,
        'genre_id' => $genre->id,
        'milliseconds' => 121000,
        'unit_price' => 0.99,
    ]);

    expect($album->artist->name)->toBe('Pink Floyd')
        ->and($album->tracks->first()->name)->toBe('Another Brick');
});

test('chinook employee self-referential and customer relations resolve', function () {
    $manager = Employee::create([
        'first_name' => 'Andrew',
        'last_name' => 'Adams',
        'email' => 'andrew@chinook.test',
    ]);

    $employee = Employee::create([
        'first_name' => 'Nancy',
        'last_name' => 'Edwards',
        'reports_to' => $manager->id,
        'email' => 'nancy@chinook.test',
    ]);

    $customer = Customer::create([
        'first_name' => 'Luís',
        'last_name' => 'Gonçalves',
        'email' => 'luis@chinook.test',
        'support_rep_id' => $employee->id,
    ]);

    expect($employee->manager->first_name)->toBe('Andrew')
        ->and($manager->subordinates->first()->first_name)->toBe('Nancy')
        ->and($employee->customers->first()->first_name)->toBe('Luís');
});

test('chinook customer support rep and invoice relations resolve', function () {
    $employee = Employee::create([
        'first_name' => 'Jane',
        'last_name' => 'Peacock',
        'email' => 'jane@chinook.test',
    ]);

    $customer = Customer::create([
        'first_name' => 'Frank',
        'last_name' => 'Harris',
        'email' => 'frank@chinook.test',
        'support_rep_id' => $employee->id,
    ]);

    $invoice = Invoice::create([
        'customer_id' => $customer->id,
        'invoice_date' => now(),
        'total' => 1.98,
    ]);

    expect($customer->supportRep->first_name)->toBe('Jane')
        ->and($customer->invoices->first()->id)->toBe($invoice->id);
});

test('chinook genre and media type track relations resolve', function () {
    $artist = Artist::create(['name' => 'Queen']);
    $album = Album::create(['title' => 'News of the World', 'artist_id' => $artist->id]);
    $genre = Genre::create(['name' => 'Rock']);
    $mediaType = MediaType::create(['name' => 'MPEG audio file']);

    Track::create([
        'name' => 'We Will Rock You',
        'album_id' => $album->id,
        'media_type_id' => $mediaType->id,
        'genre_id' => $genre->id,
        'milliseconds' => 121000,
        'unit_price' => 0.99,
    ]);

    expect($genre->tracks->first()->name)->toBe('We Will Rock You')
        ->and($mediaType->tracks->first()->name)->toBe('We Will Rock You');
});

test('chinook track relations resolve correctly', function () {
    $artist = Artist::create(['name' => 'AC/DC']);
    $album = Album::create(['title' => 'Back in Black', 'artist_id' => $artist->id]);
    $genre = Genre::create(['name' => 'Rock']);
    $mediaType = MediaType::create(['name' => 'MPEG audio file']);

    $track = Track::create([
        'name' => 'Hells Bells',
        'album_id' => $album->id,
        'media_type_id' => $mediaType->id,
        'genre_id' => $genre->id,
        'milliseconds' => 308000,
        'unit_price' => 0.99,
    ]);

    $playlist = Playlist::create(['name' => 'Rock Classics']);
    $playlist->tracks()->attach($track->id);

    $customer = Customer::create([
        'first_name' => 'Test',
        'last_name' => 'Buyer',
        'email' => 'buyer@chinook.test',
    ]);

    $invoice = Invoice::create([
        'customer_id' => $customer->id,
        'invoice_date' => now(),
        'total' => 0.99,
    ]);

    $invoiceLine = InvoiceLine::create([
        'invoice_id' => $invoice->id,
        'track_id' => $track->id,
        'unit_price' => 0.99,
        'quantity' => 1,
    ]);

    expect($track->album->title)->toBe('Back in Black')
        ->and($track->mediaType->name)->toBe('MPEG audio file')
        ->and($track->genre->name)->toBe('Rock')
        ->and($track->playlists->first()->name)->toBe('Rock Classics');
    expect($track->invoiceLines->first()->id)->toBe($invoiceLine->id);
});

test('chinook playlist and playlist track pivot relations resolve', function () {
    $artist = Artist::create(['name' => 'Led Zeppelin']);
    $album = Album::create(['title' => 'IV', 'artist_id' => $artist->id]);
    $genre = Genre::create(['name' => 'Rock']);
    $mediaType = MediaType::create(['name' => 'MPEG audio file']);
    $track = Track::create([
        'name' => 'Stairway to Heaven',
        'album_id' => $album->id,
        'media_type_id' => $mediaType->id,
        'genre_id' => $genre->id,
        'milliseconds' => 482000,
        'unit_price' => 0.99,
    ]);

    $playlist = Playlist::create(['name' => 'Epic Solos']);
    $playlist->tracks()->attach($track->id);

    expect($playlist->tracks->first()->name)->toBe('Stairway to Heaven');

    $pivot = PlaylistTrack::where('playlist_id', $playlist->id)
        ->where('track_id', $track->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->playlist->id)->toBe($playlist->id);
    expect($pivot->track->id)->toBe($track->id);
});

test('chinook invoice and invoice line relations resolve', function () {
    $artist = Artist::create(['name' => 'The Beatles']);
    $album = Album::create(['title' => 'Abbey Road', 'artist_id' => $artist->id]);
    $genre = Genre::create(['name' => 'Rock']);
    $mediaType = MediaType::create(['name' => 'MPEG audio file']);
    $track = Track::create([
        'name' => 'Come Together',
        'album_id' => $album->id,
        'media_type_id' => $mediaType->id,
        'genre_id' => $genre->id,
        'milliseconds' => 259000,
        'unit_price' => 0.99,
    ]);

    $customer = Customer::create([
        'first_name' => 'Music',
        'last_name' => 'Fan',
        'email' => 'fan@chinook.test',
    ]);

    $invoice = Invoice::create([
        'customer_id' => $customer->id,
        'invoice_date' => now(),
        'total' => 0.99,
    ]);

    $invoiceLine = InvoiceLine::create([
        'invoice_id' => $invoice->id,
        'track_id' => $track->id,
        'unit_price' => 0.99,
        'quantity' => 1,
    ]);

    expect($invoice->customer->first_name)->toBe('Music')
        ->and($invoice->invoiceLines->first()->id)->toBe($invoiceLine->id);
    expect($invoiceLine->invoice->id)->toBe($invoice->id);
    expect($invoiceLine->track->name)->toBe('Come Together');
});
