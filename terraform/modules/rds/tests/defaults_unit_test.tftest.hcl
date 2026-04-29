mock_provider "aws" {}

variables {
  name                      = "test-db"
  vpc_id                    = "vpc-12345678"
  subnet_ids                = ["subnet-aaaa1111", "subnet-bbbb2222"]
  allowed_security_group_id = "sg-12345678"
  db_name                   = "appdb"
  db_username               = "admin"
  db_password               = "supersecretpassword"
}

run "deletion_protection_enabled" {
  command = plan

  assert {
    condition     = aws_db_instance.this.deletion_protection == true
    error_message = "Deletion protection must always be enabled"
  }
}

run "final_snapshot_required" {
  command = plan

  assert {
    condition     = aws_db_instance.this.skip_final_snapshot == false
    error_message = "Final snapshot must not be skipped"
  }
}

run "backup_retention_period" {
  command = plan

  assert {
    condition     = aws_db_instance.this.backup_retention_period == 7
    error_message = "Backup retention should be 7 days"
  }
}

run "default_multi_az_disabled" {
  command = plan

  assert {
    condition     = aws_db_instance.this.multi_az == false
    error_message = "Multi-AZ should be disabled by default"
  }
}

run "storage_autoscaling_configured" {
  command = plan

  assert {
    condition     = aws_db_instance.this.allocated_storage == 20
    error_message = "Initial storage should be 20 GB"
  }

  assert {
    condition     = aws_db_instance.this.max_allocated_storage == 100
    error_message = "Autoscaling ceiling should be 100 GB"
  }
}

run "postgres_engine" {
  command = plan

  assert {
    condition     = aws_db_instance.this.engine == "postgres"
    error_message = "Database engine should be postgres"
  }

  assert {
    condition     = aws_db_instance.this.engine_version == "16"
    error_message = "PostgreSQL version should be 16"
  }
}

run "default_instance_class" {
  command = plan

  assert {
    condition     = aws_db_instance.this.instance_class == "db.t3.micro"
    error_message = "Default instance class should be db.t3.micro"
  }
}

run "security_group_postgres_port" {
  command = plan

  assert {
    condition     = one(aws_security_group.rds.ingress).from_port == 5432
    error_message = "Security group should allow ingress on PostgreSQL port 5432"
  }

  assert {
    condition     = one(aws_security_group.rds.ingress).to_port == 5432
    error_message = "Security group to_port should be 5432"
  }
}
