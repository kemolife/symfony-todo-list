#!/usr/bin/env bash
set -euo pipefail

# Required env vars — set before running or export in shell:
# export APP_SECRET=$(openssl rand -hex 32)
# export JWT_PASSPHRASE=$(openssl rand -hex 16)
# export ADMIN_SECRET=$(openssl rand -hex 16)

: "${APP_SECRET:?APP_SECRET is required}"
: "${JWT_PASSPHRASE:?JWT_PASSPHRASE is required}"
: "${ADMIN_SECRET:?ADMIN_SECRET is required}"

TERRAFORM_DIR="$(dirname "$0")/../terraform/environments/aws"

cd "$TERRAFORM_DIR"
RDS=$(terraform output -raw rds_endpoint)
REDIS=$(terraform output -raw redis_endpoint)
NLB=$(kubectl get svc -n ingress-nginx ingress-nginx-controller \
  -o jsonpath='{.status.loadBalancer.ingress[0].hostname}')
DB_PASSWORD=$(grep db_password terraform.tfvars | awk -F'"' '{print $2}')

# backend-secrets
kubectl create secret generic backend-secrets \
  --from-literal=DATABASE_URL="postgresql://app:${DB_PASSWORD}@${RDS}:5432/app?serverVersion=16&charset=utf8" \
  --from-literal=MESSENGER_TRANSPORT_DSN="sqs://default/symfony-app-async?region=eu-central-1" \
  --from-literal=REDIS_URL="redis://${REDIS}" \
  --from-literal=APP_SECRET="${APP_SECRET}" \
  --from-literal=JWT_PASSPHRASE="${JWT_PASSPHRASE}" \
  --from-literal=ADMIN_SECRET="${ADMIN_SECRET}" \
  --from-literal=MAILER_DSN="smtp://mailpit:1025" \
  --dry-run=client -o yaml | kubectl apply -f -

# backend-config
kubectl create configmap backend-config \
  --from-literal=APP_ENV=prod \
  --from-literal=APP_DEBUG=0 \
  --from-literal=FRONTEND_URL="http://${NLB}" \
  --from-literal=DEFAULT_URI="http://${NLB}" \
  --from-literal=CORS_ALLOW_ORIGIN="^https?://${NLB//./\\.}.*$" \
  --dry-run=client -o yaml | kubectl apply -f -

echo "Secrets and configmap applied."
