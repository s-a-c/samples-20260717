---
title: "Sample Databases"
description: "Research comparison of candidate sample databases and the selected Chinook, Northwind, and Pagila sources."
type: guide
tags: \[guide, documentation, sample, databases]
updated: 2026-07-30
---

# Sample Databases

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Prompt](#1-prompt)
- [2. Response](#2-response)
    - [2.1. General-purpose relational databases](#21-general-purpose-relational-databases)
    - [2.2. E-commerce and sales](#22-e-commerce-and-sales)
    - [2.3. Finance and business](#23-finance-and-business)
    - [2.4. Healthcare](#24-healthcare)
    - [2.5. Transport and public data](#25-transport-and-public-data)
    - [2.6. Social and media](#26-social-and-media)
    - [2.7. Smaller teaching datasets](#27-smaller-teaching-datasets)
    - [2.8. Good sources and repositories](#28-good-sources-and-repositories)
    - [2.9. My shortlist](#29-my-shortlist)
- [3. Prompt](#3-prompt)
- [4. Response](#4-response)
    - [4.1. Direct PostgreSQL-focused repositories](#41-direct-postgresql-focused-repositories)
    - [4.2. Quick import examples](#42-quick-import-examples)
        - [4.2.1. Northwind](#421-northwind)
        - [4.2.2. Chinook](#422-chinook)
        - [4.2.3. Pagila](#423-pagila)
    - [4.3. Recommended teaching set](#43-recommended-teaching-set)

</details>

---

## 1. Prompt

> [!NOTE] i'm looking for more sample databases, alternatives to chinook, northwind, pagila

## 2. Response

Here are good alternatives, grouped by what they’re useful for. Because apparently one tiny fictional shop can’t satisfy every SQL exercise.

### 2.1. General-purpose relational databases

- **AdventureWorks / AdventureWorksLT**
  Microsoft’s sales, products, customers, purchasing, and production data.
  Good for joins, reporting, inventory, and SQL Server practice.
  `AdventureWorksLT` is the smaller, friendlier version.

- **Contoso Retail**
  Retail sales, products, stores, customers, and geography.
  Useful for analytics, star schemas, and Power BI.

- **WideWorldImporters**
  A more modern SQL Server sample with sales, purchasing, stock, invoices, and workflows.
  Includes OLTP and data warehouse variants.

- **Pagila**
  PostgreSQL port of Pagila.
  Useful if you want PostgreSQL-specific features rather than MySQL syntax.

- **DVD Rental**
  Another PostgreSQL sample database, commonly used in PostgreSQL tutorials.
  Similar domain to Pagila, but useful for practising PostgreSQL queries and views.

### 2.2. E-commerce and sales

- **Magento / Adobe Commerce sample data**
  Products, customers, orders, carts, addresses, and catalogue structures.
  More realistic—and considerably messier—than textbook databases.

- **Olist Brazilian E-Commerce Dataset**
  Real-world-ish order data from Brazilian marketplaces.
  Available through Kaggle and other public data repositories.
  Good for multi-table joins, delivery analysis, reviews, payments, and geospatial work.

- **Instacart Market Basket Analysis**
  Orders, products, aisles, departments, and reorder behaviour.
  Excellent for customer behaviour and transactional analysis.

- **Superstore**
  Sales, customers, products, regions, discounts, and profits.
  Commonly distributed as CSV or Tableau data. Good for BI and aggregation exercises.

- **AdventureWorks Sales**
  Worth mentioning separately because it’s one of the better structured datasets for realistic sales reporting.

### 2.3. Finance and business

- **Financial Sample**
  Sales, profit, products, segments, countries, and dates.
  Small and convenient for Power BI, SQL, and dashboard demonstrations.

- **IBM Cognos Sample Outdoors**
  Retail and sales data designed for reporting and analytics.
  Useful for dimensional modelling and BI demonstrations.

- **TPC-H**
  Benchmark database for decision-support queries.
  More analytical and less “application CRUD” oriented. Available in various sizes.

- **TPC-DS**
  Larger, more complex retail decision-support benchmark.
  Excellent for query optimisation, warehouses, and performance testing.

### 2.4. Healthcare

- **MIMIC-IV**
  De-identified hospital and intensive-care data.
  Very rich, but requires registration and an appropriate data-use agreement.

- **Synthea**
  Synthetic patient records including encounters, conditions, medications, procedures, and observations.
  Great for healthcare application prototypes without handling real patient data.

- **CMS Medicare datasets**
  Public healthcare provider, procedure, cost, and utilisation data.
  Usually CSV-based rather than a ready-made relational database, but easy to import.

### 2.5. Transport and public data

- **NYC Taxi Trips**
  Trips, timestamps, locations, fares, payments, and passenger counts.
  Great for date/time analysis, aggregation, and performance testing.

- **UK Government open datasets**
  Transport, crime, population, business, and local authority data.
  Useful if you want something geographically relevant rather than another American sales demo.

- **OpenStreetMap extracts**
  Roads, buildings, places, transport, and geographic relationships.
  Better suited to spatial databases such as PostGIS.

- **Airline / flight datasets**
  Flight schedules, delays, airports, carriers, and routes.
  Common examples include the US Bureau of Transportation Statistics datasets.

### 2.6. Social and media

- **Stack Exchange Data Dump**
  Questions, answers, users, votes, tags, comments, and badges.
  Excellent for relational modelling, ranking, and aggregation.

- **Reddit datasets**
  Posts, comments, subreddits, users, and timestamps.
  Useful for text analysis and large-volume data processing.

- **IMDb datasets**
  Titles, ratings, names, principals, and crew.
  Good for film-related joins and graph-like relationships.

- **MusicBrainz**
  Artists, recordings, releases, labels, areas, and relationships.
  A richer alternative if Chinook’s music catalogue feels a bit too tidy.

### 2.7. Smaller teaching datasets

- **Employees Database**
  Employees, departments, salaries, titles, and department managers.
  Very popular for MySQL practice and stored procedures.

- **World Database**
  Countries, cities, languages, regions, and populations.
  Small, simple, and useful for beginner joins.

- **Classic Models**
  Customers, employees, offices, orders, products, and payments for a model-car business.
  A solid alternative to Northwind for sales-focused exercises.

- **SalesDB / SQLZoo datasets**
  Small purpose-built databases for query exercises.
  Good when you want to focus on SQL rather than spend half the session understanding the domain.

- **University / Student Management databases**
  Students, courses, lecturers, enrolments, grades, and departments.
  Easy to find in PostgreSQL, MySQL, and SQLite formats, though quality varies wildly.

### 2.8. Good sources and repositories

- **Kaggle Datasets** – broad selection, often CSV-based
- **GitHub** – search for SQL dumps such as `sample database`, `postgres sample db`, or `mysql sample database`
- **Microsoft SQL Server samples** – AdventureWorks and WideWorldImporters
- **PostgreSQL sample databases** – Pagila and DVD Rental
- **Data.gov.uk / data.gov** – public-sector datasets
- **Google BigQuery public datasets** – large analytical datasets
- **Snowflake Marketplace / sample data** – useful for warehouse demonstrations

### 2.9. My shortlist

If you want a balanced collection:

1. **AdventureWorksLT** – general SQL and relational modelling
2. **Classic Models** – straightforward sales queries
3. **Employees Database** – procedures, views, and subqueries
4. **Pagila** – PostgreSQL practice
5. **Olist** – realistic e-commerce analysis
6. **Synthea** – healthcare without privacy headaches
7. **Stack Exchange** – complex relationships and analytics
8. **TPC-H** – performance and analytical SQL
9. **NYC Taxi** – time-series and aggregation
10. **MusicBrainz** – rich, messy, interconnected data

## 3. Prompt

> [!NOTE] add northwind and chinook to your list
> include url's to Postgresql formats

## 4. Response

For application support and consultancy work, I’d particularly recommend **AdventureWorksLT**, **WideWorldImporters**, **Olist**, **Synthea**, and **Stack Exchange**. They cover the useful spectrum from “pleasant teaching example” to “this schema was clearly designed by committee.”

Here’s the revised shortlist, including **Northwind** and **Chinook**, with PostgreSQL-friendly sources.

| Database                     | Best for                                                    | PostgreSQL source                                                                                                           |
| ---------------------------- | ----------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| **Northwind**                | Classic sales, customers, orders, products, suppliers       | [pthom/northwind_psql](https://github.com/pthom/northwind_psql)                                                             |
| **Chinook**                  | Music catalogue, artists, albums, tracks, invoices          | [lerocha/chinook-database](https://github.com/lerocha/chinook-database)                                                     |
| **Pagila**                   | PostgreSQL features, rental management, joins, views        | [devrimgunduz/pagila](https://github.com/devrimgunduz/pagila)                                                               |
| **DVD Rental**               | PostgreSQL tutorials, reporting, functions, date queries    | [PostgreSQL DVD Rental sample](https://www.postgresqltutorial.com/postgresql-getting-started/postgresql-sample-database/)   |
| **AdventureWorks**           | Sales, purchasing, products, inventory, employees           | [stephbu/AdventureWorks-for-Postgres](https://github.com/stephbu/AdventureWorks-for-Postgres)                               |
| **AdventureWorksLT**         | Smaller version of AdventureWorks for learning              | [AdventureWorks-for-Postgres](https://github.com/stephbu/AdventureWorks-for-Postgres)                                       |
| **WideWorldImporters**       | Modern business workflows, stock, purchasing, invoices      | [Microsoft SQL samples](https://github.com/microsoft/sql-server-samples/tree/master/samples/databases/wide-world-importers) |
| **Classic Models**           | Customers, orders, products, employees, offices, payments   | [MySQL Classic Models](https://www.mysqltutorial.org/mysql-sample-database.aspx)                                            |
| **Employees Database**       | Employees, departments, salaries, titles, stored procedures | [datacharmer/test_db](https://github.com/datacharmer/test_db)                                                               |
| **Pagila-compatible Pagila** | PostgreSQL alternative to MySQL Pagila                      | [Pagila GitHub repository](https://github.com/devrimgunduz/pagila)                                                          |
| **Olist E-Commerce**         | Realistic e-commerce orders, payments, reviews, delivery    | [Kaggle: Brazilian E-Commerce](https://www.kaggle.com/datasets/olistbr/brazilian-ecommerce)                                 |
| **Synthea**                  | Synthetic healthcare records and clinical workflows         | [Synthea GitHub](https://github.com/synthetichealth/synthea)                                                                |
| **Stack Exchange**           | Users, questions, answers, tags, votes, comments            | [Stack Exchange Data Explorer](https://data.stackexchange.com/)                                                             |
| **TPC-H**                    | Analytical SQL, query optimisation, benchmarking            | [TPC-H tools](https://www.tpc.org/tpch/)                                                                                    |
| **NYC Taxi**                 | Time-series analysis, aggregation, geospatial queries       | [NYC TLC Trip Record Data](https://www.nyc.gov/site/tlc/about/tlc-trip-record-data.page)                                    |
| **MusicBrainz**              | Rich music metadata and many-to-many relationships          | [MusicBrainz Database](https://musicbrainz.org/doc/MusicBrainz_Database)                                                    |

### 4.1. Direct PostgreSQL-focused repositories

These are the easiest to use when you specifically want `.sql` files or PostgreSQL-compatible scripts:

- **Northwind PostgreSQL**
  https://github.com/pthom/northwind_psql

- **Chinook Database**
  https://github.com/lerocha/chinook-database
  PostgreSQL scripts are under the relevant `Chinook_PostgreSql.sql` path/version.

- **Pagila**
  https://github.com/devrimgunduz/pagila

- **DVD Rental**
  https://www.postgresqltutorial.com/postgresql-getting-started/postgresql-sample-database/

- **AdventureWorks for PostgreSQL**
  https://github.com/stephbu/AdventureWorks-for-Postgres

### 4.2. Quick import examples

#### 4.2.1. Northwind

```bash
git clone https://github.com/pthom/northwind_psql.git
cd northwind_psql
psql -U postgres -c "CREATE DATABASE northwind;"
psql -U postgres -d northwind -f northwind.sql
```

The exact filename may vary slightly by repository revision, because sample databases enjoy small acts of administrative sabotage.

#### 4.2.2. Chinook

```bash
git clone https://github.com/lerocha/chinook-database.git
cd chinook-database
psql -U postgres -c "CREATE DATABASE chinook;"
psql -U postgres -d chinook -f ChinookDatabase/DataSources/Chinook_PostgreSql.sql
```

#### 4.2.3. Pagila

```bash
git clone https://github.com/devrimgunduz/pagila.git
cd pagila
psql -U postgres -c "CREATE DATABASE pagila;"
psql -U postgres -d pagila -f pagila-schema.sql
psql -U postgres -d pagila -f pagila-insert-data.sql
```

### 4.3. Recommended teaching set

For a useful PostgreSQL sample collection:

1. **Northwind** – simple business relationships
2. **Chinook** – clean music and invoicing model
3. **Pagila** – richer rental domain
4. **DVD Rental** – PostgreSQL-specific learning
5. **AdventureWorksLT** – broader commercial model
6. **Classic Models** – sales reporting
7. **Employees Database** – procedural SQL
8. **Olist** – realistic analytics
9. **Synthea** – healthcare workflows
10. **Stack Exchange** – complex relationships and analysis
