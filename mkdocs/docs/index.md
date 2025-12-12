# OpenRouterApplication Installation

## Prerequisites
* **Linux/WSL:** Docker and Docker Compose
* **Windows/Mac:** Docker Desktop

## Quick Installation

1 **Clone the project**
```bash
   git clone yaelchampclaux/openrouterapplication
   cd openrouterapplication
```

2 **Checkout to dev**
```bash
   git checkout dev
   git pull
```

3 **Run the automatic installation**
```bash
   ./start.sh
```
   
    The script will:

    - Check prerequisites
    - Ask you for a database password
    - Automatically configure all files
    - Start the containers
    - Install Symfony dependencies
    - Create the database structure

4 **Access the services**

   - Symfony Application: http://localhost:9310
   - PhpMyAdmin: http://localhost:9311
   - Documentation: http://localhost:9312

## Useful Commands
```bash
./start.sh          # Start the environment
./stop.sh           # Stop the environment
./start.sh --reset  # Completely reset
./start.sh --help   # Display help
```

## Common Issues

**Containers won't start:**
```bash
./start.sh --reset
./start.sh
```

**View logs:**
```bash
docker-compose logs -f
```