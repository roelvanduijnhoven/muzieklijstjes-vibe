Register
========

**Register** is a web application designed to aggregate, track, and analyze music album lists published by critics in newsletters and magazines. It serves as a centralized database that converts subjective critic lists into data-driven aggregate scores.

📖 Project Overview
-------------------

In the world of music journalism, critics frequently publish lists of their favorite albums (e.g., "Top 10 of the Year"). These lists appear in various magazines and newsletters. **Register** allows users to browse these individual lists and, more importantly, view **Aggregate Lists** that calculate a consensus score based on the input of multiple critics.

🌟 Core Features
----------------

*   **Browse Database:** Search and view a comprehensive database of music albums.
*   **Critic Lists:** View specific lists created by individual critics from various publications.
*   **Aggregation Engine:** Automatically generate "Meta-Lists" by combining data from multiple critic lists (e.g., combining 10 unranked top-10 lists to find the true consensus favorites).
*   **Magazine Filtering:** Filter lists and aggregates based on the source publication (Magazine/Newsletter).
    

🗂 Data Model & Logic
---------------------

### 1. The Album Entity

The fundamental unit of the database.

*   **Attributes:**
    
    *   Title: String
    *   Release Year: Number
    *   Artist: String (Constraint: An album belongs to **exactly one** artist).
        

### 2. List Architecture

There are three fundamental types of lists within the application:

#### A. Ordered Lists (Ranked)

A list where position matters.

*   **Structure:** Ordered set of albums (#1, #2, ... #N).
*   **Constraint:** The length ($N$) varies per list (e.g., Top 10, Top 50, Top 100).
*   **Scoring:** Higher rank implies a higher score in aggregation logic.
    

#### B. Unordered Lists (Unranked)

A collection of "Best" albums without a hierarchy.

*   **Structure:** A set of albums.
*   **Context:** Used when a critic selects their favorites (e.g., "The 10 Best Albums of 2024") but declines to rank them numerically.
*   **Scoring:** All albums in this list receive equal weight.
    

#### C. Aggregate Lists (Derived)

A meta-list constructed computationally from other lists.

*   **Source:** Composed of multiple Source Lists (Type A or Type B).
*   **Materialization:** Aggregate lists are **materialized** in the database. When source lists are updated, the derived aggregate items (and their mention scores) are automatically recalculated and stored as `AlbumListItem` entries with a computed `mentions` count.
*   **Example:** A newsletter publishes the "Critics Poll," which aggregates the unranked Top 10s of 10 different critics into a final ordered list.
    

### 3. Sources

*   **Critic:** The author of a specific list.
*   **Magazine/Publication:** The entity where the list was published. Users can filter data by Magazine.

### Importing Legacy Data

To import the legacy SQL dump into the database, you first need to create a separate `legacy` database and import the data there.

1.  **Create the legacy database:**

    ```bash
    docker compose exec -T mysql mysql -u root -pjouwweb -e "CREATE DATABASE IF NOT EXISTS legacy; GRANT ALL PRIVILEGES ON legacy.* TO 'jouwweb'@'%'; FLUSH PRIVILEGES;"
    ```

2.  **Import the SQL dump:**

    ```bash
    docker compose exec -T mysql mysql -u jouwweb -pjouwweb legacy < resources/import/final.sql
    ```

3.  **Ensure Application Schema Exists:**

    Before importing data into the application, make sure the application database schema is created:

    ```bash
    ./runapp doctrine:schema:update --force
    ```

4.  **Run the Import Command:**

    To import data from the legacy MySQL database into the application entities, use the `app:import-legacy` command.


**Important:** Due to the large dataset size, you should run the import with the `--no-debug` flag to avoid memory exhaustion issues caused by Doctrine's debug data collection.

```bash
# Run import (resets existing data)
./runapp app:import-legacy --reset --no-debug
```

This command will:
1. Truncate existing tables (if `--reset` is used).
2. Import Artists, Magazines, Critics, Albums, Reviews, and Lists.
3. Create individual critic lists derived from aggregate lists.

### Materializing Aggregate Lists

Because aggregate lists are derived from other lists, you may need to run an aggregation pass to calculate their contents initially (e.g. after import):

```bash
./runapp app:aggregate-lists
```

This command iterates over all lists of type "aggregate" and populates their items based on the source lists.

🎵 Album Covers
---------------

The application automatically fetches album covers from MusicBrainz and stores them in DigitalOcean Spaces (or any S3-compatible storage).

### Configuration

Ensure the following variables are set in your `.env` or `.env.local`:

```dotenv
DO_SPACES_REGION=ams3
DO_SPACES_ENDPOINT=https://ams3.digitaloceanspaces.com
DO_SPACES_KEY=your_key
DO_SPACES_SECRET=your_secret
DO_SPACES_BUCKET=your_bucket
ALBUM_COVER_BASE_URL=https://your_bucket.ams3.digitaloceanspaces.com/
```

### Fetching Covers

To fetch missing covers, run the following command inside the container:

```bash
# Fetch covers for up to 50 albums (default)
./runapp app:fetch-covers

# Fetch covers for a specific number of albums
./runapp app:fetch-covers --limit=1000
```

This command:
1. Searches MusicBrainz for albums without a cover.
2. Downloads the cover art (if found).
3. Resizes it to 300x300px.
4. Uploads it to the configured S3 bucket.


# TO DO

- The rubric of the original dataset does not contain a newsletter
- Have the original dataset contain all rows from the stretch it
- The list ofdescriptions Should map correctly to magazines Using their short titles
