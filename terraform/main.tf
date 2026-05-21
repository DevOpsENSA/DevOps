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

variable "github_repo" {
  description = "URL du repo GitHub ex: https://github.com/user/repo.git"
  type        = string
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
# RESEAU
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
    description = "Frontend"
    from_port   = 4200
    to_port     = 4200
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "Backend"
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

# ══════════════════════════════
# SERVEUR EC2
# ══════════════════════════════
resource "aws_instance" "app" {
  ami                         = "ami-0302f42a44bf53a45"
  instance_type               = "t3.micro"
  subnet_id                   = aws_subnet.public.id
  vpc_security_group_ids      = [aws_security_group.app.id]
  user_data_replace_on_change = true

  user_data = <<-EOF
    #!/bin/bash
    exec > /var/log/user-data.log 2>&1

    # Amazon Linux 2023
    dnf update -y
    dnf install -y docker git
    systemctl start docker
    systemctl enable docker
    usermod -aG docker ec2-user

    # Install docker-compose
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose

    # Clone le repo (contient le docker-compose.yml)
    git clone ${var.github_repo} /home/ec2-user/app

    # Ecrire le .env — docker-compose le lit automatiquement
    cat > /home/ec2-user/app/.env << ENVFILE
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=plateformEtudiant
DB_USERNAME=admin
DB_PASSWORD=${var.db_password}
ENVFILE

    # Lancer tous les conteneurs
    cd /home/ec2-user/app
    docker-compose up -d

    # Attendre que la DB soit prête puis migrer
    sleep 30
    docker-compose exec -T backend php artisan migrate --force
  EOF

  tags = { Name = "ensate-server-fixed" }
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

output "server_ip" {
  value       = aws_instance.app.public_ip
  description = "IP publique du serveur"
}