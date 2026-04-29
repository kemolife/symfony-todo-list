variable "name" {
  description = "Name prefix for RDS resources"
  type        = string
}

variable "subnet_ids" {
  description = "Private subnet IDs for the RDS subnet group"
  type        = list(string)
}

variable "vpc_id" {
  description = "VPC ID where the RDS instance is deployed"
  type        = string
}

variable "allowed_security_group_id" {
  description = "EKS node security group allowed to connect on 5432"
  type        = string
}

variable "db_name" {
  description = "Name of the database to create"
  type        = string
}

variable "db_username" {
  description = "Master username for the database"
  type        = string
}

variable "db_password" {
  description = "Master password for the database"
  type        = string
  sensitive   = true
}

variable "instance_class" {
  description = "RDS instance class"
  type        = string
  default     = "db.t3.micro"
}

variable "multi_az" {
  description = "Enable Multi-AZ deployment for high availability"
  type        = bool
  default     = false
}
