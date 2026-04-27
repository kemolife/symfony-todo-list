variable "aws_region" {
  type    = string
  default = "eu-west-1"
}

variable "name" {
  description = "Name prefix for all resources"
  type        = string
  default     = "symfony-app"
}

variable "azs" {
  type    = list(string)
  default = ["eu-west-1a", "eu-west-1b"]
}

variable "db_name" {
  type = string
}

variable "db_username" {
  type = string
}

variable "db_password" {
  type      = string
  sensitive = true
}

variable "mq_username" {
  type = string
}

variable "mq_password" {
  type      = string
  sensitive = true
}
