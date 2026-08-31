<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping\Chinook;

use App\Domain\Staging\Chinook\Album as StagingAlbum;
use App\Domain\Staging\Chinook\Artist as StagingArtist;
use App\Domain\Staging\Chinook\Customer as StagingCustomer;
use App\Domain\Staging\Chinook\Employee as StagingEmployee;
use App\Domain\Staging\Chinook\Genre as StagingGenre;
use App\Domain\Staging\Chinook\Invoice as StagingInvoice;
use App\Domain\Staging\Chinook\MediaType as StagingMediaType;
use App\Domain\Staging\Chinook\Playlist as StagingPlaylist;
use App\Domain\Staging\Chinook\Track as StagingTrack;
use App\Services\ProductImport\Mapping\ProductMapper;
use App\Services\ProductImport\Mapping\SelfReferentialMapper;
use App\Services\ProductImport\Mapping\TableMapper;
use App\Services\ProductImport\SourceIdentityRegistry;
use App\Services\ProductImport\StagingContext;
use Illuminate\Support\Facades\DB;

/**
 * Chinook product mapper — orchestrates all Chinook table mappers in FK dependency order.
 */
class ChinookProductMapper extends ProductMapper
{
    /**
     * @param  string  $sourceSchema  The upstream-shaped source schema
     * @param  string  $stagingSchema  The app-shaped staging schema
     * @return array{tables: int, rows: int}
     */
    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): array
    {
        $registry = app(SourceIdentityRegistry::class);
        $context = app(StagingContext::class);

        $mappers = [
            new ArtistMapper($registry),
            new AlbumMapper($registry),
            new GenreMapper($registry),
            new MediaTypeMapper($registry),
            new EmployeeMapper($registry),
            new CustomerMapper($registry),
            new TrackMapper($registry),
            new PlaylistMapper($registry),
            new InvoiceMapper($registry),
        ];

        $totalRows = 0;

        $context->run(function () use ($mappers, $sourceSchema, $stagingSchema, &$totalRows) {
            foreach ($mappers as $mapper) {
                $totalRows += $mapper->load($sourceSchema, $stagingSchema);
            }
        });

        return ['tables' => count($mappers), 'rows' => $totalRows];
    }

    /**
     * @return array<int, TableMapper>
     */
    #[\Override]
    protected function mappers(): array
    {
        $registry = app(SourceIdentityRegistry::class);

        return [
            new ArtistMapper($registry),
            new AlbumMapper($registry),
            new GenreMapper($registry),
            new MediaTypeMapper($registry),
            new EmployeeMapper($registry),
            new CustomerMapper($registry),
            new TrackMapper($registry),
            new PlaylistMapper($registry),
            new InvoiceMapper($registry),
        ];
    }
}

// Individual table mappers

final class ArtistMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.artist")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('chinook.artists', ['ArtistId' => $row->artist_id]);
            StagingArtist::create(['id' => $uuid, 'name' => $row->name ?? '']);
            $count++;
        }

        return $count;
    }
}

final class AlbumMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.album")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('chinook.albums', ['AlbumId' => $row->album_id]);
            $artistUuid = $this->registry->getOrMint('chinook.artists', ['ArtistId' => $row->artist_id]);
            StagingAlbum::create(['id' => $uuid, 'title' => $row->title ?? '', 'artist_id' => $artistUuid]);
            $count++;
        }

        return $count;
    }
}

final class GenreMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.genre")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('chinook.genres', ['GenreId' => $row->genre_id]);
            StagingGenre::create(['id' => $uuid, 'name' => $row->name]);
            $count++;
        }

        return $count;
    }
}

final class MediaTypeMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.media_type")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('chinook.media_types', ['MediaTypeId' => $row->media_type_id]);
            StagingMediaType::create(['id' => $uuid, 'name' => $row->name]);
            $count++;
        }

        return $count;
    }
}

final class EmployeeMapper extends SelfReferentialMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.employee")->orderBy('employee_id')->get();
        $count = 0;
        $sourceToUuid = [];

        // Pass 1: Insert all employees with reports_to = null
        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('chinook.employees', ['EmployeeId' => $row->employee_id]);
            $sourceToUuid[$this->sourceInt($row->employee_id)] = $uuid;

            StagingEmployee::create([
                'id' => $uuid,
                'last_name' => $row->last_name ?? '',
                'first_name' => $row->first_name ?? '',
                'title' => $row->title,
                'reports_to' => null,
                'birth_date' => $row->birth_date,
                'hire_date' => $row->hire_date,
                'address' => $row->address,
                'city' => $row->city,
                'state' => $row->state,
                'country' => $row->country,
                'postal_code' => $row->postal_code,
                'phone' => $row->phone,
                'fax' => $row->fax,
                'email' => $row->email,
            ]);
            $count++;
        }

        // Pass 2: Update self-referential FK
        foreach ($rows as $row) {
            if ($row->reports_to !== null && isset($sourceToUuid[$this->sourceInt($row->reports_to)])) {
                $employeeUuid = $sourceToUuid[$this->sourceInt($row->employee_id)];
                $reportsToUuid = $sourceToUuid[$this->sourceInt($row->reports_to)];
                StagingEmployee::where('id', $employeeUuid)->update(['reports_to' => $reportsToUuid]);
            }
        }

        return $count;
    }
}

final class CustomerMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.customer")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('chinook.customers', ['CustomerId' => $row->customer_id]);
            $supportRepUuid = null;
            if ($row->support_rep_id !== null) {
                $supportRepUuid = $this->registry->getOrMint('chinook.employees', [
                    'EmployeeId' => $row->support_rep_id,
                ]);
            }
            StagingCustomer::create([
                'id' => $uuid,
                'first_name' => $row->first_name ?? '',
                'last_name' => $row->last_name ?? '',
                'company' => $row->company,
                'address' => $row->address,
                'city' => $row->city,
                'state' => $row->state,
                'country' => $row->country,
                'postal_code' => $row->postal_code,
                'phone' => $row->phone,
                'fax' => $row->fax,
                'email' => $row->email ?? '',
                'support_rep_id' => $supportRepUuid,
            ]);
            $count++;
        }

        return $count;
    }
}

final class TrackMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.track")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('chinook.tracks', ['TrackId' => $row->track_id]);
            $albumUuid = $row->album_id !== null
                ? $this->registry->getOrMint('chinook.albums', ['AlbumId' => $row->album_id])
                : null;
            $mediaTypeUuid = $this->registry->getOrMint('chinook.media_types', ['MediaTypeId' => $row->media_type_id]);
            $genreUuid = $row->genre_id !== null
                ? $this->registry->getOrMint('chinook.genres', ['GenreId' => $row->genre_id])
                : null;

            StagingTrack::create([
                'id' => $uuid,
                'name' => $row->name ?? '',
                'album_id' => $albumUuid,
                'media_type_id' => $mediaTypeUuid,
                'genre_id' => $genreUuid,
                'composer' => $row->composer,
                'milliseconds' => $this->sourceInt($row->milliseconds),
                'bytes' => $row->bytes !== null ? $this->sourceInt($row->bytes) : null,
                'unit_price' => $this->sourceFloat($row->unit_price),
            ]);
            $count++;
        }

        return $count;
    }
}

final class PlaylistMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.playlist")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('chinook.playlists', ['PlaylistId' => $row->playlist_id]);
            StagingPlaylist::create(['id' => $uuid, 'name' => $row->name]);
            $count++;
        }

        // Also load playlist_track junction
        $junctionRows = DB::table("{$sourceSchema}.playlist_track")->get();
        foreach ($junctionRows as $row) {
            DB::table("{$stagingSchema}.playlist_track")->insert([
                'playlist_id' => $this->registry->getOrMint('chinook.playlists', ['PlaylistId' => $row->playlist_id]),
                'track_id' => $this->registry->getOrMint('chinook.tracks', ['TrackId' => $row->track_id]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $count;
    }
}

final class InvoiceMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.invoice")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('chinook.invoices', ['InvoiceId' => $row->invoice_id]);
            $customerUuid = $this->registry->getOrMint('chinook.customers', ['CustomerId' => $row->customer_id]);

            StagingInvoice::create([
                'id' => $uuid,
                'customer_id' => $customerUuid,
                'invoice_date' => $row->invoice_date,
                'billing_address' => $row->billing_address,
                'billing_city' => $row->billing_city,
                'billing_state' => $row->billing_state,
                'billing_country' => $row->billing_country,
                'billing_postal_code' => $row->billing_postal_code,
                'total' => $this->sourceFloat($row->total),
            ]);

            // Also load invoice items
            $items = DB::table("{$sourceSchema}.invoice_line")->where('invoice_id', $row->invoice_id)->get();
            foreach ($items as $item) {
                $itemUuid = $this->registry->getOrMint('chinook.invoice_lines', [
                    'InvoiceLineId' => $item->invoice_line_id,
                ]);
                $trackUuid = $this->registry->getOrMint('chinook.tracks', ['TrackId' => $item->track_id]);
                DB::table("{$stagingSchema}.invoice_lines")->insert([
                    'id' => $itemUuid,
                    'invoice_id' => $uuid,
                    'track_id' => $trackUuid,
                    'unit_price' => $this->sourceFloat($item->unit_price),
                    'quantity' => $this->sourceInt($item->quantity),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $count++;
        }

        return $count;
    }
}
