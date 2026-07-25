<?php

namespace Tests\Feature\Filament;

use App\Domain\Chinook\Models\Album;
use App\Domain\Chinook\Models\Artist;
use App\Domain\Chinook\Models\Customer;
use App\Domain\Chinook\Models\Employee;
use App\Domain\Chinook\Models\Genre;
use App\Domain\Chinook\Models\Invoice;
use App\Domain\Chinook\Models\MediaType;
use App\Domain\Chinook\Models\Playlist;
use App\Domain\Chinook\Models\Track;
use App\Filament\Chinook\Resources\AlbumResource\Pages\ListAlbums;
use App\Filament\Chinook\Resources\ArtistResource\Pages\ListArtists;
use App\Filament\Chinook\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Chinook\Resources\EmployeeResource\Pages\ListEmployees;
use App\Filament\Chinook\Resources\GenreResource\Pages\ListGenres;
use App\Filament\Chinook\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Chinook\Resources\PlaylistResource\Pages\ListPlaylists;
use App\Filament\Chinook\Resources\TrackResource\Pages\ListTracks;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChinookResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $curator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->curator = User::factory()->create();
        $this->curator->assignRole(Role::findOrCreate('chinook_curator', 'web'));
    }

    public function test_chinook_curator_can_access_all_chinook_resource_list_pages(): void
    {
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
    }

    public function test_artist_resource_renders_columns_and_data(): void
    {
        $artist = Artist::create(['name' => 'Queen']);

        $this->actingAs($this->curator)
            ->get('/chinook/artists')
            ->assertSuccessful()
            ->assertSee('Queen');

        Livewire::test(ListArtists::class)
            ->assertCanSeeTableRecords([$artist])
            ->assertTableColumnExists('name');
    }

    public function test_album_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_track_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_playlist_resource_renders_columns_and_data(): void
    {
        $playlist = Playlist::create(['name' => 'Classic Rock Hits']);

        $this->actingAs($this->curator)
            ->get('/chinook/playlists')
            ->assertSuccessful()
            ->assertSee('Classic Rock Hits');

        Livewire::test(ListPlaylists::class)
            ->assertCanSeeTableRecords([$playlist])
            ->assertTableColumnExists('name');
    }

    public function test_employee_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_customer_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_invoice_resource_renders_columns_and_data(): void
    {
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
    }

    public function test_genre_resource_renders_columns_and_data(): void
    {
        $genre = Genre::create(['name' => 'Heavy Metal']);

        $this->actingAs($this->curator)
            ->get('/chinook/genres')
            ->assertSuccessful()
            ->assertSee('Heavy Metal');

        Livewire::test(ListGenres::class)
            ->assertCanSeeTableRecords([$genre])
            ->assertTableColumnExists('name');
    }
}
