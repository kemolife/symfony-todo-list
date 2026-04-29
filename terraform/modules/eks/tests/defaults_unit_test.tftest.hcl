mock_provider "aws" {
  mock_data "aws_iam_policy_document" {
    defaults = {
      json = "{\"Version\":\"2012-10-17\",\"Statement\":[]}"
    }
  }

  mock_resource "aws_eks_cluster" {
    defaults = {
      identity = [
        {
          oidc = [
            {
              issuer = "https://oidc.eks.eu-west-1.amazonaws.com/id/EXAMPLE123"
            }
          ]
        }
      ]
      certificate_authority = [
        {
          data = "dGVzdA=="
        }
      ]
      vpc_config = [
        {
          cluster_security_group_id = "sg-mock12345678"
          endpoint_private_access   = false
          endpoint_public_access    = true
          public_access_cidrs       = ["0.0.0.0/0"]
          security_group_ids        = []
          subnet_ids                = []
          vpc_id                    = "vpc-mock12345678"
        }
      ]
    }
  }
}

mock_provider "tls" {
  mock_data "tls_certificate" {
    defaults = {
      certificates = [
        {
          is_ca                = true
          not_after            = "2030-01-01T00:00:00Z"
          not_before           = "2024-01-01T00:00:00Z"
          public_key_algorithm = "RSA"
          serial_number        = "1"
          sha1_fingerprint     = "9e99a48a9960b14926bb7f3b02e22da2b0ab7280"
          subject              = []
          version              = 3
        }
      ]
    }
  }
}

variables {
  name       = "test-cluster"
  vpc_id     = "vpc-12345678"
  subnet_ids = ["subnet-aaaa1111", "subnet-bbbb2222"]
}

run "default_kubernetes_version" {
  command = plan

  assert {
    condition     = aws_eks_cluster.this.version == "1.31"
    error_message = "Default Kubernetes version should be 1.31"
  }
}

run "cluster_name_from_variable" {
  command = plan

  assert {
    condition     = aws_eks_cluster.this.name == "test-cluster"
    error_message = "Cluster name should match input variable"
  }
}

run "default_node_instance_type" {
  command = plan

  assert {
    condition     = aws_eks_node_group.this.instance_types == tolist(["t3.medium"])
    error_message = "Default node instance type should be t3.medium"
  }
}

run "default_scaling_config" {
  command = plan

  assert {
    condition     = aws_eks_node_group.this.scaling_config[0].desired_size == 2
    error_message = "Default desired node count should be 2"
  }

  assert {
    condition     = aws_eks_node_group.this.scaling_config[0].min_size == 1
    error_message = "Default min node count should be 1"
  }

  assert {
    condition     = aws_eks_node_group.this.scaling_config[0].max_size == 5
    error_message = "Default max node count should be 5"
  }
}

run "custom_scaling_config" {
  command = plan

  variables {
    node_desired = 3
    node_min     = 2
    node_max     = 10
  }

  assert {
    condition     = aws_eks_node_group.this.scaling_config[0].desired_size == 3
    error_message = "Custom desired size should be applied"
  }

  assert {
    condition     = aws_eks_node_group.this.scaling_config[0].max_size == 10
    error_message = "Custom max size should be applied"
  }
}
