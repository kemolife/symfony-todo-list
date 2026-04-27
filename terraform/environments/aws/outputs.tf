output "cluster_name" {
  value = module.eks.cluster_name
}

output "cluster_endpoint" {
  value = module.eks.cluster_endpoint
}

output "rds_endpoint" {
  description = "Use in k8s/overlays/aws/patches/external-services.yaml"
  value       = module.rds.endpoint
}

output "redis_endpoint" {
  description = "Use in k8s/overlays/aws/patches/external-services.yaml"
  value       = module.elasticache.endpoint
}

output "rabbitmq_endpoint" {
  description = "Use in k8s/overlays/aws/patches/external-services.yaml"
  value       = module.mq.endpoint
}
