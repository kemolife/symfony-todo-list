mock_provider "aws" {
  mock_data "aws_iam_policy_document" {
    defaults = {
      json = "{\"Version\":\"2012-10-17\",\"Statement\":[]}"
    }
  }
}

variables {
  name                       = "test-sqs"
  oidc_provider_arn          = "arn:aws:iam::123456789012:oidc-provider/oidc.eks.eu-central-1.amazonaws.com/id/ABCDEF"
  oidc_provider_url          = "oidc.eks.eu-central-1.amazonaws.com/id/ABCDEF"
  kubernetes_namespace       = "default"
  kubernetes_service_account = "backend"
}

run "queue_name" {
  command = plan

  assert {
    condition     = aws_sqs_queue.this.name == "test-sqs"
    error_message = "Queue name should match var.name"
  }
}

run "dlq_name" {
  command = plan

  assert {
    condition     = aws_sqs_queue.dlq.name == "test-sqs-dlq"
    error_message = "DLQ name should be var.name + -dlq"
  }
}

run "dlq_retention_14_days" {
  command = plan

  assert {
    condition     = aws_sqs_queue.dlq.message_retention_seconds == 1209600
    error_message = "DLQ should retain messages for 14 days"
  }
}

run "irsa_role_name" {
  command = plan

  assert {
    condition     = aws_iam_role.irsa.name == "test-sqs-irsa"
    error_message = "IRSA role name should be var.name + -irsa"
  }
}
