# Symfony Todo App

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?logo=symfony&logoColor=white)
![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![Kubernetes](https://img.shields.io/badge/Kubernetes-Kustomize-326CE5?logo=kubernetes&logoColor=white)

Full-stack todo application — Symfony 7 REST API backend + React 19 SPA frontend.

---

## Features

| Domain | Capabilities |
|--------|-------------|
| **Auth** | JWT + TOTP two-factor authentication, role-based access control (`ROLE_USER`, `ROLE_ADMIN`), API key auth with scoped permissions (`read`, `write`, `delete`) |
| **Todos** | CRUD with status, tags, descriptions; nested items with completion tracking and ordering; CSV import; tag filtering, full-text search, pagination; soft-delete with scheduler-driven archival |
| **Admin** | User management, role assignment, API key management, cross-user todo overview, audit log |
| **Infrastructure** | Rate limiting (1 000 req/hour per API key), Redis caching, RabbitMQ async messaging, transactional email (Mailpit in dev) |

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Symfony 7.4, PHP 8.4, FrankenPHP |
| Frontend | React 19, TypeScript, Vite, Tailwind CSS |
| Database | PostgreSQL 16 |
| Queue | RabbitMQ 3.13 |
| Cache | Redis 7 |
| Orchestration | Kubernetes (Kustomize) |
| Cloud Infrastructure | Terraform — AWS EKS, RDS, ElastiCache, Amazon MQ |

---

## Local Development (Native)

Prerequisites: PHP 8.4, Composer, Node 22, Symfony CLI, Docker (for infra).

**1. Start infrastructure**

```bash
docker run -d --name postgres -e POSTGRES_DB=app -e POSTGRES_USER=app \
  -e POSTGRES_PASSWORD=secret -p 5432:5432 postgres:16-alpine

docker run -d --name rabbitmq -e RABBITMQ_DEFAULT_USER=guest \
  -e RABBITMQ_DEFAULT_PASS=guest -p 5672:5672 -p 15672:15672 \
  rabbitmq:3.13-management-alpine

docker run -d --name redis -p 6379:6379 redis:7-alpine

docker run -d --name mailpit -p 1025:1025 -p 8025:8025 axllent/mailpit
```

**2. Backend**

```bash
cd backend
composer install
cp .env .env.local
```

Edit `.env.local`:

```dotenv
DATABASE_URL=postgresql://app:secret@127.0.0.1:5432/app?serverVersion=16&charset=utf8
REDIS_URL=redis://localhost
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f
MAILER_DSN=smtp://localhost:1025
JWT_PASSPHRASE=your-passphrase
APP_SECRET=your-secret
ADMIN_SECRET=your-admin-secret
```

```bash
php bin/console lexik:jwt:generate-keypair
php bin/console doctrine:migrations:migrate --no-interaction
symfony server:start
```

**3. Messenger worker** (separate terminal)

```bash
cd backend && php bin/console messenger:consume async -vv
```

**4. Frontend**

```bash
cd frontend
npm install
npm run dev
```

| Service | URL |
|---------|-----|
| Frontend | http://localhost:5173 |
| Backend API | http://localhost:8000/api |
| Mailpit UI | http://localhost:8025 |
| RabbitMQ UI | http://localhost:15672 (guest / guest) |

---

## Environment Variables

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | PostgreSQL connection string |
| `REDIS_URL` | Redis connection string |
| `MESSENGER_TRANSPORT_DSN` | RabbitMQ AMQP DSN |
| `JWT_PASSPHRASE` | Passphrase for JWT private key |
| `MAILER_DSN` | SMTP DSN — use `smtp://localhost:1025` for Mailpit |
| `FRONTEND_URL` | Base URL of the frontend (used in emailed links) |
| `APP_SECRET` | Symfony app secret |
| `ADMIN_SECRET` | Required to register the first admin account |
| `CORS_ALLOW_ORIGIN` | Regex of allowed CORS origins |

---

## API Reference

Base path: `/api`

### Authentication

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/auth/register` | Register new user |
| `POST` | `/auth/login` | Login — returns JWT or triggers 2FA flow |
| `POST` | `/auth/2fa` | Complete 2FA verification, returns JWT |
| `GET` | `/2fa/enroll` | Enroll TOTP device (token from email) |

### Todos

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/todos` | List todos — params: `page`, `limit`, `status`, `tag`, `search` |
| `POST` | `/todos` | Create todo |
| `GET` | `/todos/{id}` | Get todo |
| `PUT` | `/todos/{id}` | Update todo |
| `DELETE` | `/todos/{id}` | Delete todo |
| `GET` | `/todos/tags` | List unique tags |
| `POST` | `/todos/import` | Import from CSV |
| `GET` | `/todos/{id}/items` | List todo items |
| `POST` | `/todos/{id}/items` | Add todo item |
| `PATCH` | `/todos/{id}/items/{itemId}` | Toggle item completion |
| `DELETE` | `/todos/{id}/items/{itemId}` | Delete item |

### Profile & API Keys

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/profile` | Get own profile |
| `GET` | `/profile/api-keys` | List own API keys |
| `POST` | `/profile/api-keys` | Create API key |
| `DELETE` | `/profile/api-keys/{id}` | Revoke API key |

### Admin

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/admin/register` | Register admin (requires `ADMIN_SECRET`) |
| `GET` | `/admin/users` | List all users |
| `POST` | `/admin/users` | Create user |
| `PUT` | `/admin/users/{id}` | Update user |
| `DELETE` | `/admin/users/{id}` | Delete user |
| `GET` | `/admin/users/{id}/api-keys` | List user's API keys |
| `DELETE` | `/admin/api-keys/{id}` | Revoke any API key |

### API Key Authentication

Pass the key in the request header:

```
X-Api-Key: <your-api-key>
```

Permissions: `read` (GET endpoints), `write` (POST/PUT), `delete` (DELETE).

---

## Kubernetes

```
k8s/
├── base/               # shared manifests (backend, frontend, messenger, postgres, rabbitmq, redis, mailpit, ingress)
└── overlays/
    ├── local/          # self-hosted — all services run in-cluster, imagePullPolicy: Never
    └── aws/            # EKS — postgres/rabbitmq/redis replaced by RDS/AmazonMQ/ElastiCache
```

### Self-hosted (OrbStack / Docker Desktop)

**Prerequisites:** Kubernetes enabled, [ingress-nginx](https://kubernetes.github.io/ingress-nginx/) installed.

```bash
# Install ingress-nginx
kubectl apply -f https://raw.githubusercontent.com/kubernetes/ingress-nginx/controller-v1.10.1/deploy/static/provider/cloud/deploy.yaml
kubectl wait --namespace ingress-nginx \
  --for=condition=ready pod \
  --selector=app.kubernetes.io/component=controller \
  --timeout=120s
```

**Build local images**

```bash
docker build -t backend:local ./backend
docker build -t frontend:local ./frontend
```

**Generate JWT keys**

```bash
cd backend
php bin/console lexik:jwt:generate-keypair
kubectl create secret generic jwt-keys \
  --from-file=private.pem=config/jwt/private.pem \
  --from-file=public.pem=config/jwt/public.pem
```

**Create secrets**

```bash
kubectl create secret generic backend-secrets \
  --from-literal=DATABASE_URL='postgresql://app:secret@postgres:5432/app?serverVersion=16&charset=utf8' \
  --from-literal=APP_SECRET='your-secret' \
  --from-literal=JWT_PASSPHRASE='your-passphrase' \
  --from-literal=REDIS_URL='redis://redis' \
  --from-literal=MESSENGER_TRANSPORT_DSN='amqp://guest:guest@rabbitmq:5672/%2f' \
  --from-literal=MAILER_DSN='smtp://mailpit:1025' \
  --from-literal=ADMIN_SECRET='your-admin-secret'

kubectl create secret generic postgres-secrets \
  --from-literal=POSTGRES_DB=app \
  --from-literal=POSTGRES_USER=app \
  --from-literal=POSTGRES_PASSWORD=secret

kubectl create secret generic rabbitmq-secrets \
  --from-literal=RABBITMQ_DEFAULT_USER=guest \
  --from-literal=RABBITMQ_DEFAULT_PASS=guest
```

**Deploy**

```bash
kubectl apply -k k8s/overlays/local
kubectl exec deploy/backend -- php bin/console doctrine:migrations:migrate --no-interaction
```

| Service | Access |
|---------|--------|
| App | http://localhost (via ingress) |
| Mailpit UI | `kubectl port-forward deployment/mailpit 8025:8025` → http://localhost:8025 |
| PostgreSQL | `kubectl port-forward statefulset/postgres 5432:5432` → `psql -h localhost -U app app` |

### AWS (EKS)

```bash
# 1. Provision infrastructure
cd terraform/environments/aws
cp terraform.tfvars.example terraform.tfvars  # fill in your values
terraform init \
  -backend-config="bucket=your-tf-state-bucket" \
  -backend-config="key=symfony-app/terraform.tfstate" \
  -backend-config="region=eu-west-1"
terraform apply

# 2. Patch external service endpoints
terraform output rds_endpoint      # → k8s/overlays/aws/patches/external-services.yaml
terraform output redis_endpoint
terraform output rabbitmq_endpoint

# 3. Configure kubectl
aws eks update-kubeconfig --name symfony-app --region eu-west-1

# 4. Build and push images
echo $GITHUB_TOKEN | docker login ghcr.io -u USERNAME --password-stdin
docker build -t ghcr.io/USERNAME/backend:latest ./backend
docker build -t ghcr.io/USERNAME/frontend:latest ./frontend \
  --build-arg VITE_API_URL=https://your-domain.com/api
docker push ghcr.io/USERNAME/backend:latest
docker push ghcr.io/USERNAME/frontend:latest

# 5. Deploy
kubectl apply -k k8s/overlays/aws
kubectl exec deploy/backend -- php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Testing

```bash
# Backend (integration tests, rolled-back transactions)
cd backend && ./vendor/bin/phpunit

# Frontend (Vitest + MSW)
cd frontend && npm run test
```
