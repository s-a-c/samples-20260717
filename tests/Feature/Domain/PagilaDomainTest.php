<?php

declare(strict_types=1);

use App\Models\Pagila\Actor;
use App\Models\Pagila\Category;
use App\Models\Pagila\City;
use App\Models\Pagila\Country;
use App\Models\Pagila\Customer;
use App\Models\Pagila\Film;
use App\Models\Pagila\FilmActor;
use App\Models\Pagila\FilmCategory;
use App\Models\Pagila\FilmText;
use App\Models\Pagila\Inventory;
use App\Models\Pagila\Language;
use App\Models\Pagila\Payment;
use App\Models\Pagila\Rental;
use App\Models\Pagila\Staff;
use App\Models\Pagila\Store;

covers(Film::class);

test('pagila film, actor, category, language and film_text models can be persisted and queried', function () {
    $language = Language::create(['name' => 'English']);
    $origLanguage = Language::create(['name' => 'Italian']);

    $film = Film::create([
        'title' => 'ACADEMY DINOSAUR',
        'description' => 'A Epic Drama of a Feminist And a Mad Scientist who must Battle a Teacher in The Canadian Rockies',
        'release_year' => 2006,
        'language_id' => $language->id,
        'original_language_id' => $origLanguage->id,
        'rental_duration' => 6,
        'rental_rate' => 0.99,
        'length' => 86,
        'replacement_cost' => 20.99,
        'rating' => 'PG',
    ]);

    $actor = Actor::create([
        'first_name' => 'PENELOPE',
        'last_name' => 'GUINESS',
    ]);

    $category = Category::create(['name' => 'Documentary']);

    $film->actors()->attach($actor->id);
    $film->categories()->attach($category->id);

    $filmText = FilmText::create([
        'film_id' => $film->id,
        'title' => 'ACADEMY DINOSAUR',
        'description' => 'A Epic Drama...',
    ]);

    expect($film->id)->not->toBeNull();
    expect($film->getProductDomainName())->toBe('pagila');
    expect($film->language->name)->toBe('English');
    expect($film->originalLanguage->name)->toBe('Italian');
    expect($film->actors->first()->last_name)->toBe('GUINESS');
    expect($film->categories->first()->name)->toBe('Documentary');
    expect($actor->films->first()->title)->toBe('ACADEMY DINOSAUR');
    expect($category->films->first()->title)->toBe('ACADEMY DINOSAUR');
    expect($filmText->film->id)->toBe($film->id);

    $pivotActor = FilmActor::where('film_id', $film->id)->where('actor_id', $actor->id)->first();
    expect($pivotActor)->not->toBeNull();
    expect($pivotActor->film->id)->toBe($film->id);
    expect($pivotActor->actor->id)->toBe($actor->id);

    $pivotCategory = FilmCategory::where('film_id', $film->id)->where('category_id', $category->id)->first();
    expect($pivotCategory)->not->toBeNull();
    expect($pivotCategory->film->id)->toBe($film->id);
    expect($pivotCategory->category->id)->toBe($category->id);
});

test('pagila country, city, store, staff, and customer relationships work', function () {
    $country = Country::create(['country' => 'United States']);
    $city = City::create([
        'city' => 'Lethbridge',
        'country_id' => $country->id,
    ]);

    $store = Store::create([
        'address' => '47 MyGate Drive',
    ]);

    $staff = Staff::create([
        'first_name' => 'Mike',
        'last_name' => 'Hillyer',
        'email' => 'Mike.Hillyer@pagilastaff.com',
        'store_id' => $store->id,
        'username' => 'Mike',
        'active' => true,
    ]);

    $store->update(['manager_staff_id' => $staff->id]);

    $customer = Customer::create([
        'store_id' => $store->id,
        'first_name' => 'MARY',
        'last_name' => 'SMITH',
        'email' => 'MARY.SMITH@pagilacustomer.org',
        'active' => true,
    ]);

    expect($city->country->country)->toBe('United States');
    expect($country->cities->first()->city)->toBe('Lethbridge');
    expect($store->manager->first_name)->toBe('Mike');
    expect($staff->store->address)->toBe('47 MyGate Drive');
    expect($staff->managedStore->id)->toBe($store->id);
    expect($customer->store->id)->toBe($store->id);
    expect($store->customers->first()->last_name)->toBe('SMITH');
    expect($store->staff->first()->first_name)->toBe('Mike');
});

test('pagila inventory, rental, and payment relationships work', function () {
    $language = Language::create(['name' => 'English']);
    $film = Film::create([
        'title' => 'BLANKET BEVERLY',
        'language_id' => $language->id,
    ]);

    $store = Store::create(['address' => '123 Main St']);
    $staff = Staff::create([
        'first_name' => 'Jon',
        'last_name' => 'Stephens',
        'store_id' => $store->id,
        'username' => 'Jon',
    ]);

    $customer = Customer::create([
        'store_id' => $store->id,
        'first_name' => 'PATRICIA',
        'last_name' => 'JOHNSON',
    ]);

    $inventory = Inventory::create([
        'film_id' => $film->id,
        'store_id' => $store->id,
    ]);

    $rental = Rental::create([
        'rental_date' => now(),
        'inventory_id' => $inventory->id,
        'customer_id' => $customer->id,
        'staff_id' => $staff->id,
    ]);

    $payment = Payment::create([
        'customer_id' => $customer->id,
        'staff_id' => $staff->id,
        'rental_id' => $rental->id,
        'amount' => 2.99,
        'payment_date' => now(),
    ]);

    expect($inventory->film->title)->toBe('BLANKET BEVERLY');
    expect($inventory->store->id)->toBe($store->id);
    expect($rental->inventory->film->title)->toBe('BLANKET BEVERLY');
    expect($rental->customer->first_name)->toBe('PATRICIA');
    expect($rental->staff->first_name)->toBe('Jon');
    expect($payment->customer->last_name)->toBe('JOHNSON');
    expect($payment->staff->last_name)->toBe('Stephens');
    expect($payment->rental->id)->toBe($rental->id);
    expect($customer->rentals->first()->id)->toBe($rental->id);
    expect($customer->payments->first()->amount)->toBe('2.99');
});
