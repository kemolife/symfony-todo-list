TERRAFORM_MODULES := vpc rds elasticache sqs eks
TERRAFORM_DIR     := terraform/environments/aws
BACKEND_ECR       := $(shell cd $(TERRAFORM_DIR) && terraform output -raw ecr_backend_url 2>/dev/null)
FRONTEND_ECR      := $(shell cd $(TERRAFORM_DIR) && terraform output -raw ecr_frontend_url 2>/dev/null)
IMAGE_TAG         := $(shell git rev-parse --short HEAD)

.PHONY: install up down migrate fixtures \
        test test-backend test-frontend terraform-test \
        dev worker lint \
        ecr-login build-aws push-aws secrets-aws deploy-aws migrate-aws

# --- Setup ---

install:
	cd backend && composer install
	cd frontend && npm install

up:
	docker compose up -d

down:
	docker compose down

migrate:
	cd backend && ./bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	cd backend && ./bin/console doctrine:fixtures:load --no-interaction

# --- Dev ---

dev:
	cd frontend && npm run dev

worker:
	cd backend && ./bin/console messenger:consume async -vv

# --- Tests ---

test: test-backend test-frontend terraform-test

test-backend:
	cd backend && ./vendor/bin/phpunit

test-frontend:
	cd frontend && npm run test:ci

terraform-test:
	@for mod in $(TERRAFORM_MODULES); do \
		echo "=== terraform/modules/$$mod ==="; \
		(cd terraform/modules/$$mod && terraform init -backend=false -input=false 1>/dev/null && terraform test) || exit 1; \
	done

# --- AWS Deploy ---

ecr-login:
	aws ecr get-login-password | \
		docker login --username AWS --password-stdin $(shell echo $(BACKEND_ECR) | cut -d/ -f1)

build-aws: ecr-login
	docker buildx build --platform linux/amd64 -t $(BACKEND_ECR):$(IMAGE_TAG) ./backend
	docker buildx build --platform linux/amd64 -t $(FRONTEND_ECR):$(IMAGE_TAG) ./frontend

push-aws: build-aws
	docker push $(BACKEND_ECR):$(IMAGE_TAG)
	docker push $(FRONTEND_ECR):$(IMAGE_TAG)

secrets-aws:
	@test -n "$(APP_SECRET)" || (echo "ERROR: export APP_SECRET first"; exit 1)
	@test -n "$(JWT_PASSPHRASE)" || (echo "ERROR: export JWT_PASSPHRASE first"; exit 1)
	@test -n "$(ADMIN_SECRET)" || (echo "ERROR: export ADMIN_SECRET first"; exit 1)
	./scripts/create-k8s-secrets.sh

deploy-aws:
	$(eval RDS    := $(shell cd $(TERRAFORM_DIR) && terraform output -raw rds_endpoint))
	$(eval REDIS  := $(shell cd $(TERRAFORM_DIR) && terraform output -raw redis_endpoint))
	sed -i '' \
		-e 's|REPLACE_WITH_BACKEND_ECR|$(BACKEND_ECR)|' \
		-e 's|REPLACE_WITH_FRONTEND_ECR|$(FRONTEND_ECR)|' \
		-e 's|REPLACE_WITH_RDS_ENDPOINT|$(RDS)|' \
		-e 's|REPLACE_WITH_ELASTICACHE_ENDPOINT|$(REDIS)|' \
		-e 's|newTag: .*|newTag: $(IMAGE_TAG)|' \
		k8s/overlays/aws/kustomization.yaml
	kubectl apply -k k8s/overlays/aws
	kubectl rollout restart deployment/backend deployment/messenger deployment/frontend
	kubectl rollout status deployment/backend
	kubectl rollout status deployment/frontend

migrate-aws:
	kubectl exec deploy/backend -- php bin/console doctrine:migrations:migrate --no-interaction

# --- Quality ---

lint:
	cd frontend && npm run lint
