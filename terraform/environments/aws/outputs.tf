output "cluster_name" {
  description = "Name of the EKS cluster"
  value       = module.eks.cluster_name
}

output "cluster_endpoint" {
  description = "API server endpoint of the EKS cluster"
  value       = module.eks.cluster_endpoint
}

output "rds_endpoint" {
  description = "Use in k8s/overlays/aws/patches/external-services.yaml"
  value       = module.rds.endpoint
}

output "redis_endpoint" {
  description = "Use in k8s/overlays/aws/patches/external-services.yaml"
  value       = module.elasticache.endpoint
}

output "sqs_queue_url" {
  description = "Set as MESSENGER_TRANSPORT_DSN in backend k8s secret"
  value       = module.sqs.queue_url
}

output "sqs_irsa_role_arn" {
  description = "Annotate backend ServiceAccount: eks.amazonaws.com/role-arn"
  value       = module.sqs.irsa_role_arn
}

output "ingress_nginx_status" {
  description = "Run: kubectl get svc -n ingress-nginx ingress-nginx-controller to get NLB hostname"
  value       = helm_release.ingress_nginx.status
}

output "ecr_backend_url" {
  description = "ECR URL for backend image"
  value       = aws_ecr_repository.backend.repository_url
}

output "ecr_frontend_url" {
  description = "ECR URL for frontend image"
  value       = aws_ecr_repository.frontend.repository_url
}
