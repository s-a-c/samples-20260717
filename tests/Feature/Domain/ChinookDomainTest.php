<?php

use App\Domain\Chinook\Models\Album;
use App\Domain\Chinook\Models\Artist;
use App\Domain\Chinook\Models\Customer;
use App\Domain\Chinook\Models\Employee;
use App\Domain\Chinook\Models\Genre;
use App\Domain\Chinook\Models\Invoice;
use App\Domain\Chinook\Models\InvoiceLine;
use App\Domain\Chinook\Models\MediaType;
use App\Domain\Chinook\Models\Playlist;
use App\Domain\Chinook\Models\Track;

test('chinook artist and album models can be persisted and queried', function () {
    $artist = Artist::create([
        'name' => 'AC/DC',
    ]);

    $album = Album::create([
        'title' => 'For Those About To Rock We Salute You',
        'artist_id' => $artist->id,
    ]);

    expect($artist->id)->not->toBeNull();
    expect($album->artist->name)->toBe('AC/DC');
    expect($artist->albums->first()->title)->toBe('For Those About To Rock We Salute You');
});

test('chinook track, genre, media type and playlist relationships work', function () {
    $artist = Artist::create(['name' => 'Queen']);
    $album = Album::create(['title' => 'A Night at the Opera', 'artist_id' => $artist->id]);
    $genre = Genre::create(['name' => 'Rock']);
    $mediaType = MediaType::create(['name' => 'MPEG audio file']);

    $track = Track::create([
        'name' => 'Bohemian Rhapsody',
        'album_id' => $album->id,
        'media_type_id' => $mediaType->id,
        'genre_id' => $genre->id,
        'composer' => 'Freddie Mercury',
        'milliseconds' => 354000,
        'bytes' => 5700000,
        'unit_price' => 0.99,
    ]);

    $playlist = Playlist::create(['name' => 'Classics']);
    $playlist->tracks()->attach($track->id);

    expect($track->album->title)->toBe('A Night at the Opera');
    expect($track->genre->name)->toBe('Rock');
    expect($track->mediaType->name)->toBe('MPEG audio file');
    expect($playlist->tracks->first()->name)->toBe('Bohemian Rhapsody');
    expect($track->playlists->first()->name)->toBe('Classics');
});

test('chinook employee and customer relationships work', function () {
    $manager = Employee::create([
        'first_name' => 'Andrew',
        'last_name' => 'Adams',
        'title' => 'General Manager',
        'email' => 'andrew@chinookcorp.com',
    ]);

    $employee = Employee::create([
        'first_name' => 'Nancy',
        'last_name' => 'Edwards',
        'title' => 'Sales Support Agent',
        'reports_to' => $manager->id,
        'email' => 'nancy@chinookcorp.com',
    ]);

    $customer = Customer::create([
        'first_name' => 'Luís',
        'last_name' => 'Gonçalves',
        'email' => 'luisg@brazil.com',
        'support_rep_id' => $employee->id,
    ]);

    expect($employee->manager->first_name)->toBe('Andrew');
    expect($manager->subordinates->first()->first_name)->toBe('Nancy');
    expect($customer->supportRep->first_name)->toBe('Nancy');
    expect($employee->customers->first()->first_name)->toBe('Luís');
});

test('chinook invoice and invoice line relationships work', function () {
    $artist = Artist::create(['name' => 'AC/DC']);
    $album = Album::create(['title' => 'Let There Be Rock', 'artist_id' => $artist->id]);
    $genre = Genre::create(['name' => 'Rock']);
    $mediaType = MediaType::create(['name' => 'MPEG audio file']);
    $track = Track::create([
        'name' => 'Problem Child',
        'album_id' => $album->id,
        'media_type_id' => $mediaType->id,
        'genre_id' => $genre->id,
        'milliseconds' => 325000,
        'unit_price' => 0.99,
    ]);

    $customer = Customer::create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
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

    expect($invoice->customer->first_name)->toBe('Jane');
    expect($invoice->invoiceLines->first()->track->name)->toBe('Problem Child');
    expect($invoiceLine::class)->toBe(InvoiceLine::class);
});
