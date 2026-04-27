terraform {
  required_providers {
    docker = {
      source  = "kreuzwerker/docker"
      version = "~> 3.0"
    }
  }
}

provider "docker" {}

resource "docker_image" "postgres" {
  name = "postgres:16"
}

resource "docker_image" "backend" {
  name = "ilyass324/backend:latest"
}

resource "docker_image" "frontend" {
  name = "ilyass324/frontend:latest"
}

resource "docker_network" "mon_reseau" {
  name = "mon-app-network"
}

resource "docker_container" "db" {
  name  = "db"
  image = docker_image.postgres.image_id

  networks_advanced {
    name = docker_network.mon_reseau.name
  }

  env = [
    "POSTGRES_DB=plateformEtudiant",
    "POSTGRES_USER=admin",
    "POSTGRES_PASSWORD=admin123"
  ]

  ports {
    internal = 5432
    external = 5433
  }
}

resource "docker_container" "backend" {
  name  = "backend"
  image = docker_image.backend.image_id

  networks_advanced {
    name = docker_network.mon_reseau.name
  }

  env = [
    "DB_HOST=db",
    "DB_PORT=5432",
    "DB_NAME=plateformEtudiant",
    "DB_USER=admin",
    "DB_PASSWORD=admin123"
  ]

  ports {
    internal = 80
    external = 8080
  }

  depends_on = [docker_container.db]
}

resource "docker_container" "frontend" {
  name  = "frontend"
  image = docker_image.frontend.image_id

  networks_advanced {
    name = docker_network.mon_reseau.name
  }

  ports {
    internal = 80
    external = 4200
  }

  depends_on = [docker_container.backend]
}