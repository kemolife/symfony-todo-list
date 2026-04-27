module "vpc" {
  source = "../../modules/vpc"
  name   = var.name
  azs    = var.azs
}

module "eks" {
  source     = "../../modules/eks"
  name       = var.name
  vpc_id     = module.vpc.vpc_id
  subnet_ids = module.vpc.private_subnet_ids
}

module "rds" {
  source                    = "../../modules/rds"
  name                      = "${var.name}-db"
  vpc_id                    = module.vpc.vpc_id
  subnet_ids                = module.vpc.private_subnet_ids
  allowed_security_group_id = module.eks.node_security_group_id
  db_name                   = var.db_name
  db_username               = var.db_username
  db_password               = var.db_password
}

module "elasticache" {
  source                    = "../../modules/elasticache"
  name                      = "${var.name}-redis"
  vpc_id                    = module.vpc.vpc_id
  subnet_ids                = module.vpc.private_subnet_ids
  allowed_security_group_id = module.eks.node_security_group_id
}

module "mq" {
  source                    = "../../modules/mq"
  name                      = "${var.name}-mq"
  vpc_id                    = module.vpc.vpc_id
  subnet_ids                = module.vpc.private_subnet_ids
  allowed_security_group_id = module.eks.node_security_group_id
  mq_username               = var.mq_username
  mq_password               = var.mq_password
}
