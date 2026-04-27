resource "aws_security_group" "mq" {
  name   = "${var.name}-mq-sg"
  vpc_id = var.vpc_id

  ingress {
    from_port       = 5671
    to_port         = 5671
    protocol        = "tcp"
    security_groups = [var.allowed_security_group_id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_mq_broker" "this" {
  broker_name         = var.name
  engine_type         = "RabbitMQ"
  engine_version      = "3.13"
  host_instance_type  = var.instance_type
  deployment_mode     = "SINGLE_INSTANCE"
  publicly_accessible = false
  subnet_ids          = [var.subnet_ids[0]]
  security_groups     = [aws_security_group.mq.id]

  user {
    username = var.mq_username
    password = var.mq_password
  }
}
