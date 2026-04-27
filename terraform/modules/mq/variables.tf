variable "name" {
  type = string
}

variable "subnet_ids" {
  type = list(string)
}

variable "vpc_id" {
  type = string
}

variable "allowed_security_group_id" {
  type = string
}

variable "mq_username" {
  type = string
}

variable "mq_password" {
  type      = string
  sensitive = true
}

variable "instance_type" {
  type    = string
  default = "mq.m5.large"
}
