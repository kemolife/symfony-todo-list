variable "aws_region" {
  description = "AWS region to deploy resources into"
  type        = string
  default     = "eu-west-1"
}

variable "name" {
  description = "Name prefix for all resources"
  type        = string
  default     = "symfony-app"
}

variable "azs" {
  description = "Availability zones for subnet distribution"
  type        = list(string)
  default     = ["eu-west-1a", "eu-west-1b"]
}

variable "db_name" {
  description = "Name of the PostgreSQL database to create"
  type        = string
}

variable "db_username" {
  description = "Master username for the PostgreSQL database"
  type        = string
}

variable "db_password" {
  description = "Master password for the PostgreSQL database"
  type        = string
  sensitive   = true
}

