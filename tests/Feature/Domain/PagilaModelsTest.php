<?php

declare(strict_types=1);

use App\Enums\SamplesProduct;
use App\Models\Pagila\Actor;
use App\Models\Pagila\Address;
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

covers(
    Address::class,
    Actor::class,
    Category::class,
    City::class,
    Country::class,
    Customer::class,
    Film::class,
    FilmActor::class,
    FilmCategory::class,
    FilmText::class,
    Inventory::class,
    Language::class,
    Payment::class,
    Rental::class,
    Staff::class,
    Store::class,
);

test('pagila models report their product domain', function () {
    expect((new Actor)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new Category)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new City)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new Country)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new Customer)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new FilmActor)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new FilmCategory)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new FilmText)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new Inventory)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new Language)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new Payment)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new Rental)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new Staff)->getProductDomain())->toBe(SamplesProduct::Pagila)
        ->and((new Store)->getProductDomain())->toBe(SamplesProduct::Pagila);
});

test('pagila country and city relations resolve', function () {
    $country = Country::create(['country' => 'United States']);
    $city = City::create(['city' => 'Lethbridge', 'country_id' => $country->id]);

    expect($country->cities->first()->city)->toBe('Lethbridge')
        ->and($city->country->country)->toBe('United States');
});

test('pagila film has many relations resolve', function () {
    $language = Language::create(['name' => 'English']);
    $film = Film::create(['title' => 'EAGLE PEAK', 'language_id' => $language->id]);
    $actor = Actor::create(['first_name' => 'KARL', 'last_name' => 'BERRY']);
    $category = Category::create(['name' => 'Comedy']);

    $film->actors()->attach($actor->id);
    $film->categories()->attach($category->id);

    FilmText::create([
        'film_id' => $film->id,
        'title' => 'EAGLE PEAK',
        'description' => 'A high-altitude thriller.',
    ]);

    $country = Country::create(['country' => 'Test Country']);
    $city = City::create(['city' => 'Test City', 'country_id' => $country->id]);
    $address = Address::create(['address' => '99 Film Lane', 'city_id' => $city->id]);
    $store = Store::create(['address_id' => $address->id]);
    Inventory::create([
        'film_id' => $film->id,
        'store_id' => $store->id,
    ]);

    expect($film->filmActors->first()->actor_id)->toBe($actor->id)
        ->and($film->filmCategories->first()->category_id)->toBe($category->id)
        ->and($film->filmTexts->first()->title)->toBe('EAGLE PEAK')
        ->and($film->inventories->first()->store_id)->toBe($store->id);
});

test('pagila language and film relations resolve', function () {
    $language = Language::create(['name' => 'English']);
    Film::create([
        'title' => 'ACADEMY DINOSAUR',
        'language_id' => $language->id,
        'rental_duration' => 6,
        'rental_rate' => 0.99,
        'replacement_cost' => 20.99,
    ]);

    expect($language->films->first()->title)->toBe('ACADEMY DINOSAUR');
});

test('pagila actor, category and their film pivot relations resolve', function () {
    $language = Language::create(['name' => 'English']);
    $film = Film::create(['title' => 'BRIGHT ELEPHANT', 'language_id' => $language->id]);
    $actor = Actor::create(['first_name' => 'PENELOPE', 'last_name' => 'GUINESS']);
    $category = Category::create(['name' => 'Documentary']);

    $film->actors()->attach($actor->id);
    $film->categories()->attach($category->id);

    expect($actor->films->first()->title)->toBe('BRIGHT ELEPHANT')
        ->and($actor->filmActors->first()->film_id)->toBe($film->id)
        ->and($category->films->first()->title)->toBe('BRIGHT ELEPHANT')
        ->and($category->filmCategories->first()->film_id)->toBe($film->id);
});

test('pagila film actor and film category pivot relations resolve', function () {
    $language = Language::create(['name' => 'English']);
    $film = Film::create(['title' => 'CHOCOLATE DUKE', 'language_id' => $language->id]);
    $actor = Actor::create(['first_name' => 'BETTE', 'last_name' => 'NICHOLSON']);
    $category = Category::create(['name' => 'Action']);

    $film->actors()->attach($actor->id);
    $film->categories()->attach($category->id);

    $filmActor = FilmActor::where('film_id', $film->id)->where('actor_id', $actor->id)->first();
    $filmCategory = FilmCategory::where('film_id', $film->id)->where('category_id', $category->id)->first();

    expect($filmActor)->not->toBeNull();
    expect($filmActor->film->title)->toBe('CHOCOLATE DUKE');
    expect($filmActor->actor->last_name)->toBe('NICHOLSON');
    expect($filmCategory)->not->toBeNull();
    expect($filmCategory->film->title)->toBe('CHOCOLATE DUKE');
    expect($filmCategory->category->name)->toBe('Action');
});

test('pagila film text relation resolves', function () {
    $language = Language::create(['name' => 'English']);
    $film = Film::create(['title' => 'GOLDWYN GHOST', 'language_id' => $language->id]);

    $filmText = FilmText::create([
        'film_id' => $film->id,
        'title' => 'GOLDWYN GHOST',
        'description' => 'A spooky tale.',
    ]);

    expect($filmText->film->title)->toBe('GOLDWYN GHOST');
});

test('pagila store, staff and customer relations resolve', function () {
    $country = Country::create(['country' => 'Test Country']);
    $city = City::create(['city' => 'Test City', 'country_id' => $country->id]);
    $address = Address::create(['address' => '47 MyGate Drive', 'city_id' => $city->id]);
    $store = Store::create(['address_id' => $address->id]);
    $staff = Staff::create([
        'first_name' => 'Mike',
        'last_name' => 'Hillyer',
        'email' => 'mike@pagila.test',
        'store_id' => $store->id,
        'username' => 'Mike',
        'active' => true,
        'address_id' => $address->id,
    ]);
    $store->update(['manager_staff_id' => $staff->id]);

    $customer = Customer::create([
        'store_id' => $store->id,
        'first_name' => 'MARY',
        'last_name' => 'SMITH',
        'email' => 'mary@pagila.test',
        'active' => true,
        'address_id' => $address->id,
    ]);

    Inventory::create([
        'film_id' => Film::create(['title' => 'TEST FILM', 'language_id' => Language::create(['name' => 'English'])->id])->id,
        'store_id' => $store->id,
    ]);

    expect($address->city->city)->toBe('Test City')
        ->and($address->staff->first()->last_name)->toBe('Hillyer')
        ->and($address->customers->first()->last_name)->toBe('SMITH')
        ->and($address->stores->first()->id)->toBe($store->id)
        ->and($store->manager->first_name)->toBe('Mike')
        ->and($staff->address->address)->toBe('47 MyGate Drive')
        ->and($customer->address->address)->toBe('47 MyGate Drive')
        ->and($store->staff->first()->last_name)->toBe('Hillyer')
        ->and($store->customers->first()->last_name)->toBe('SMITH')
        ->and($store->inventories->first()->film->title)->toBe('TEST FILM');
    expect($staff->store->address->address)->toBe('47 MyGate Drive')
        ->and($staff->managedStore->id)->toBe($store->id)
        ->and($staff->rentals)->toBeEmpty()
        ->and($staff->payments)->toBeEmpty();
    expect($store->customers->first()->store->id)->toBe($store->id);
});

test('pagila inventory, rental and payment relations resolve', function () {
    $language = Language::create(['name' => 'English']);
    $film = Film::create(['title' => 'BLANKET BEVERLY', 'language_id' => $language->id]);
    $country = Country::create(['country' => 'Test Country']);
    $city = City::create(['city' => 'Test City', 'country_id' => $country->id]);
    $address = Address::create(['address' => '123 Main St', 'city_id' => $city->id]);
    $store = Store::create(['address_id' => $address->id]);
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

    expect($inventory->film->title)->toBe('BLANKET BEVERLY')
        ->and($inventory->store->id)->toBe($store->id)
        ->and($inventory->rentals->first()->id)->toBe($rental->id)
        ->and($rental->inventory->film->title)->toBe('BLANKET BEVERLY')
        ->and($rental->customer->first_name)->toBe('PATRICIA')
        ->and($rental->staff->last_name)->toBe('Stephens')
        ->and($rental->payments->first()->id)->toBe($payment->id);
    expect($payment->customer->last_name)->toBe('JOHNSON')
        ->and($payment->staff->last_name)->toBe('Stephens')
        ->and($payment->rental->id)->toBe($rental->id);
    expect($customer->rentals->first()->id)->toBe($rental->id)
        ->and($customer->payments->first()->amount)->toBe('2.99');
    expect($staff->rentals->first()->id)->toBe($rental->id)
        ->and($staff->payments->first()->id)->toBe($payment->id);
});
