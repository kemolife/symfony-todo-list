# Symfony Todo App

Full-stack todo application — Symfony 7 REST API + React 19 SPA.

## Features

**Auth**
- JWT authentication with TOTP two-factor authentication
- Role-based access control (`ROLE_USER`, `ROLE_ADMIN`)
- API key authentication with per-key permissions (`read`, `write`, `delete`)

**Todo**
- CRUD for todo lists with status, tags, description
- Nested todo items with completion tracking and ordering
- CSV import with column mapping
- Tag filtering, search, pagination
- Soft delete — completed lists auto-archived by scheduler

**Admin**
- User management (create, edit, delete, role assignment)
- View and revoke any user's API keys
- Admin todo overview across all users
- Audit log

**Infrastructure**
- Rate limiting — 1 000 req/hour per API key
- Redis caching, RabbitMQ async messaging
- Email notifications (Mailpit in dev)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Symfony 7.4, PHP 8.4, FrankenPHP |
| Frontend | React 19, TypeScript, Vite, Tailwind CSS |
| Database | PostgreSQL 16 |
| Queue | RabbitMQ 3.13 |
| Cache | Redis 7 |
| Orchestration | Kubernetes (Kustomize) |
| Infrastructure | Terraform (AWS EKS, RDS, ElastiCache, Amazon MQ) |

---

## Local Setup (Docker)

```bash
git clone <repo-url> && cd symfony-learn-7

docker compose up -d
docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
```

| Service | URL |
|---|---|
| Frontend | http://localhost:5173 |
| Backend API | http://localhost:8080/api |
| Mailpit | http://localhost:8025 |
| RabbitMQ | http://localhost:15672 (guest/guest) |

---

## Local Setup (without Docker)

```bash
# Backend
cd backend
composer install
cp .env .env.local   # set DATABASE_URL, REDIS_URL, MESSENGER_TRANSPORT_DSN, MAILER_DSN
php bin/console doctrine:migrations:migrate --no-interaction
symfony server:start

# Frontend
cd frontend
npm install
npm run dev
```

---

## Environment Variables

| Variable | Description |
|---|---|
| `DATABASE_URL` | PostgreSQL connection string |
| `REDIS_URL` | Redis connection string |
| `MESSENGER_TRANSPORT_DSN` | RabbitMQ or Redis DSN |
| `JWT_PASSPHRASE` | Passphrase for JWT private key |
| `MAILER_DSN` | SMTP DSN (`smtp://localhost:1025` for Mailpit) |
| `ADMIN_SECRET` | Secret required to register the first admin |
| `CORS_ALLOW_ORIGIN` | Regex of allowed origins |

---

## API

Base path: `/api`

### Auth
| Method | Path | Description |
|---|---|---|
| POST | `/auth/login` | Login, returns JWT |
| POST | `/auth/register` | Register user |
| POST | `/auth/2fa` | Complete 2FA verification |
| POST | `/2fa/enroll` | Enroll TOTP device |

### Todos
| Method | Path | Description |
|---|---|---|
| GET | `/todos` | List todos (paginated, filterable) |
| POST | `/todos` | Create todo |
| GET | `/todos/{id}` | Get todo |
| PUT | `/todos/{id}` | Update todo |
| DELETE | `/todos/{id}` | Delete todo |
| GET | `/todos/tags` | List unique tags |
| POST | `/todos/import` | Import from CSV |
| GET | `/todos/{id}/items` | List items |
| POST | `/todos/{id}/items` | Add item |
| PATCH | `/todos/{id}/items/{itemId}` | Toggle completion |
| DELETE | `/todos/{id}/items/{itemId}` | Delete item |

### Profile / API Keys
| Method | Path | Description |
|---|---|---|
| GET | `/profile` | Profile info |
| GET | `/profile/api-keys` | List own API keys |
| POST | `/profile/api-keys` | Create API key |
| DELETE | `/profile/api-keys/{id}` | Revoke API key |

### Admin
| Method | Path | Description |
|---|---|---|
| GET | `/admin/users` | List users |
| POST | `/admin/users` | Create user |
| PUT | `/admin/users/{id}` | Update user |
| DELETE | `/admin/users/{id}` | Delete user |
| GET | `/admin/users/{id}/api-keys` | List user's API keys |
| DELETE | `/admin/api-keys/{id}` | Revoke any API key |

### API Key Auth

```
X-Api-Key: <your-key>
```

Permissions: `read` (view), `write` (create/update), `delete` (delete).

---

## Kubernetes

```
k8s/
  base/          ← manifests defined once
  overlays/
    local/       ← self-hosted, all services in-cluster
    aws/         ← EKS + managed services (RDS, ElastiCache, AmazonMQ)
```

### Self-hosted (minikube)

```bash
minikube start && minikube addons enable ingress

docker build -t backend:local ./backend && minikube image load backend:local
docker build -t frontend:local ./frontend && minikube image load frontend:local

kubectl create secret generic backend-secrets \
  --from-literal=DATABASE_URL='postgresql://app:pass@postgres:5432/app' \
  --from-literal=JWT_PASSPHRASE='your-passphrase' \
  --from-literal=REDIS_URL='redis://redis' \
  --from-literal=MESSENGER_TRANSPORT_DSN='amqp://guest:guest@rabbitmq:5672/%2f/messages' \
  --from-literal=APP_SECRET='your-secret' \
  --from-literal=ADMIN_SECRET='your-admin-secret'

kubectl create secret generic postgres-secrets \
  --from-literal=POSTGRES_DB=app \
  --from-literal=POSTGRES_USER=app \
  --from-literal=POSTGRES_PASSWORD=changeme

kubectl create secret generic rabbitmq-secrets \
  --from-literal=RABBITMQ_DEFAULT_USER=guest \
  --from-literal=RABBITMQ_DEFAULT_PASS=guest

kubectl apply -k k8s/overlays/local
kubectl exec deploy/backend -- php bin/console doctrine:migrations:migrate --no-interaction
minikube tunnel  # visit http://localhost
```

### AWS (EKS)

```bash
# 1. Provision infrastructure
cd terraform/environments/aws
cp terraform.tfvars.example terraform.tfvars
terraform init -backend-config="bucket=your-tf-state-bucket" \
               -backend-config="key=symfony-app/terraform.tfstate" \
               -backend-config="region=eu-west-1"
terraform apply

# 2. Fill endpoints into k8s/overlays/aws/patches/external-services.yaml
terraform output rds_endpoint
terraform output redis_endpoint
terraform output rabbitmq_endpoint

# 3. Configure kubectl
aws eks update-kubeconfig --name symfony-app --region eu-west-1

# 4. Build and push images
echo $GITHUB_TOKEN | docker login ghcr.io -u USERNAME --password-stdin
docker build -t ghcr.io/vitaliiantoniuk/backend:latest ./backend
docker build -t ghcr.io/vitaliiantoniuk/frontend:latest ./frontend \
  --build-arg VITE_API_URL=https://your-domain.com/api
docker push ghcr.io/vitaliiantoniuk/backend:latest
docker push ghcr.io/vitaliiantoniuk/frontend:latest

# 5. Deploy
kubectl apply -k k8s/overlays/aws
kubectl exec deploy/backend -- php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Testing

```bash
cd backend && ./vendor/bin/phpunit
cd frontend && npm run test
```
