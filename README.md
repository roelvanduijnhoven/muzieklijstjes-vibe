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
*   **Example:** A newsletter publishes the "Critics Poll," which aggregates the unranked Top 10s of 10 different critics into a final ordered list.
*   **Function:** The application must be able to construct these dynamically.
    

### 3. Sources

*   **Critic:** The author of a specific list.
*   **Magazine/Publication:** The entity where the list was published. Users can filter data by Magazine.

        