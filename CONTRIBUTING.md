# Contributing to Register Vibe

First off, thanks for taking the time to contribute!

The following is a set of guidelines for contributing to Register Vibe. These are mostly guidelines, not rules. Use your best judgment, and feel free to propose changes to this document in a pull request.

## Prerequisites

You will need the following tools installed on your local machine:

*   [Docker](https://docs.docker.com/get-docker/)
*   [Docker Compose](https://docs.docker.com/compose/install/)
*   [Git](https://git-scm.com/downloads)

## Getting Started

1.  **Clone the repository**

    ```bash
    git clone <repository-url>
    cd register-vibe
    ```

2.  **Start the environment**

    We use Docker Compose to run the application. The setup uses your current user ID to ensure file permissions are handled correctly between the host and the container.

    ```bash
    # Export your user ID (required for Linux)
    export UID=$(id -u)
    
    # Build and start the containers
    docker compose up -d
    ```

    Note: If you encounter permission issues, ensure the `UID` variable is set in your shell before running docker commands.

3.  **Install dependencies**

    Use the provided `run` script to execute commands inside the container. This ensures dependencies are installed in the container environment.

    ```bash
    ./run composer install
    ```

4.  **Access the application**

    The application should now be running at http://register.localhost.

## Development Workflow

### Helper Scripts

The project includes two helper scripts in the root directory to simplify running commands:

*   `./run <command>`: Executes a shell command inside the `web` service container.
    *   Example: `./run composer require symfony/validator`
    *   Example: `./run bash` (opens a shell session inside the container)

*   `./runapp <command>`: A shortcut for running Symfony Console commands (`bin/console`).
    *   Example: `./runapp list`
    *   Example: `./runapp make:controller`
    *   Example: `./runapp cache:clear`

### Database

The project uses MySQL as the primary database.

*   **Host**: `mysql`
*   **Database**: `dev`
*   **User**: `jouwweb`
*   **Password**: `jouwweb`

#### Database Management

A **PHPMyAdmin** instance is available at [http://localhost:8000](http://localhost:8000) for managing the database via a web interface.

### Directory Structure

*   `bin/`: Executable binaries (e.g., `console`).
*   `config/`: Symfony configuration files.
*   `public/`: Web server document root.
*   `src/`: Application source code (Controllers, Entities, Services).
*   `templates/`: Twig templates.
*   `migrations/`: Database migrations.

## Code Style

*   Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards for PHP.
*   Follow Symfony best practices.
