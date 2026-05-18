terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

# ══════════════════════════════
# VARIABLES
# ══════════════════════════════
variable "db_password" {
  description = "Mot de passe PostgreSQL"
  type        = string
  sensitive   = true
}

variable "db_name" {
  description = "Nom de la base de données"
  default     = "plateformEtudiant"
}

variable "db_user" {
  description = "Utilisateur PostgreSQL"
  default     = "ensate_user"    # ← changé (admin est réservé)
}

variable "aws_region" {
  description = "Region AWS"
  default     = "eu-west-3"
}

# ══════════════════════════════
# PROVIDER AWS
# ══════════════════════════════
provider "aws" {
  region = var.aws_region
}

# ══════════════════════════════
# RESEAU VPC
# ══════════════════════════════
resource "aws_vpc" "main" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_hostnames = true
  enable_dns_support   = true
  tags = { Name = "ensate-vpc" }
}

resource "aws_subnet" "public" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.1.0/24"
  availability_zone       = "eu-west-3a"
  map_public_ip_on_launch = true
  tags = { Name = "ensate-public-subnet" }
}

resource "aws_subnet" "db_1" {
  vpc_id            = aws_vpc.main.id
  cidr_block        = "10.0.2.0/24"
  availability_zone = "eu-west-3a"
  tags = { Name = "ensate-db-subnet-1" }
}

resource "aws_subnet" "db_2" {
  vpc_id            = aws_vpc.main.id
  cidr_block        = "10.0.3.0/24"
  availability_zone = "eu-west-3b"
  tags = { Name = "ensate-db-subnet-2" }
}

resource "aws_internet_gateway" "igw" {
  vpc_id = aws_vpc.main.id
  tags   = { Name = "ensate-igw" }
}

resource "aws_route_table" "public" {
  vpc_id = aws_vpc.main.id
  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.igw.id
  }
  tags = { Name = "ensate-route-table" }
}

resource "aws_route_table_association" "public" {
  subnet_id      = aws_subnet.public.id
  route_table_id = aws_route_table.public.id
}

# ══════════════════════════════
# FIREWALL
# ══════════════════════════════
resource "aws_security_group" "app" {
  name   = "ensate-app-sg"
  vpc_id = aws_vpc.main.id

  ingress {
    description = "Frontend Angular"
    from_port   = 4200
    to_port     = 4200
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "Backend Laravel"
    from_port   = 8080
    to_port     = 8080
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "SSH"
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = { Name = "ensate-app-sg" }
}

resource "aws_security_group" "db" {
  name   = "ensate-db-sg"
  vpc_id = aws_vpc.main.id

  ingress {
    description     = "PostgreSQL depuis EC2"
    from_port       = 5432
    to_port         = 5432
    protocol        = "tcp"
    security_groups = [aws_security_group.app.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = { Name = "ensate-db-sg" }
}

# ══════════════════════════════
# BASE DE DONNEES RDS
# ══════════════════════════════
resource "aws_db_subnet_group" "main" {
  name       = "ensate-db-subnet-group"
  subnet_ids = [aws_subnet.db_1.id, aws_subnet.db_2.id]
  tags       = { Name = "ensate-db-subnet-group" }
}

resource "aws_db_instance" "postgres" {
  identifier        = "ensate-db"
  engine            = "postgres"
  engine_version    = "16"
  instance_class    = "db.t3.micro"
  allocated_storage = 20

  db_name  = var.db_name
  username = var.db_user      # ← ensate_user
  password = var.db_password

  db_subnet_group_name   = aws_db_subnet_group.main.name
  vpc_security_group_ids = [aws_security_group.db.id]

  skip_final_snapshot = true
  publicly_accessible = false

  tags = { Name = "ensate-postgres" }
}

# ══════════════════════════════
# SERVEUR EC2
# ══════════════════════════════
resource "aws_instance" "app" {
  ami                    = "ami-0302f42a44bf53a45"
  instance_type          = "t2.micro"
  subnet_id              = aws_subnet.public.id
  vpc_security_group_ids = [aws_security_group.app.id]

  user_data = <<-EOF
    #!/bin/bash
    apt-get update -y
    apt-get install -y docker.io
    systemctl start docker
    systemctl enable docker

    sleep 30

    docker run -d \
      --name backend \
      --restart always \
      -p 8080:80 \
      -e DB_HOST=${aws_db_instance.postgres.address} \
      -e DB_PORT=5432 \
      -e DB_DATABASE=${var.db_name} \
      -e DB_USERNAME=${var.db_user} \
      -e DB_PASSWORD=${var.db_password} \
      -e APP_ENV=production \
      ilyass324/backend:latest

    docker run -d \
      --name frontend \
      --restart always \
      -p 4200:80 \
      ilyass324/frontend:latest
  EOF

  tags = { Name = "ensate-server" }
}

# ══════════════════════════════
# OUTPUTS
# ══════════════════════════════
output "frontend_url" {
  value       = "http://${aws_instance.app.public_ip}:4200"
  description = "URL du Frontend"
}

output "backend_url" {
  value       = "http://${aws_instance.app.public_ip}:8080"
  description = "URL du Backend"
}

output "database_endpoint" {
  value       = aws_db_instance.postgres.address
  description = "Endpoint PostgreSQL"
}

output "server_ip" {
  value       = aws_instance.app.public_ip
  description = "IP publique du serveur"
}
