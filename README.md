Delivery Tracking API — A secure multi-tenant logistics backend with payment handling and delivery verification.
# Package Delivery Tracking API

A SaaS backend API for e-commerce merchants and organizations to manage the complete lifecycle of their parcel deliveries, from creation to closure.

## Features

- **Multi-tenant Architecture** — Organizations and workspaces with strict data isolation
- **API Key Authentication** — Organisation Secret Keys, Workspace Secret Keys, Workspace Public Keys, and Driver Keys
- **Package State Machine** — Complete lifecycle tracking (DRAFT → READY → ASSIGNED → ACCEPTED → PICKUP → DELIVERY → DELIVERED → CLOSED)
- **Zero-Trust Security** — Double validation codes for every critical physical exchange (pickup, delivery, returns)
- **COD Payment Support** — Configurable collection methods (manual, payment link, or both)
- **Webhooks** — Real-time notifications with HMAC-SHA256 signatures
- **Driver Management** — Work hours, availability, and concurrent package limits
- **Billing System** — Usage-based quotas with subscription plans and extra packs
- **Audit Logs** — Complete action history via Spatie Laravel Activity Log

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel 12 |
| Database | PostgreSQL 16 |
| Cache & Queues | Redis 7 |
| Logging | Spatie Laravel Activity Log |
| Monitoring | Grafana Cloud (Prometheus + Loki + Tempo) |
| Testing | Pest PHP |
| API Documentation | Scribe |
| Code Quality | Laravel Pint, PHPStan (level 9), Rector |
| Containerization | Docker + Docker Compose |

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Make (optional)

### Installation

```bash
# Clone the repository
git clone https://github.com/boko-marc/delivering-tracking-api
cd package-delivery-api

# Copy environment file
cp .env.example .env

# Start containers
docker-compose up -d

# Run migrations
docker exec app php artisan migrate

# Run seeders (optional)
docker exec app php artisan db:seed
