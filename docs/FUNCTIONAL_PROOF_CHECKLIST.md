# Functional Proof Checklist (Rubric 4 + Test Evidence)

## Legacy Bugs Fixed (Quick Evidence)

1. **Order product visibility bug fixed**
   - Before: creating an order did not preserve/display selected products.
   - After: `orders` now stores ordered products in `ordered_products` and displays in:
     - `Orders > index`
     - `Orders > show`

2. **Admin text visibility/contrast bug fixed**
   - Before: IDs/status chips (`Active`, role badges, etc.) became unreadable due to global CSS overrides.
   - After: targeted visibility overrides in `templates/admin_base.html.twig` preserve badge utility colors while keeping readable content text.

3. **Sidebar visibility bug fixed**
   - Before: sidebar item labels/icons were faint or hidden.
   - After: explicit sidebar text/icon color rules enforce visible white labels in default/hover/active states.

4. **Admin dashboard data connectivity improved**
   - Before: sales chart used hardcoded sample values.
   - After: chart uses live last-7-day revenue aggregated from real orders.

## Rubric 6-8 Functional Evidence

### 6) Google OAuth (Staff)
- Login page includes `Continue with Google`.
- Routes:
  - `GET /connect/google`
  - `GET /connect/google/check`
- On successful OAuth:
  - user is created or updated as staff
  - account is auto-verified (`is_verified = 1`)
  - session persists and user is redirected to staff/admin area.

### 7) Email Verification (Web + API registration)
- Shared verification service is used by both web and API registration.
- Verification route:
  - `GET /verify/email/{token}`
- DB fields:
  - `is_verified` (bool)
  - `verification_token` (nullable)
- Web registration + API registration both:
  - create user as unverified
  - generate token
  - send verification email
  - require verification before normal password login.

### 8) Mobile API (3 standardized JSON endpoints)
- `GET /api/products`
- `GET /api/orders` (authenticated user/admin)
- `POST /api/register`
- Standardized JSON shape:
  - `success`
  - `message`
  - `data`
  - `meta` (count/timestamp where applicable)

## Test Config Fix

- Added `config/packages/test/framework.yaml` with:
  - `framework.test: true`
  - mock session storage

This addresses the previous functional test error:
`You cannot create the client used in functional tests if the "framework.test" config is not set to true.`

## Demo Script (2-3 minutes)

1. Login as admin -> open Dashboard (show live stats + chart).
2. Go to Products/Users/Orders pages -> verify IDs and status labels are readable.
3. Create an order and select products -> show products appear in order list/details.
4. Open Login page -> show Google button.
5. Register via web and via `/api/register` -> show verification required.
6. Open verification link -> login succeeds only after verification.
7. Call `/api/products` + `/api/orders` -> show standardized JSON output.

