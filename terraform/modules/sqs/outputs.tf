output "queue_url" {
  description = "SQS queue URL for MESSENGER_TRANSPORT_DSN"
  value       = aws_sqs_queue.this.url
}

output "queue_name" {
  description = "SQS queue name"
  value       = aws_sqs_queue.this.name
}

output "dlq_url" {
  description = "Dead-letter queue URL"
  value       = aws_sqs_queue.dlq.url
}

output "irsa_role_arn" {
  description = "IRSA IAM role ARN — annotate backend ServiceAccount with this"
  value       = aws_iam_role.irsa.arn
}
