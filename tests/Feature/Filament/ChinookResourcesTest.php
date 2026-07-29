<?php

declare(strict_types=1);

use App\Filament\Chinook\Resources\AlbumResource;
use App\Filament\Chinook\Resources\AlbumResource\Pages\EditAlbum;
use App\Filament\Chinook\Resources\AlbumResource\Pages\ListAlbums;
use App\Filament\Chinook\Resources\ArtistResource;
use App\Filament\Chinook\Resources\ArtistResource\Pages\EditArtist;
use App\Filament\Chinook\Resources\ArtistResource\Pages\ListArtists;
use App\Filament\Chinook\Resources\CustomerResource;
use App\Filament\Chinook\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Chinook\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Chinook\Resources\EmployeeResource;
use App\Filament\Chinook\Resources\EmployeeResource\Pages\EditEmployee;
use App\Filament\Chinook\Resources\EmployeeResource\Pages\ListEmployees;
use App\Filament\Chinook\Resources\GenreResource;
use App\Filament\Chinook\Resources\GenreResource\Pages\EditGenre;
use App\Filament\Chinook\Resources\GenreResource\Pages\ListGenres;
use App\Filament\Chinook\Resources\InvoiceResource;
use App\Filament\Chinook\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Chinook\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Chinook\Resources\PlaylistResource;
use App\Filament\Chinook\Resources\PlaylistResource\Pages\EditPlaylist;
use App\Filament\Chinook\Resources\PlaylistResource\Pages\ListPlaylists;
use App\Filament\Chinook\Resources\TrackResource;
use App\Filament\Chinook\Resources\TrackResource\Pages\EditTrack;
use App\Filament\Chinook\Resources\TrackResource\Pages\ListTracks;
use App\Models\Chinook\Album;
use App\Models\Chinook\Artist;
use App\Models\Chinook\Customer;
use App\Models\Chinook\Employee;
use App\Models\Chinook\Genre;
use App\Models\Chinook\Invoice;
use App\Models\Chinook\MediaType;
use App\Models\Chinook\Playlist;
use App\Models\Chinook\Track;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

covers(
    AlbumResource::class,
    ListAlbums::class,
    EditAlbum::class,
    ArtistResource::class,
    ListArtists::class,
    EditArtist::class,
    CustomerResource::class,
    ListCustomers::class,
    EditCustomer::class,
    EmployeeResource::class,
    ListEmployees::class,
    EditEmployee::class,
    GenreResource::class,
    ListGenres::class,
    EditGenre::class,
    InvoiceResource::class,
    ListInvoices::class,
    EditInvoice::class,
    PlaylistResource::class,
    ListPlaylists::class,
    EditPlaylist::class,
    TrackResource::class,
    ListTracks::class,
    EditTrack::class,
);

beforeEach(function () {
    $this->curator = User::factory()->create();
    $this->curator->assignRole(Role::findOrCreate('chinook_curator', 'web'));
});

test('chinook curator can access all chinook resource list pages', function () {
    $endpoints = [
        '/chinook/artists',
        '/chinook/albums',
        '/chinook/tracks',
        '/chinook/playlists',
        '/chinook/customers',
        '/chinook/employees',
        '/chinook/invoices',
        '/chinook/genres',
    ];

    foreach ($endpoints as $endpoint) {
        $this->actingAs($this->curator)
            ->get($endpoint)
            ->assertSuccessful();
    }
});

test('artist resource renders columns and data', function () {
    $artist = Artist::create(['name' => 'Queen']);

    $this->actingAs($this->curator)
        ->get('/chinook/artists')
        ->assertSuccessful()
        ->assertSee('Queen');

    Livewire::test(ListArtists::class)
        ->assertCanSeeTableRecords([$artist])
        ->assertTableColumnExists('name');
});

test('album resource renders columns and data', function () {
    $artist = Artist::create(['name' => 'Queen']);
    $album = Album::create(['title' => 'A Night at the Opera', 'artist_id' => $artist->id]);

    $this->actingAs($this->curator)
        ->get('/chinook/albums')
        ->assertSuccessful()
        ->assertSee('A Night at the Opera');

    Livewire::test(ListAlbums::class)
        ->assertCanSeeTableRecords([$album])
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('artist.name');
});

test('track resource renders columns and data', function () {
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
        'unit_price' => 0.99,
    ]);

    $this->actingAs($this->curator)
        ->get('/chinook/tracks')
        ->assertSuccessful()
        ->assertSee('Bohemian Rhapsody');

    Livewire::test(ListTracks::class)
        ->assertCanSeeTableRecords([$track])
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('album.title')
        ->assertTableColumnExists('genre.name')
        ->assertTableColumnExists('composer');
});

test('playlist resource renders columns and data', function () {
    $playlist = Playlist::create(['name' => 'Classic Rock Hits']);

    $this->actingAs($this->curator)
        ->get('/chinook/playlists')
        ->assertSuccessful()
        ->assertSee('Classic Rock Hits');

    Livewire::test(ListPlaylists::class)
        ->assertCanSeeTableRecords([$playlist])
        ->assertTableColumnExists('name');
});

test('employee resource renders columns and data', function () {
    $employee = Employee::create([
        'first_name' => 'Andrew',
        'last_name' => 'Adams',
        'title' => 'General Manager',
        'email' => 'andrew@chinook.test',
        'phone' => '+1 (780) 428-9482',
    ]);

    $this->actingAs($this->curator)
        ->get('/chinook/employees')
        ->assertSuccessful()
        ->assertSee('Adams');

    Livewire::test(ListEmployees::class)
        ->assertCanSeeTableRecords([$employee])
        ->assertTableColumnExists('first_name')
        ->assertTableColumnExists('last_name')
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('email');
});

test('customer resource renders columns and data', function () {
    $employee = Employee::create([
        'first_name' => 'Jane',
        'last_name' => 'Peacock',
        'email' => 'jane@chinook.test',
    ]);

    $customer = Customer::create([
        'first_name' => 'Luís',
        'last_name' => 'Gonçalves',
        'email' => 'luisg@brazil.test',
        'company' => 'Embraer',
        'city' => 'São José dos Campos',
        'country' => 'Brazil',
        'support_rep_id' => $employee->id,
    ]);

    $this->actingAs($this->curator)
        ->get('/chinook/customers')
        ->assertSuccessful()
        ->assertSee('Gonçalves');

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer])
        ->assertTableColumnExists('first_name')
        ->assertTableColumnExists('last_name')
        ->assertTableColumnExists('email')
        ->assertTableColumnExists('country');
});

test('invoice resource renders columns and data', function () {
    $customer = Customer::create([
        'first_name' => 'Luís',
        'last_name' => 'Gonçalves',
        'email' => 'luisg@brazil.test',
    ]);

    $invoice = Invoice::create([
        'customer_id' => $customer->id,
        'invoice_date' => '2026-01-01 00:00:00',
        'total' => 18.81,
        'billing_country' => 'Brazil',
    ]);

    $this->actingAs($this->curator)
        ->get('/chinook/invoices')
        ->assertSuccessful()
        ->assertSee('Brazil');

    Livewire::test(ListInvoices::class)
        ->assertCanSeeTableRecords([$invoice])
        ->assertTableColumnExists('customer.last_name')
        ->assertTableColumnExists('total')
        ->assertTableColumnExists('billing_country');
});

test('genre resource renders columns and data', function () {
    $genre = Genre::create(['name' => 'Heavy Metal']);

    $this->actingAs($this->curator)
        ->get('/chinook/genres')
        ->assertSuccessful()
        ->assertSee('Heavy Metal');

    Livewire::test(ListGenres::class)
        ->assertCanSeeTableRecords([$genre])
        ->assertTableColumnExists('name');
});

test('artist edit page renders form', function () {
    $artist = Artist::create(['name' => 'Queen']);

    $this->actingAs($this->curator)
        ->get("/chinook/artists/{$artist->id}/edit")
        ->assertSuccessful();
});

test('album edit page renders form', function () {
    $artist = Artist::create(['name' => 'Queen']);
    $album = Album::create(['title' => 'A Night at the Opera', 'artist_id' => $artist->id]);

    $this->actingAs($this->curator)
        ->get("/chinook/albums/{$album->id}/edit")
        ->assertSuccessful();
});

test('track edit page renders form', function () {
    $artist = Artist::create(['name' => 'Queen']);
    $album = Album::create(['title' => 'A Night at the Opera', 'artist_id' => $artist->id]);
    $genre = Genre::create(['name' => 'Rock']);
    $mediaType = MediaType::create(['name' => 'MPEG audio file']);
    $track = Track::create([
        'name' => 'Bohemian Rhapsody',
        'album_id' => $album->id,
        'media_type_id' => $mediaType->id,
        'genre_id' => $genre->id,
        'milliseconds' => 354000,
        'unit_price' => 0.99,
    ]);

    $this->actingAs($this->curator)
        ->get("/chinook/tracks/{$track->id}/edit")
        ->assertSuccessful();
});

test('playlist edit page renders form', function () {
    $playlist = Playlist::create(['name' => 'Classic Rock Hits']);

    $this->actingAs($this->curator)
        ->get("/chinook/playlists/{$playlist->id}/edit")
        ->assertSuccessful();
});

test('employee edit page renders form', function () {
    $employee = Employee::create([
        'first_name' => 'Andrew',
        'last_name' => 'Adams',
        'title' => 'General Manager',
        'email' => 'andrew@chinook.test',
    ]);

    $this->actingAs($this->curator)
        ->get("/chinook/employees/{$employee->id}/edit")
        ->assertSuccessful();
});

test('customer edit page renders form', function () {
    $employee = Employee::create([
        'first_name' => 'Jane',
        'last_name' => 'Peacock',
        'email' => 'jane@chinook.test',
    ]);

    $customer = Customer::create([
        'first_name' => 'Luís',
        'last_name' => 'Gonçalves',
        'email' => 'luisg@brazil.test',
        'support_rep_id' => $employee->id,
    ]);

    $this->actingAs($this->curator)
        ->get("/chinook/customers/{$customer->id}/edit")
        ->assertSuccessful();
});

test('invoice edit page renders form', function () {
    $customer = Customer::create([
        'first_name' => 'Luís',
        'last_name' => 'Gonçalves',
        'email' => 'luisg@brazil.test',
    ]);

    $invoice = Invoice::create([
        'customer_id' => $customer->id,
        'invoice_date' => '2026-01-01 00:00:00',
        'total' => 18.81,
        'billing_country' => 'Brazil',
    ]);

    $this->actingAs($this->curator)
        ->get("/chinook/invoices/{$invoice->id}/edit")
        ->assertSuccessful();
});

test('genre edit page renders form', function () {
    $genre = Genre::create(['name' => 'Heavy Metal']);

    $this->actingAs($this->curator)
        ->get("/chinook/genres/{$genre->id}/edit")
        ->assertSuccessful();
});
