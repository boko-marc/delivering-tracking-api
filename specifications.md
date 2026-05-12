# REQUIREMENTS SPECIFICATION

**Package Delivery Tracking API**

SaaS System for Managing and Tracking Package Deliveries



---

**Table of Contents**

1. Project Presentation
2. Multi-Tenant Architecture
3. Authentication and Security
4. Actor Management
5. Package Lifecycle
6. Exchange Security (Zero-Trust)
7. Payment Management
8. Parameters and Configuration
9. Webhooks and Notifications
10. Automations
11. Billing System
12. API Endpoints
13. Business Rules
14. Technical Constraints
15. Glossary

---

## 1. Project Presentation

**1.1 Context and Objectives**

**Package Delivery Tracking API** is a backend SaaS platform allowing e-commerce merchants and organizations to manage the complete lifecycle of their package deliveries, from creation to closure.

**The system meets the following needs:**

- Centralized management of packages and their real-time tracking
- Assignment and coordination of delivery drivers
- Securing physical handovers via code validation system
- Complete traceability of payments related to packages
- Billing adapted to each organization's needs
- Easy integration via a documented REST API

**1.2 Functional Scope**

| **✅ In Scope** | **❌ Out of Scope** |
|----------------|-------------------|
| Multi-tenant management (organizations and workspaces) | User interface / Frontend dashboard |
| Complete CRUD of packages and drivers | Payment gateway (real money collection) |
| Package state machine | Real-time GPS / geolocation |
| Double validation at each key step | Mobile driver application |
| Declarative payment tracking | Certified accounting module |
| Webhooks and notifications | Dispute and refund management |
| Usage + subscription billing | |
| API Keys (Organization, Workspace, Driver) | |

**1.3 Target Users**

| **Actor** | **Profile** | **System Interaction** |
|-----------|------------|----------------------|
| **E-commerce Merchant** | Workspace owner | Creates packages, assigns drivers, tracks deliveries |
| **Driver** | Independent or company | Accepts, picks up and delivers packages |
| **End Customer** | Package recipient | Receives validation codes and confirms receipt |
| **Administrator** | Technical manager | Manages organizations, plans, quotas |

---

## 2. Multi-Tenant Architecture

**2.1 Entity Hierarchy**

| **Level** | **Entity** | **Role** |
|-----------|-----------|----------|
| **Level 1** | **Organization** | Main legal entity. Defines global payment rules and expiration times. |
| **Level 2** | **Workspace** | Operational unit (agency, city). All resources are attached to it. |
| **Level 3** | **Resources** | Packages, drivers, API Keys, webhooks. Strictly isolated per workspace. |

**2.2 API Keys — Types and Scopes**

The system relies on a unique authentication principle: **API Keys** via the `X-API-Key` header.

| Key Type | Format | Scope | Usage |
|----------|--------|--------|-------|
| **Organization Secret Key** | `org_secret_live_xxx` or `org_secret_test_xxx` | Entire organization | Workspace management, global reports, parameters |
| **Workspace Secret Key** | `ws_secret_live_xxx` or `ws_secret_test_xxx` | Single workspace | Package CRUD, drivers, webhooks |
| **Workspace Public Key** | `ws_public_live_xxx` or `ws_public_test_xxx` | Single workspace (read-only) | Public tracking, package statuses |
| **Driver Key** | `driver_live_xxx` | Single driver | Field actions (accept, pickup, delivery) |

**Authentication header:**
```
X-API-Key: ws_secret_live_a7f3e9d2c1b8a4f6...
```

**Optional header (with organization key):**
```
X-Workspace-Key: ws_public_live_yyy
```

**2.3 Separate Environments**

Two completely independent instances (same code, separate deployment):

| Environment | API URL | Database | Key Types |
|-------------|---------|----------|-----------|
| **Sandbox** | `sandbox.api.packagedelivery.com` | `package_delivery_sandbox` | `_test_` |
| **Live** | `api.packagedelivery.com` | `package_delivery_production` | `_live_` |

- Independent user accounts
- No data sharing between environments
- User must register separately on each environment

**2.4 Data Isolation**

**Isolation rule:** No resource (package, driver, API Key, webhook) can be accessed from a workspace other than the one it belongs to. All queries are automatically filtered by workspace_id.

---

## 3. Authentication and Security

**3.1 Registration Flow**

**Step 1: Email Verification**

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/auth/check-email` | Check available email + generate token + send email (can be called multiple times, previous token is invalidated) |

**Step 2: Account Creation**

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/auth/register` | Create user + organization + workspace + all keys |

**What is created in a single transaction:**
- User (email, password hash, first name, last name)
- Organization (name, default currency, timezone)
- Default Workspace (name = "Default Workspace")
- Organization Secret Key
- Workspace Secret Key
- Workspace Public Key

**3.2 Key Rotation**

Keys can be regenerated at any time by a user with organization key. The old key is immediately revoked. The raw new key is displayed only once during generation and is never stored in plain text (SHA-256).

---

## 4. Actor Management

**4.1 E-commerce Merchants / Organizations**

- Account creation with email and password via email validation
- A workspace is automatically created upon registration
- Possibility to create multiple workspaces (according to plan)
- Organization parameter configuration (expiration times, collection methods)
- Access to reports and statistics via API keys

**4.2 Drivers**

**Driver Types**

| **Type** | **Characteristics** |
|----------|---------------------|
| **Independent Driver** | Natural person. Has an email, phone number and their own schedules. |
| **Delivery Company** | Legal entity. May have multiple agents. |

**Work Hours (availability)**

Each driver defines their availability slots:
- Available days (Monday → Sunday)
- Time slots per day (e.g.: 08h00 - 18h00)
- No automatic assignment outside work hours

**Driver Data**

| **Field** | **Description** |
|-----------|-----------------|
| **Name / Company Name** | Full name for individual, company name for company |
| **Email** | Unique identifier in workspace. Receives notifications |
| **Phone** | Direct contact. Optional |
| **Type** | `individual` or `company` |
| **Work Hours** | Availability days and time slots |
| **Status** | `active` / `inactive`. Inactive driver cannot receive new packages |

**Driver Key**

Upon driver creation, a **Driver Key** is automatically generated. It serves to authenticate the driver for all their field actions.

**4.3 End Customers**

The end customer is the package recipient. They do not have an account in the system but receive:

- An informational email when the package is ready (READY): "Your package is ready to be delivered"
- An email when the driver accepts the mission: "Your package will be delivered by [driver name]"
- A **Client Delivery Code** when the package is picked up (PICKED_UP): to keep for receipt
- A tracking link to view their package status
- A notification when the driver starts delivery

---

## 5. Package Lifecycle

**5.1 Overall Status View**

| **Status Code** | **Label** | **Description** | **Actor** |
|-----------------|-----------|-----------------|-----------|
| `DRAFT` | Draft | Package created, not yet submitted. Freely modifiable. | E-commerce Merchant |
| `READY` | Ready | Package ready to be assigned. Informational email sent to customer. | E-commerce Merchant |
| `ASSIGNED` | Assigned | Driver designated. Waiting for acceptance. | E-commerce Merchant |
| `ACCEPTED` | Accepted | Driver accepted. Pickup Codes generated. | Driver |
| `PICKUP_IN_PROGRESS` | Pickup in Progress | Driver validated their pickup. Waiting for merchant confirmation. | Driver |
| `PICKED_UP` | Picked Up | Double pickup validation complete. Delivery codes generated. | System |
| `DELIVERY_STARTED` | Delivery Started | Driver used their Delivery Start Code. Customer notified. | Driver |
| `DELIVERED` | Delivered | Customer confirmed receipt with their code. | Customer |
| `REJECTED_BY_DRIVER` | Rejected by Driver | Driver refused the mission. | Driver |
| `REJECTED_BY_CLIENT` | Rejected by Client | Customer refused the package. Return codes generated. | Customer |
| `RETURN_IN_PROGRESS` | Return in Progress | Driver validated their return. Waiting for merchant confirmation. | Driver |
| `RETURNED` | Returned | Merchant confirmed the return. | Merchant |
| `ARCHIVED` | Archived | Package hidden but restorable. | E-commerce Merchant |
| `CLOSED` | Closed | Package definitively completed. Non-modifiable. | System / E-commerce Merchant |

**5.2 Detailed Phase Description**

**Phase A — Preparation (DRAFT → READY)**

The e-commerce merchant creates the package with all necessary information. A unique `tracking_code` is automatically generated. Once the package is validated (READY status):
- An informational email is sent to the customer: "Your package is ready to be delivered"
- No delivery code is sent at this stage
- The package is modifiable until assignment

**Rule:** A package in READY that is modified automatically returns to DRAFT.

**Phase B — Assignment (READY → ASSIGNED → ACCEPTED)**

- The e-commerce merchant assigns an available driver
- The `assignment_timeout_minutes` and `pickup_timeout_hours` parameters can be defined at package level (otherwise default organization values)
- The driver is notified by email with mission details
- The driver has a configurable time (`assignment_timeout_minutes`) to accept or refuse
- Upon acceptance:
  - **Pickup Code (driver)** generated and sent to driver
  - **Pickup Code (merchant)** generated and sent to merchant
  - Email to customer: "Your package will be delivered by [driver name]"

**Phase C — Double Pickup Validation (ACCEPTED → PICKUP_IN_PROGRESS → PICKED_UP)**

- Driver has `pickup_timeout_hours` to perform pickup (time counted after acceptance)
- If timeout exceeded: mission cancelled, return to READY, notification to merchant
- Driver validates pickup with their Pickup Code → status `PICKUP_IN_PROGRESS`
- Merchant is notified and has a configurable time (`pickup_confirmation_timeout_hours`) to confirm
- Reminders are sent to merchant according to configurable schedule
- If merchant does not confirm within time: return to `ACCEPTED`
- Merchant can then manually regenerate pickup codes via dedicated endpoint
- If merchant confirms: status `PICKED_UP`
- **Delivery Start Code** and **Client Delivery Code** are generated and sent (they expire at the same time)
- Email to customer: "Your package is on the way! Here is your delivery code"

**Phase D — Delivery (PICKED_UP → DELIVERY_STARTED → DELIVERED)**

- Driver arrives at customer's location and uses their **Delivery Start Code** → status `DELIVERY_STARTED`
- Customer is notified: "The driver has started delivery. Prepare your code."
- If COD payment: payment management according to `collection_method`
- Customer provides their **Client Delivery Code** → system verifies and changes to `DELIVERED`
- Notification to merchant: delivery confirmation

**Phase E — Client Rejection (DELIVERY_STARTED → REJECTED_BY_CLIENT)**

- Customer can refuse the package with mandatory reason (only after DELIVERY_STARTED)
- **Return Codes** (driver + merchant) are generated
- Driver and merchant are notified

**Phase F — Package Return (REJECTED_BY_CLIENT → RETURN_IN_PROGRESS → RETURNED)**

- Driver validates their return with their Return Code → status `RETURN_IN_PROGRESS`
- Merchant validates the return with their Return Code → status `RETURNED`
- Notification to merchant: "The package has been returned"
- A RETURNED package can be modified and return to READY for a new cycle

**Phase G — Archiving (ARCHIVED)**

- The e-commerce merchant can archive a package at any status except DELIVERED and CLOSED
- Package changes to `ARCHIVED` and no longer appears in active lists
- An archived package can be restored → return to `DRAFT`

**Phase H — Closure (DELIVERED → CLOSED)**

- A DELIVERED package can be closed (manually or automatically after `auto_close_delay_hours`)
- Closure is definitive: a CLOSED package cannot be modified
- Only DELIVERED packages can be closed

---

## 6. Exchange Security (Zero-Trust)

Each critical physical step is protected by a unique validation code. No physical exchange can be validated in the system without all concerned parties having provided their respective codes.

| **Code** | **Generated When** | **Sent To** | **Validated By** | **Expiration** |
|----------|-------------------|-------------|------------------|----------------|
| **Pickup Code (driver)** | Driver accepts | Driver | Driver (for pickup) | `pickup_timeout_hours` |
| **Pickup Code (merchant)** | Driver accepts | Merchant | Merchant (for confirmation) | `pickup_confirmation_timeout_hours` |
| **Delivery Start Code** | Double pickup validation | Driver | Driver (start delivery) | Configurable |
| **Client Delivery Code** | Double pickup validation | Customer | Customer (confirm receipt) | Configurable |
| **Return Code (driver)** | Customer rejects | Driver | Driver (return) | Configurable |
| **Return Code (merchant)** | Customer rejects | Merchant | Merchant (return confirmation) | Configurable |

**Security principle:**
- Codes generated randomly (cryptographically secure)
- Stored only as SHA-256 hash (never retrievable)
- Sent only once to recipient
- Single-use and expiring

**Code Regeneration:**

| Code Type | Trigger | Endpoint | Authentication |
|-----------|---------|----------|----------------|
| Pickup Codes (driver + merchant) | Merchant (manual) | `POST /packages/{id}/regenerate-pickup-codes` | 🔐 |
| Client Delivery Code | Customer (via tracking link) | `POST /client/packages/{code}/renew-code` | 🌍 |
| Delivery Start Code | Driver (via application) | `POST /driver/packages/{id}/renew-delivery-code` | 🚚 |

**Note:** Delivery Start Code and Client Delivery Code are generated **at the same time** (after double pickup validation) and expire **at the same time**.

---

## 7. Payment Management

The payment system is declarative. It records and traces amounts declared by stakeholders but does not manage real financial flows.

**7.1 Amount Structure**

| **Amount** | **Field** | **Description** |
|------------|-----------|-----------------|
| **Product Price** | `order_amount` | Package content value |
| **Delivery Fee** | `delivery_fee` | Transport service cost |

**7.2 Payment Modes**

| **Mode** | **Behavior** | **Driver Role** |
|----------|--------------|-----------------|
| **PREPAID** | Payment made outside the system | No collection |
| **CASH ON DELIVERY** | Payment collected during delivery | Mandatory collection |

**7.3 COD Collection Methods**

| **Method** | **Value** | **Functioning** |
|------------|-----------|-----------------|
| **Manual** | `manual` | Driver receives cash. Declares collected amount |
| **Payment Link** | `link` | Mobile payment link generated. Driver validates after confirmation |
| **Both** | `both` | Driver chooses method at delivery time |

**⚠️ Important:** The authorized collection method is configurable per organization via the `collection_method` parameter.

**7.4 Payment Traceability**

For each package, the system records:
- Amount declared by merchant (order_amount, delivery_fee)
- Amount collected declared by driver during delivery
- Collector's identity (driver)
- Collection date and time
- Collection method used
- `payment_confirmed` flag on package (true/false)

---

## 8. Parameters and Configuration

Each organization configures their parameters. These parameters influence system behavior for all packages in the organization.

**8.1 Expiration Times**

| **Parameter** | **Description** | **Default** | **Can be overridden per package** |
|----------------|-----------------|-------------|-----------------------------------|
| `assignment_timeout_minutes` | Time to accept/refuse | 30 min | ✅ Yes |
| `pickup_timeout_hours` | Time to do pickup (counted after acceptance) | 2 hours | ✅ Yes |
| `pickup_confirmation_timeout_hours` | Time to confirm pickup | 2 hours | ❌ No |
| `delivery_start_code_expiration_hours` | Validity of code to start delivery | 48 hours | ❌ No |
| `client_code_expiration_hours` | Customer code validity | 48 hours | ❌ No |
| `auto_close_delay_hours` | Delay before automatic closure (DELIVERED only) | 48 hours | ❌ No |

**8.2 Pickup Confirmation Reminders**

| **Parameter** | **Description** | **Default** |
|----------------|-----------------|-------------|
| `pickup_confirmation_reminder_ratios` | Reminder moments (fractions of total time) | `[0.5, 0.75, 1]` |

**Example with 2-hour delay:**
- T+0: Driver pickup → email to merchant
- T+1h (50%): 1st reminder
- T+1h30 (75%): 2nd reminder
- T+2h (end): Last reminder + return to ACCEPTED

**8.3 Collection Methods**

| **Parameter** | **Description** | **Default** | **Possible Values** |
|----------------|-----------------|-------------|---------------------|
| `collection_method` | How driver collects money | `manual` | `manual`, `link`, `both` |
| `default_currency` | Default currency | `XOF` | ISO 4217 |

---

## 9. Webhooks and Notifications

**9.1 Webhook Events**

| **Event** | **Triggered When** |
|-----------|---------------------|
| `package.created` | A new package is created (DRAFT) |
| `package.ready` | Package changes to READY |
| `package.assigned` | A driver is assigned |
| `package.accepted` | Driver accepts the mission |
| `package.rejected_by_driver` | Driver refuses the mission |
| `package.picked_up` | Pickup is validated (PICKED_UP) |
| `package.delivery_started` | Driver starts delivery |
| `package.delivered` | Delivery is confirmed by customer |
| `package.payment_confirmed` | COD payments are confirmed |
| `package.rejected_by_client` | Customer refuses package |
| `package.returned` | Package return is validated |
| `package.archived` | Package is archived |
| `package.closed` | Package is closed |

**9.2 Webhook Security**

- Each webhook delivery is signed with HMAC-SHA256 (secret key per workspace)
- `X-Delivery-Signature` header included in each request
- In case of failure (timeout, server error), delivery is retried
- In sandbox mode, webhooks are **not dispatched**

**9.3 Email Notifications**

| **Event** | **Recipient** | **Content** |
|-----------|---------------|-------------|
| **READY Package** | End Customer | Informational email: "Your package is ready to be delivered" |
| **Mission Assigned** | Driver | Mission details + acceptance deadline |
| **Mission Accepted** | End Customer | "Your package will be delivered by [driver name]" |
| **Pickup Validated** | End Customer | Customer delivery code + tracking link |
| **Delivery Started** | End Customer | "The driver has started delivery" |
| **Package Delivered** | E-commerce Merchant | Delivery confirmation |
| **Package Rejected** | E-commerce Merchant + Driver | Customer rejection reason + return codes |
| **Mission Cancelled (timeout)** | E-commerce Merchant + Driver | Cancellation for inactivity |
| **Pickup Confirmation Reminder** | E-commerce Merchant | Reminders at defined intervals |

---

## 10. Automations

**10.1 Auto-Requeue (Automatic Reassignment)**

If driver does not accept before configured `assignment_timeout_minutes` (global or specific to package):
- Package automatically returns to READY
- Driver notified (mission removed from them)
- E-commerce merchant notified (can proceed to new assignment)
- `submitted_count` counter incremented

**10.2 Pickup Timeout**

If driver has not performed pickup before configured `pickup_timeout_hours` (global or specific to package):
- Package automatically returns to READY
- Driver notified (mission cancelled for inactivity)
- E-commerce merchant notified

**10.3 Pickup Confirmation Timeout**

If merchant does not confirm pickup before `pickup_confirmation_timeout_hours`:
- Reminders sent according to `pickup_confirmation_reminder_ratios`
- At timeout end: package returns to ACCEPTED
- Merchant must manually regenerate pickup codes via dedicated endpoint

**10.4 Auto-Close (Automatic Closure)**

After a package passes to DELIVERED status:
- If `auto_close_delay_hours` configured → automatic change to CLOSED
- E-commerce merchant can close manually before this delay
- A RETURNED package is not automatically closed (can be reused)

**10.5 Automatic Code Generation**

The following codes are automatically generated by the system:
- `tracking_code`: upon package creation
- **Pickup Code (driver)**: upon driver acceptance
- **Pickup Code (merchant)**: upon driver acceptance
- **Delivery Start Code**: after double pickup validation
- **Client Delivery Code**: after double pickup validation
- **Return Codes (x2)**: upon customer rejection

**10.6 Manual Code Regeneration**

| Code Type | Trigger | Endpoint |
|-----------|---------|----------|
| Pickup Codes | Merchant (after return to ACCEPTED) | `POST /packages/{id}/regenerate-pickup-codes` |
| Client Delivery Code | Customer (via tracking link) | `POST /client/packages/{code}/renew-code` |
| Delivery Start Code | Driver (via application) | `POST /driver/packages/{id}/renew-delivery-code` |

---

## 11. Billing System

The platform billing relies on a hybrid model combining monthly subscription and usage consumption.

**11.1 Pricing Structure**

| **Plan** | **Monthly Quota** | **Workspaces** | **Support** |
|----------|-------------------|----------------|-------------|
| **Free** | 50 packages / month | 1 workspace | Email only |
| **Starter** | 500 packages / month | 3 workspaces | Email + Chat |
| **Pro** | 2,000 packages / month | 10 workspaces | Priority |
| **Enterprise** | Unlimited | Unlimited | SLA dedicated |

**11.2 Consumption Rules**

- Each package created (READY status) consumes 1 unit of monthly quota
- DRAFT packages do not consume quota
- If monthly quota exceeded, units are drawn from additional packs
- Unused units are carried over to the next month

**11.3 Additional Pack Model**

| **Pack** | **Included Units** | **Validity** |
|----------|--------------------|--------------|
| **Pack 100** | 100 units | 12 months |
| **Pack 500** | 500 units | 12 months |
| **Pack 2000** | 2,000 units | 12 months |

---

## 12. API Endpoints

All endpoints are prefixed with `/api/v1/`. Authentication is done via the `X-API-Key` header.

**12.1 Legend**

| Symbol | Key Type |
|--------|----------|
| 🔑 | Organization Secret Key |
| 🔐 | Workspace Secret Key |
| 👁️ | Workspace Public Key |
| 🚚 | Driver Key |
| 🌍 | None (public) |

**12.2 Authentication**

| Method | Endpoint | Key | Description |
|--------|----------|-----|-------------|
| `POST` | `/auth/check-email` | 🌍 | Check email + generate token + send email |
| `POST` | `/auth/register` | 🌍 | Create account + organization + keys |

**12.3 Organization**

| Method | Endpoint | Key | Description |
|--------|----------|-----|-------------|
| `GET` | `/organisations/me` | 🔑 | Organization info |
| `GET` | `/organisations/workspaces` | 🔑 | List all workspaces |
| `POST` | `/organisations/workspaces` | 🔑 | Create workspace |
| `GET` | `/organisations/settings` | 🔑 | Retrieve parameters |
| `PATCH` | `/organisations/settings` | 🔑 | Modify parameters |
| `GET` | `/organisations/stats` | 🔑 | Global statistics |
| `GET` | `/organisations/usage` | 🔑 | Consumption |
| `GET` | `/organisations/reports/consolidated` | 🔑 | Consolidated report |

**12.4 Workspace**

| Method | Endpoint | Key | Description |
|--------|----------|-----|-------------|
| `GET` | `/workspaces/me` | 🔐 | Workspace info |
| `GET` | `/workspaces/stats` | 🔐 | Workspace statistics |
| `GET` | `/workspaces/api-keys` | 🔐 | List keys |
| `POST` | `/workspaces/api-keys` | 🔐 | Generate new key |
| `DELETE` | `/workspaces/api-keys/{id}` | 🔐 | Revoke key |
| `GET` | `/workspaces/reports/packages` | 🔐 | Export CSV packages |
| `GET` | `/workspaces/reports/drivers` | 🔐 | Driver report |
| `GET` | `/workspaces/reports/revenue` | 🔐 | Revenue report |

**12.5 Drivers**

| Method | Endpoint | Key | Description |
|--------|----------|-----|-------------|
| `GET` | `/drivers` | 🔐 | List drivers |
| `POST` | `/drivers` | 🔐 | Create driver (generates Driver Key) |
| `GET` | `/drivers/{id}` | 🔐 | View driver |
| `PUT` | `/drivers/{id}` | 🔐 | Modify driver |
| `DELETE` | `/drivers/{id}` | 🔐 | Delete |
| `PATCH` | `/drivers/{id}/work-hours` | 🔐 | Modify availability |
| `POST` | `/drivers/{id}/revoke-key` | 🔐 | Revoke Driver Key |

**12.6 Packages**

| Method | Endpoint | Key | Description |
|--------|----------|-----|-------------|
| `POST` | `/packages` | 🔐 | Create (DRAFT) |
| `GET` | `/packages` | 🔐 | List |
| `GET` | `/packages/{id}` | 🔐 | View |
| `PUT` | `/packages/{id}` | 🔐 | Modify (if DRAFT or READY) |
| `DELETE` | `/packages/{id}` | 🔐 | Delete (if DRAFT) |
| `PATCH` | `/packages/{id}/submit` | 🔐 | DRAFT → READY |
| `PATCH` | `/packages/{id}/assign` | 🔐 | READY → ASSIGNED (with optional timeouts) |
| `PATCH` | `/packages/{id}/requeue` | 🔐 | ASSIGNED → READY |
| `POST` | `/packages/{id}/regenerate-pickup-codes` | 🔐 | Regenerate pickup codes (ACCEPTED status) |
| `POST` | `/packages/{id}/confirm-pickup` | 🔐 | PICKUP_IN_PROGRESS → PICKED_UP |
| `POST` | `/packages/{id}/archive` | 🔐 | Change to ARCHIVED |
| `POST` | `/packages/{id}/unarchive` | 🔐 | Restore ARCHIVED → DRAFT |
| `POST` | `/packages/{id}/close` | 🔐 | Close (DELIVERED only) |
| `GET` | `/packages/tracking/{code}` | 👁️ or 🌍 | Customer tracking |

**12.7 Driver Actions**

| Method | Endpoint | Key | Description |
|--------|----------|-----|-------------|
| `GET` | `/driver/assignments` | 🚚 | View their missions |
| `POST` | `/driver/assignments/{id}/accept` | 🚚 | Accept mission |
| `POST` | `/driver/assignments/{id}/reject` | 🚚 | Refuse mission |
| `POST` | `/driver/packages/{id}/pickup` | 🚚 | Validate pickup |
| `POST` | `/driver/packages/{id}/start-delivery` | 🚚 | Start delivery |
| `POST` | `/driver/packages/{id}/declare-payment` | 🚚 | Declare COD payment |
| `POST` | `/driver/packages/{id}/confirm-delivery` | 🚚 | Confirm delivery (with customer code) |
| `POST` | `/driver/packages/{id}/return` | 🚚 | Return package |
| `POST` | `/driver/packages/{id}/renew-delivery-code` | 🚚 | Renew Delivery Start Code |

**12.8 Customer Actions**

| Method | Endpoint | Key | Description |
|--------|----------|-----|-------------|
| `POST` | `/client/packages/{code}/reject` | 🌍 | DELIVERY_STARTED → REJECTED_BY_CLIENT |
| `POST` | `/client/packages/{code}/renew-code` | 🌍 | Renew Client Delivery Code |

**12.9 Webhooks**

| Method | Endpoint | Key | Description |
|--------|----------|-----|-------------|
| `GET` | `/workspace/webhooks` | 🔐 | List |
| `POST` | `/workspace/webhooks` | 🔐 | Create |
| `DELETE` | `/workspace/webhooks/{id}` | 🔐 | Delete |

---

## 13. Business Rules

**13.1 Package Rules**

1. A package is modifiable only if its status is DRAFT or READY.
2. If a READY package is modified, it automatically returns to DRAFT.
3. A package can be deleted only in DRAFT status.
4. A DELIVERED package cannot be modified.
5. A DELIVERED package cannot be archived (only closed).
6. A CLOSED package cannot be modified, restored or reopened.
7. An ARCHIVED package can be restored → return to DRAFT.
8. A RETURNED package can be modified and return to READY.
9. A REJECTED_BY_CLIENT package can be modified and return to READY.
10. A package in sandbox mode does not generate emails or webhooks.
11. The `submitted_count` counter is incremented at each return to READY (rejection, timeout, etc.)

**13.2 Driver Rules**

12. An inactive driver cannot be assigned to a new package.
13. Driver assignment outside work hours is possible only with override.
14. A driver cannot have a package in ACCEPTED or PICKED_UP status at the same time.
15. The driver only collects amounts in COD mode.

**13.3 Validation Rules**

16. Pickup requires validation of both codes: Pickup Code (driver) AND Pickup Code (merchant).
17. Final delivery requires the Client Delivery Code provided by the customer.
18. Codes are single-use. Once used, they are invalidated.
19. Codes are stored only as SHA-256 hash.
20. Customer rejection is possible only after DELIVERY_STARTED.
21. Delivery Start Code and Client Delivery Code are generated at the same time and expire at the same time.

**13.4 Closure and Archiving Rules**

22. Only DELIVERED packages can be closed (manually or automatically).
23. Automatic closure applies only to DELIVERED packages (after `auto_close_delay_hours`).
24. A CLOSED package cannot be reopened.
25. An ARCHIVED package can be unarchived → return to DRAFT.
26. A DELIVERED package cannot be archived.

**13.5 Isolation Rules**

27. All data (package, driver, webhook) belongs to a workspace and can be accessed only by that workspace.
28. Sandbox and live data are strictly separated.

**13.6 Logs and Audit**

29. All important actions are logged via Spatie Laravel Activity Log
30. Logs include: creation, modification, deletion, status changes, code validations, assignment, acceptance, pickup, delivery, rejection, return, closure, archiving

---

## 14. Technical Constraints

**14.1 Technology Stack**

| **Component** | **Chosen Technology** |
|----------------|-----------------------|
| **Backend Framework** | Laravel 12 |
| **Database** | PostgreSQL 16 |
| **Cache and Queues** | Redis 7 |
| **Application Logs** | Spatie Laravel Activity Log |
| **Dev Emails** | Mailpit |
| **Dev Monitoring** | Laravel Telescope |
| **Prod Monitoring** | Grafana Cloud (Prometheus, Loki, Tempo) |
| **Tests** | Pest PHP |
| **API Documentation** | Scribe |
| **PHP Linting** | Laravel Pint (PSR-12) |
| **Static Analysis** | PHPStan level 9 + Larastan |
| **Refactoring** | Rector |
| **CI/CD** | GitHub Actions |
| **Containerization** | Docker + docker-compose |

**14.2 Performance Requirements**

- API response time: < 300 ms for 95% of production requests
- Target availability: 99.5% (excluding scheduled maintenance)
- Rate limiting: configurable per plan
- Job queues: email and webhook processing in less than 30 seconds

**14.3 Security Requirements**

- All communications must be encrypted via HTTPS (TLS 1.2 minimum)
- API keys are stored only as SHA-256 hash
- Validation codes are generated with cryptographically secure generator
- Audit logs (Temporal Tables) are immutable
- Sensitive data is never logged

**14.4 Code Quality and Linters**

| **Tool** | **Role** | **Level** |
|----------|----------|-----------|
| Laravel Pint | Code style (PSR-12) | Strict (CI blocks) |
| PHPStan | Static analysis | Level 9 (max) |
| Rector | Auto refactoring | Laravel 12 set + Dead Code |
| Larastan | Laravel rules | Integrated in PHPStan |

**Composer Scripts:**

| **Command** | **Action** |
|-------------|------------|
| `composer lint` | Check code style |
| `composer analyse` | Run PHPStan level max |
| `composer refactor:dry` | Simulate Rector refactorings |
| `composer refactor` | Apply Rector refactorings |
| `composer quality` | Lint + Analyse + Tests |

**14.5 Deployment (Free)**

**Production on Render.com:**

| **Service** | **Configuration** |
|-------------|-------------------|
| Web service | Laravel 12, GitHub auto-deploy |
| PostgreSQL | Render Managed DB (free tier, 1 GB) |
| Redis | Render Managed Redis (free tier, 25 MB) |
| Environment variables | Configured in Render dashboard |

**Observability on Grafana Cloud:**

| **Step** | **Action** |
|----------|------------|
| 1 | Create account on grafana.com |
| 2 | Enable Prometheus, Loki, Tempo |
| 3 | Retrieve OTLP endpoint and token |
| 4 | Add OTel variables on Render |
| 5 | Visualize on `YOUR_TENANT.grafana.net` |

**Observability environment variables:**

| **Variable** | **Role** |
|--------------|----------|
| `OTEL_EXPORTER_OTLP_ENDPOINT` | Grafana Cloud OTLP endpoint |
| `OTEL_EXPORTER_OTLP_HEADERS` | Authentication token |
| `OTEL_SERVICE_NAME` | `package-delivery-api` |
| `OTEL_TRACES_SAMPLER` | `always_on` or `parentbased_always_on` |

---

## 15. Glossary

| **Term** | **Definition** |
|----------|----------------|
| **API Key** | Authentication key allowing identification of an organization, workspace or driver |
| **ARCHIVED** | Package status hidden but restorable |
| **CLOSED** | Package status definitively completed (non-modifiable) |
| **Client Delivery Code** | 6-digit code sent to customer, required to confirm receipt |
| **COD (Cash on Delivery)** | Payment mode where amount is collected physically during delivery |
| **Delivery Start Code** | 6-digit code given to driver, required to start delivery |
| **Driver Key** | API key specific to driver for their field actions |
| **HMAC-SHA256** | Signature algorithm used to secure webhook payloads |
| **Organization** | Main legal entity (company) |
| **Pickup Code** | Code generated upon acceptance, validated by driver and merchant to confirm pickup |
| **PREPAID** | Payment mode where amount was paid before delivery |
| **Return Code** | Code generated upon customer rejection, validated by driver and merchant to confirm return |
| **Sandbox** | Isolated test environment where actions have no real impact |
| **Spatie Laravel Activity Log** | Package for system action audit and logging |
| **tracking_code** | Unique and public package identifier |
| **Webhook** | HTTP notification sent to external URL upon system event |
| **Workspace** | Operational unit attached to an organization |
| **Work Hours** | Availability slots defined by a driver |
| **Zero-Trust** | Security principle requiring explicit validation at each step |

---
