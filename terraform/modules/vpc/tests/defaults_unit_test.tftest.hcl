mock_provider "aws" {}

variables {
  name = "test"
  azs  = ["eu-west-1a", "eu-west-1b"]
}

run "default_cidr_block" {
  command = plan

  assert {
    condition     = aws_vpc.this.cidr_block == "10.0.0.0/16"
    error_message = "Default VPC CIDR should be 10.0.0.0/16"
  }
}

run "dns_settings_enabled" {
  command = plan

  assert {
    condition     = aws_vpc.this.enable_dns_support == true
    error_message = "DNS support should be enabled"
  }

  assert {
    condition     = aws_vpc.this.enable_dns_hostnames == true
    error_message = "DNS hostnames should be enabled"
  }
}

run "subnet_count_matches_azs" {
  command = plan

  assert {
    condition     = length(aws_subnet.public) == 2
    error_message = "Should create one public subnet per AZ"
  }

  assert {
    condition     = length(aws_subnet.private) == 2
    error_message = "Should create one private subnet per AZ"
  }
}

run "public_subnets_map_public_ip" {
  command = plan

  assert {
    condition = alltrue([
      for s in aws_subnet.public : s.map_public_ip_on_launch == true
    ])
    error_message = "Public subnets should auto-assign public IPs"
  }
}

run "private_subnets_no_public_ip" {
  command = plan

  assert {
    condition = alltrue([
      for s in aws_subnet.private : s.map_public_ip_on_launch == false
    ])
    error_message = "Private subnets should not auto-assign public IPs"
  }
}

run "custom_subnet_cidrs" {
  command = plan

  variables {
    public_subnets  = ["10.1.0.0/24"]
    private_subnets = ["10.1.10.0/24"]
    azs             = ["eu-west-1a"]
  }

  assert {
    condition     = length(aws_subnet.public) == 1
    error_message = "Should create one public subnet when one CIDR provided"
  }

  assert {
    condition     = length(aws_subnet.private) == 1
    error_message = "Should create one private subnet when one CIDR provided"
  }
}
