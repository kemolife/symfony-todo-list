variable "name" {
  description = "Cluster name"
  type        = string
}

variable "kubernetes_version" {
  description = "Kubernetes version"
  type        = string
  default     = "1.31"
}

variable "vpc_id" {
  description = "VPC ID where the cluster is deployed"
  type        = string
}

variable "subnet_ids" {
  description = "Private subnet IDs for nodes"
  type        = list(string)
}

variable "node_instance_type" {
  description = "EC2 instance type for worker nodes"
  type        = string
  default     = "t3.medium"
}

variable "node_desired" {
  description = "Desired number of worker nodes"
  type        = number
  default     = 2
}

variable "node_min" {
  description = "Minimum number of worker nodes"
  type        = number
  default     = 1
}

variable "node_max" {
  description = "Maximum number of worker nodes"
  type        = number
  default     = 5
}
