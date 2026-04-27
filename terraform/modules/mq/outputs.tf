output "endpoint" {
  value = aws_mq_broker.this.instances[0].endpoints[0]
}

output "broker_id" {
  value = aws_mq_broker.this.id
}
