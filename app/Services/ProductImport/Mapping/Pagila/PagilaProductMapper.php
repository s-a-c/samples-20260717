<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Mapping\Pagila;

use App\Domain\Staging\Pagila\Actor as StagingActor;
use App\Domain\Staging\Pagila\Category as StagingCategory;
use App\Domain\Staging\Pagila\Customer as StagingCustomer;
use App\Domain\Staging\Pagila\Film as StagingFilm;
use App\Domain\Staging\Pagila\Staff as StagingStaff;
use App\Domain\Staging\Pagila\Store as StagingStore;
use App\Services\ProductImport\Mapping\ProductMapper;
use App\Services\ProductImport\Mapping\TableMapper;
use App\Services\ProductImport\SourceIdentityRegistry;
use App\Services\ProductImport\StagingContext;
use Illuminate\Support\Facades\DB;

class PagilaProductMapper extends ProductMapper
{
    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): array
    {
        $registry = app(SourceIdentityRegistry::class);
        $context = app(StagingContext::class);

        $mappers = [
            new CountryCityMapper($registry),
            new CategoryMapper($registry),
            new LanguageMapper($registry),
            new ActorMapper($registry),
            new FilmMapper($registry),
            new StoreStaffMapper($registry), // Circular FK: store+staff in one transaction
            new CustomerMapper($registry),
        ];

        $totalRows = 0;

        $context->run(function () use ($mappers, $sourceSchema, $stagingSchema, &$totalRows) {
            foreach ($mappers as $mapper) {
                $totalRows += $mapper->load($sourceSchema, $stagingSchema);
            }
        });

        return ['tables' => count($mappers), 'rows' => $totalRows];
    }

    #[\Override]
    protected function mappers(): array
    {
        $registry = app(SourceIdentityRegistry::class);

        return [
            new CountryCityMapper($registry),
            new CategoryMapper($registry),
            new LanguageMapper($registry),
            new ActorMapper($registry),
            new FilmMapper($registry),
            new StoreStaffMapper($registry),
            new CustomerMapper($registry),
        ];
    }
}

class CountryCityMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $count = 0;

        $countries = DB::table("{$sourceSchema}.country")->get();
        foreach ($countries as $row) {
            $uuid = $this->registry->getOrMint('pagila.countries', ['country_id' => $row->country_id]);
            DB::table("{$stagingSchema}.countries")->insert([
                'id' => $uuid,
                'country' => $row->country ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count++;
        }

        $cities = DB::table("{$sourceSchema}.city")->get();
        foreach ($cities as $row) {
            $uuid = $this->registry->getOrMint('pagila.cities', ['city_id' => $row->city_id]);
            $countryUuid = $this->registry->getOrMint('pagila.countries', ['country_id' => $row->country_id]);
            DB::table("{$stagingSchema}.cities")->insert([
                'id' => $uuid,
                'city' => $row->city ?? '',
                'country_id' => $countryUuid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count++;
        }

        return $count;
    }
}

class CategoryMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.category")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('pagila.categories', ['category_id' => $row->category_id]);
            StagingCategory::create(['id' => $uuid, 'name' => $row->name ?? '']);
            $count++;
        }

        return $count;
    }
}

class LanguageMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.language")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('pagila.languages', ['language_id' => $row->language_id]);
            DB::table("{$stagingSchema}.languages")->insert([
                'id' => $uuid,
                'name' => $row->name ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count++;
        }

        return $count;
    }
}

class ActorMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.actor")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('pagila.actors', ['actor_id' => $row->actor_id]);
            StagingActor::create([
                'id' => $uuid,
                'first_name' => $row->first_name ?? '',
                'last_name' => $row->last_name ?? '',
            ]);
            $count++;
        }

        return $count;
    }
}

class FilmMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $rows = DB::table("{$sourceSchema}.film")->get();
        $count = 0;

        foreach ($rows as $row) {
            $uuid = $this->registry->getOrMint('pagila.films', ['film_id' => $row->film_id]);
            $langUuid = $this->registry->getOrMint('pagila.languages', ['language_id' => $row->language_id]);
            StagingFilm::create([
                'id' => $uuid,
                'title' => $row->title ?? '',
                'description' => $row->description,
                'release_year' => $row->release_year !== null ? $this->sourceInt($row->release_year) : null,
                'language_id' => $langUuid,
                'rental_duration' => $this->sourceInt($row->rental_duration ?? 3),
                'rental_rate' => $this->sourceFloat($row->rental_rate ?? 4.99),
                'length' => $row->length !== null ? $this->sourceInt($row->length) : null,
                'replacement_cost' => $this->sourceFloat($row->replacement_cost ?? 19.99),
                'rating' => $row->rating,
                'special_features' => $row->special_features,
            ]);
            $count++;
        }

        return $count;
    }
}

class StoreStaffMapper extends TableMapper
{
    public function __construct(
        private SourceIdentityRegistry $registry,
    ) {}

    #[\Override]
    public function load(string $sourceSchema, string $stagingSchema): int
    {
        $count = 0;

        // Handle circular FK in one transaction (stores.manager_staff_id <-> staff.store_id)
        DB::transaction(function () use ($sourceSchema, &$count) {
            $stores = DB::table("{$sourceSchema}.store")->get();
            $staff = DB::table("{$sourceSchema}.staff")->get();

            $storeUuidMap = [];
            $staffUuidMap = [];

            // First: create stores with null manager
            foreach ($stores as $row) {
                $storeUuid = $this->registry->getOrMint('pagila.stores', [
                    'store_id' => $this->sourceInt($row->store_id),
                ]);
                $storeUuidMap[$this->sourceInt($row->store_id)] = $storeUuid;
                StagingStore::create(['id' => $storeUuid, 'manager_staff_id' => null, 'address_id' => null]);
                $count++;
            }

            // Second: create staff with store FK
            foreach ($staff as $row) {
                $staffUuid = $this->registry->getOrMint('pagila.staff', ['staff_id' => $row->staff_id]);
                $staffUuidMap[$this->sourceInt($row->staff_id)] = $staffUuid;
                $storeUuid = $row->store_id !== null && isset($storeUuidMap[$this->sourceInt($row->store_id)])
                    ? $storeUuidMap[$this->sourceInt($row->store_id)]
                    : null;
                StagingStaff::create([
                    'id' => $staffUuid,
                    'first_name' => $row->first_name ?? '',
                    'last_name' => $row->last_name ?? '',
                    'email' => $row->email,
                    'store_id' => $storeUuid,
                    'active' => (bool) ($row->active ?? true),
                    'username' => $row->username,
                    'password' => $row->password,
                    'address_id' => null,
                ]);
                $count++;
            }

            // Third: update store manager FK
            foreach ($stores as $row) {
                if ($row->manager_staff_id !== null && isset($staffUuidMap[$this->sourceInt($row->manager_staff_id)])) {
                    StagingStore::where('id', $storeUuidMap[$this->sourceInt($row->store_id)])
                        ->update(['manager_staff_id' => $staffUuidMap[$this->sourceInt($row->manager_staff_id)]]);
                }
            }
        });

        return $count;
    }
}

class CustomerMapper extends TableMapper
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
            $uuid = $this->registry->getOrMint('pagila.customers', ['customer_id' => $row->customer_id]);
            $storeUuid = $this->registry->getOrMint('pagila.stores', ['store_id' => $this->sourceInt($row->store_id)]);
            StagingCustomer::create([
                'id' => $uuid,
                'store_id' => $storeUuid,
                'first_name' => $row->first_name ?? '',
                'last_name' => $row->last_name ?? '',
                'email' => $row->email,
                'active' => (bool) ($row->active ?? true),
                'address_id' => null,
            ]);
            $count++;
        }

        return $count;
    }
}
