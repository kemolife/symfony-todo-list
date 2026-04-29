mock_provider "aws" {}

variables {
  name                      = "test-redis"
  vpc_id                    = "vpc-12345678"
  subnet_ids                = ["subnet-aaaa1111", "subnet-bbbb2222"]
  allowed_security_group_id = "sg-12345678"
}

run "redis_engine" {
  command = plan

  assert {
    condition     = aws_elasticache_cluster.this.engine == "redis"
    error_message = "Engine should be redis"
  }
}

run "redis_port" {
  command = plan

  assert {
    condition     = aws_elasticache_cluster.this.port == 6379
    error_message = "Redis port should be 6379"
  }
}

run "single_cache_node" {
  command = plan

  assert {
    condition     = aws_elasticache_cluster.this.num_cache_nodes == 1
    error_message = "Should create a single cache node"
  }
}

run "default_node_type" {
  command = plan

  assert {
    condition     = aws_elasticache_cluster.this.node_type == "cache.t3.micro"
    error_message = "Default node type should be cache.t3.micro"
  }
}

run "security_group_ingress_port" {
  command = plan

  assert {
    condition     = one(aws_security_group.redis.ingress).from_port == 6379
    error_message = "Security group should allow ingress on port 6379"
  }

  assert {
    condition     = one(aws_security_group.redis.ingress).to_port == 6379
    error_message = "Security group to_port should be 6379"
  }
}

run "custom_node_type" {
  command = plan

  variables {
    node_type = "cache.r6g.large"
  }

  assert {
    condition     = aws_elasticache_cluster.this.node_type == "cache.r6g.large"
    error_message = "Custom node type should be applied"
  }
}
