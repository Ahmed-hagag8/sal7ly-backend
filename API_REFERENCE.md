# Sal7ly API Reference

**Base URL:** `http://localhost:8000/api`            **will change later**

**Auth Header:** `Authorization: Bearer {token}`

---

## 🔓 Public (No Auth Required)

### Auth
| Method | Endpoint | Body |
|--------|----------|------|
| POST | `/login` | `{ phone, password }` |
| POST | `/register/customer` | `{ name, phone, email, password, password_confirmation, city_id }` |
| POST | `/register/technician` | `{ name, phone, email, password, password_confirmation, city_id, category_id, national_id }` |

**Login Response:**
```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "name": "Ahmed", "phone": "01000000000", "email": "a@a.com", "role": "admin" },
    "token": "1|abc..."
  }
}
```

### Catalog
| Method | Endpoint | Params |
|--------|----------|--------|
| GET | `/categories` | — |
| GET | `/categories/{id}` | — |
| GET | `/categories/{id}/services` | — |
| GET | `/services` | — |
| GET | `/health` | — |

---

## 👤 Shared (Any Authenticated User)

| Method | Endpoint | Body / Params |
|--------|----------|---------------|
| POST | `/logout` | — |
| GET | `/me` | — |
| GET | `/profile` | — |
| PUT | `/profile` | `{ name, email, phone }` |
| POST | `/profile/image` | `image` (file, multipart/form-data) |
## new
| POST | `/profile/credentials` | `{ email, password, password_confirmation }` |
##
| GET | `/wallet` | — |
| GET | `/wallet/transactions` | — |
| GET | `/conversations` | — |
| GET | `/conversations/{id}/messages` | — |
| POST | `/conversations/{id}/messages` | `{ body }` |
| GET | `/notifications` | — |
| POST | `/notifications/{id}/read` | — |
| POST | `/notifications/read-all` | — |
| POST | `/ai/predict-price` | `{ service_id, description }` |
| POST | `/ai/detect-image` | `image` (file) |
| POST | `/ai/chat` | `{ message }` |

---

## 🧑‍💼 Customer Routes (`/customer/...`)

| Method | Endpoint | Body / Params |
|--------|----------|---------------|
| POST | `/customer/requests` | `{ service_id, city_id, title, description, address, latitude?, longitude?, preferred_date, preferred_time, images[]? }` |
| GET | `/customer/requests` | `?status=pending\|open\|assigned\|cancelled` |
| GET | `/customer/requests/{id}` | — |
| POST | `/customer/requests/{id}/cancel` | — |
| GET | `/customer/requests/{id}/offers` | — |
| POST | `/customer/requests/{requestId}/offers/{offerId}/accept` | — |
| GET | `/customer/jobs` | — |
| POST | `/customer/jobs/{id}/pay` | `{ payment_method }` |
| POST | `/customer/jobs/{id}/review` | `{ rating (1-5), comment? }` |

---

## 🔧 Technician Routes (`/technician/...`)

| Method | Endpoint | Body / Params |
|--------|----------|---------------|
| GET | `/technician/documents` | — |
| POST | `/technician/documents` | `{ type, document }` (file) |
| DELETE | `/technician/documents/{id}` | — |
| GET | `/technician/requests` | — |
| GET | `/technician/requests/{id}` | — |
| GET | `/technician/offers` | — |
| POST | `/technician/requests/{id}/offer` | `{ offered_price, estimated_duration?, notes? }` |
| DELETE | `/technician/offers/{id}` | — |
| GET | `/technician/jobs` | — |
| POST | `/technician/jobs/{id}/start` | — |
| POST | `/technician/jobs/{id}/complete` | — |
| GET | `/technician/withdrawals` | — |
| POST | `/technician/withdrawals` | `{ amount, method }` |

---

## 🛡️ Admin Routes (`/admin/...`)

### Technician Management
| Method | Endpoint | Body |
|--------|----------|------|
| GET | `/admin/technicians` | `?status=pending` |
| GET | `/admin/technicians/{id}` | — |
| POST | `/admin/technicians/{id}/approve` | — |
| POST | `/admin/technicians/{id}/reject` | `{ reason? }` |
| POST | `/admin/documents/{id}/approve` | — |
| POST | `/admin/documents/{id}/reject` | `{ reason }` |
| POST | `/admin/withdrawals/{id}/process` | `{ action: "approve"\|"reject", reason? }` |

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/dashboard/stats` | Overview cards + all counts |
| GET | `/admin/dashboard/activity` | Recent jobs, requests, withdrawals |
| GET | `/admin/users` | `?role=customer&search=ahmed` |

### Billing & Finance
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/billing/transactions` | `?status=completed` |
| GET | `/admin/billing/withdrawals` | `?status=pending` |
| GET | `/admin/billing/wallet-overview` | Total balances summary |
| GET | `/admin/billing/wallets` | `?role=technician` |

### Reports & Analytics
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/reports/requests` | `?period=week\|month\|year` |
| GET | `/admin/reports/services` | Service type pie chart data |
| GET | `/admin/reports/revenue` | Revenue by service bar chart |
| GET | `/admin/reports/top-technicians` | Top 10 leaderboard |
| GET | `/admin/reports/satisfaction` | Customer satisfaction % |
| GET | `/admin/reports/requests-breakdown` | Completed vs cancelled/month |
| GET | `/admin/reports/service-utilization` | Map data with lat/lng |

---

## ⚡ React Axios Setup

```js
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: { 'Content-Type': 'application/json' },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export default api;
```

```js
// Login
const { data } = await api.post('/login', { phone: '01000000000', password: 'password' });
localStorage.setItem('token', data.data.token);

// Get dashboard stats
const stats = await api.get('/admin/dashboard/stats');
```

---

## 🔑 Test Accounts

| Role | Phone | Password |
|------|-------|----------|
| Admin | 01000000000 | password |
| Customer | 01111111111 | password |
| Technician (approved) | 01333333333 | password |

---

## 📌 Notes

- All responses: `{ "success": true/false, "data": {...}, "message": "..." }`
- Paginated: `"meta": { "current_page", "last_page", "total" }`
- File uploads: use `multipart/form-data`
- Currency: **EGP** (Egyptian Pound)
- Dates: `YYYY-MM-DD`
