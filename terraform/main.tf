terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

variable "db_password" {
  description = "Mot de passe PostgreSQL"
  type        = string
  sensitive   = true
}

variable "github_repo" {
  description = "URL du repo GitHub"
  type        = string
}

variable "aws_region" {
  description = "Region AWS"
  default     = "eu-west-3"
}

provider "aws" {
  region = var.aws_region
}

resource "aws_vpc" "main" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_hostnames = true
  enable_dns_support   = true
  tags                 = { Name = "ensate-vpc" }
}

resource "aws_subnet" "public" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.1.0/24"
  availability_zone       = "eu-west-3a"
  map_public_ip_on_launch = true
  tags                    = { Name = "ensate-public-subnet" }
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

resource "aws_security_group" "app" {
  name   = "ensate-app-sg"
  vpc_id = aws_vpc.main.id

  ingress {
    from_port   = 4200
    to_port     = 4200
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    from_port   = 8080
    to_port     = 8080
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
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

resource "aws_instance" "app" {
  ami                         = "ami-0302f42a44bf53a45"
  instance_type               = "t3.small"
  subnet_id                   = aws_subnet.public.id
  vpc_security_group_ids      = [aws_security_group.app.id]
  key_name                    = "keypair"
  user_data_replace_on_change = true

  user_data = <<USERDATA
#!/bin/bash
exec > /var/log/user-data.log 2>&1

echo "STEP 1: dnf update"
dnf update -y

echo "STEP 2: install docker and git"
dnf install -y docker git

echo "STEP 3: start docker"
systemctl start docker
systemctl enable docker
usermod -aG docker ec2-user

echo "STEP 4: install docker-compose"
curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-Linux-x86_64" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
/usr/local/bin/docker-compose --version

echo "STEP 5: clone repo"
cd /home/ec2-user
git clone ${var.github_repo} app
ls -la app/

echo "STEP 6: write .env"
cat > /home/ec2-user/app/.env <<DOTENV
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=plateformEtudiant
DB_USERNAME=admin
DB_PASSWORD=${var.db_password}
DOTENV
cat /home/ec2-user/app/.env

echo "STEP 7: docker-compose up"
cd /home/ec2-user/app
/usr/local/bin/docker-compose up -d
/usr/local/bin/docker-compose ps

echo "STEP 8: wait and migrate"
sleep 60
/usr/local/bin/docker-compose exec -T backend php artisan migrate --force

echo "ALL DONE"
USERDATA

  tags = { Name = "ensate-server-fixed" }
}

output "frontend_url" {
  value = "http://${aws_instance.app.public_ip}:4200"
}

output "backend_url" {
  value = "http://${aws_instance.app.public_ip}:8080"
}

output "server_ip" {
  value = aws_instance.app.public_ip
}