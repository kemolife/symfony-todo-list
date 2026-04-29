variable "name" {
  description = "Name prefix for ElastiCache resources"
  type        = string
}

variable "subnet_ids" {
  description = "Private subnet IDs for the ElastiCache subnet group"
  type        = list(string)
}

variable "vpc_id" {
  description = "VPC ID where the ElastiCache cluster is deployed"
  type        = string
}

variable "allowed_security_group_id" {
  description = "Security group ID allowed to connect on port 6379"
  type        = string
}

variable "node_type" {
  description = "ElastiCache node type"
  type        = string
  default     = "cache.t3.micro"
}
