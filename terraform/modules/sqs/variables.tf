variable "name" {
  description = "Name prefix for SQS resources"
  type        = string
}

variable "oidc_provider_arn" {
  description = "ARN of the EKS OIDC provider for IRSA"
  type        = string
}

variable "oidc_provider_url" {
  description = "URL of the EKS OIDC provider without https:// for IRSA trust policy"
  type        = string
}

variable "kubernetes_namespace" {
  description = "Kubernetes namespace of the service account"
  type        = string
  default     = "default"
}

variable "kubernetes_service_account" {
  description = "Kubernetes service account name allowed to assume the IRSA role"
  type        = string
  default     = "backend"
}
