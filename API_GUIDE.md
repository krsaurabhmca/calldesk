# Calldesk CRM API Documentation

Base URL: `http://localhost/calldesk/api/`

## 1. Authentication
All endpoints (except login) require a Bearer Token in the Authorization header.

### Login
*   **Endpoint**: `login.php`
*   **Method**: `POST`
*   **Parameters**: `mobile`, `password`
*   **Response**: Returns an `api_token` and user details.

## 2. Call Logs
### Sync Call Logs
*   **Endpoint**: `sync_calls.php`
*   **Method**: `POST`
*   **Headers**: `Authorization: Bearer <your_token>`
*   **Body**: JSON Array of call objects.
    *   Example: `[{"mobile": "9999999999", "type": "Incoming", "duration": 45, "call_time": "2023-10-21 14:30:00"}]`

## 3. Leads
### List Leads
*   **Endpoint**: `leads.php`
*   **Method**: `GET`
*   **Headers**: `Authorization: Bearer <your_token>`

### Add Lead
*   **Endpoint**: `leads.php`
*   **Method**: `POST`
*   **Headers**: `Authorization: Bearer <your_token>`
*   **Parameters**: `name`, `mobile`, `source_id` (optional), `remarks` (optional)

## 4. Metadata
### Fetch Lead Sources
*   **Endpoint**: `sources.php`
*   **Method**: `GET`
*   **Headers**: `Authorization: Bearer <your_token>`
