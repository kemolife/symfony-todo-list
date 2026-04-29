output "endpoint" {
  description = "RDS hostname without port, for use as Kubernetes ExternalName"
  value       = split(":", aws_db_instance.this.endpoint)[0]
}

output "db_name" {
  description = "Name of the database created in the RDS instance"
  value       = aws_db_instance.this.db_name
}
