# KASSA ONE API Documentation

This document outlines the API endpoints for the KASSA ONE application backend.

## Base URL

`http://your-kassa-api-url.com/api`
## Authentication

All endpoints under the `auth:sanctum` middleware require a valid `Bearer` token in the `Authorization` header.

### Postman Tutorial for Bearer Token Authentication

To interact with authenticated endpoints using Postman, follow these steps:

1. **Obtain an Access Token:**

   * Make a `POST` request to the `/login` endpoint (e.g., `http://your-kassa-api-url.com/api/login`).
   * In the "Body" tab, select `raw` and `JSON` and provide your `username` and `password`.
   * Send the request. The response will contain an `access_token`. Copy this token.
2. **Set up Authorization for Authenticated Requests:**

   * Open a new request tab for an authenticated endpoint (e.g., `GET /user`).
   * Go to the "Authorization" tab.
   * Select `Type` as `Bearer Token`.
   * In the `Token` field, paste the `access_token` you copied in step 1.
   * Send the request. You should now be able to access the authenticated endpoint.
3. **Using Environment Variables (Recommended):**

   * For easier management, you can store the `access_token` in a Postman environment variable.
   * Create a new environment (or select an existing one).
   * Add a new variable, for example, `access_token`, and paste your token into the `Current Value` field.
   * In your authenticated requests, in the "Authorization" tab, for `Bearer Token`, you can now use `{{access_token}}` instead of pasting the token directly. This way, you only need to update the environment variable when your token expires.

## Standard Error Responses

The API uses standard HTTP status codes to indicate the success or failure of a request.

- **`401 Unauthorized`**: The request lacks valid authentication credentials.
- **`403 Forbidden`**: The authenticated user does not have permission to perform the requested action.
- **`404 Not Found`**: The requested resource could not be found.
- **`422 Unprocessable Entity`**: The request was well-formed but was unable to be followed due to semantic errors (e.g., validation failures).
- **`500 Internal Server Error`**: A generic error message for an unexpected condition on the server.

### Example Error Response (`422 Unprocessable Entity`)

This response is returned when validation fails for a `POST` or `PUT` request.

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email has already been taken."
        ],
        "password": [
            "The password must be at least 8 characters."
        ]
    }
}
```

### Example General Error Response (`404 Not Found`)

```json
{
    "message": "Resource not found."
}
```

---

### 1. Register a New Member

**Endpoint:** `POST /register`

**Description:** Registers a new member with a default role of 'Anggota' if not specified.

**Request Body:**

```json
{
    "full_name": "string (required)",
    "username": "string (required, unique)",
    "email": "string (optional, email, unique)",
    "phone_number": "string (optional)",
    "address": "string (optional)",
    "join_date": "YYYY-MM-DD (optional, defaults to current date)",
    "password": "string (required, min:8, confirmed)",
    "password_confirmation": "string (required, must match password)",
    "role_id": "integer (optional, exists in roles table)" // Defaults to 'Anggota' role if not provided
}
```

**Response Body (201 Created):**

```json
{
    "message": "Registration successful",
    "access_token": "string",
    "token_type": "Bearer",
    "user": {
        "id": "integer",
        "name": "string",
        "username": "string",
        "email": "string",
        "role_id": "integer",
        "created_at": "timestamp",
        "updated_at": "timestamp",
        "role": {
            "id": "integer",
            "name": "string",
            "description": "string"
        },
        "member": {
            "id": "integer",
            "user_id": "integer",
            "member_id_number": "string",
            "full_name": "string",
            "address": "string",
            "phone_number": "string",
            "join_date": "YYYY-MM-DD",
            "member_type": "string",
            "status": "string",
            "created_at": "timestamp",
            "updated_at": "timestamp"
        }
    }
}
```


### 2. User Login

* **Endpoint:** `POST /login`
* **Description:** Authenticates a user and returns an access token.
* **Request Body:**
  ```json
  {
      "login": "string (required, can be username or email)",
      "password": "string (required)"
  }
  ```
* **Response Body (200 OK):**
  ```json
  {
      "message": "Login successful",
      "access_token": "string",
      "token_type": "Bearer",
      "user": {
          "id": "integer",
          "member_id_number": "string",
          "full_name": "string",
          "username": "string",
          "email": "string",
          "phone_number": "string",
          "address": "string",
          "join_date": "YYYY-MM-DD",
          "status": "string",
          "role_id": "integer",
          "created_at": "timestamp",
          "updated_at": "timestamp",
          "role": {
              "id": "integer",
              "name": "string",
              "description": "s
  }  }einooitB (200 OK):*
* **Request Body:** (None)
* **Response Body (200 OK):**
  ```json
  {
      "id": "integer",
      "member_id_number": "string",
      "full_name": "string",
      "username": "string",
      "email": "string",
      "phone_number": "string",
      "address": "string",
      "join_date": "YYYY-MM-DD",
      "status": "string",
      "role_id": "integer",
      "created_at": "timestamp",
      "updated_at": "timestamp",
      "role": {
          "id": "integer",
          "name": "string",
          "description": "string"
      }
  }
  ```

## Dashboard

### 5. Get Dashboard Statistics

* **Endpoint:** `GET /dashboard/stats`
* **Description:** Retrieves various statistics for the application dashboard.
* **Authentication:** Required (Bearer Token)
* **Request Body:** (None)
* **Response Body (200 OK):**
  ```json
  {
      "totalAnggota": "integer",
      "anggotaBaruBulanIni": "integer",
      "totalSimpanan": "decimal",
      "totalPembiayaan": "decimal",
      "shuTahunBerjalan": "decimal",
      "transaksiBulanIni": "integer",
      "rapatTerjadwal": "integer"
  }
  ```

## Member Management

### 6. Get All Members

* **Endpoint:** `GET /members`
* **Description:** Retrieves a list of all members with their associated roles.
* **Authentication:** Required (Bearer Token)
* **Request Body:** (None)
* **Response Body (200 OK):** Array of member objects.
  ```json
  [
      {
          "id": "integer",
          "member_id_number": "string",
          "full_name": "string",
          "username": "string",
          "email": "string",
          "phone_number": "string",
          "address": "string",
          "join_date": "YYYY-MM-DD",
          "status": "string",
          "role_id": "integer",
          "created_at": "timestamp",
          "updated_at": "timestamp",
          "role": {
              "id": "integer",
              "name": "string",
              "description": "string"
          }
      }
  ]
  ```

### 7. Get Member by ID

* **Endpoint:** `GET /members/{id}`
* **Description:** Retrieves details of a specific member by their ID.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the member)
* **Request Body:** (None)
* **Response Body (200 OK):** Member object.

### 8. Create New Member

* **Endpoint:** `POST /members`
* **Description:** Creates a new member.
* **Authentication:** Required (Bearer Token)
* **Request Body:**
  ```json
  {
      "full_name": "string (required)",
      "member_id_number": "string (required, unique)",
      "username": "string (required, unique)",
      "email": "string (optional, email, unique)",
      "phone_number": "string (optional)",
      "address": "string (optional)",
      "join_date": "YYYY-MM-DD (required)",
      "password": "string (required, min:8, confirmed)",
      "password_confirmation": "string (required, must match password)",
      "status": "string (required, enum: active, inactive, suspended)",
      "role_id": "integer (required, exists in roles table)"
  }
  ```
* **Response Body (201 Created):** New member object.

### 9. Update Member

* **Endpoint:** `PUT /members/{id}`
* **Description:** Updates an existing member's details.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the member to update)
* **Request Body:** (Partial member object with fields to update)
* **Response Body (200 OK):** Updated member object.

### 10. Delete Member

* **Endpoint:** `DELETE /members/{id}`
* **Description:** Deletes a member.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the member to delete)
* **Request Body:** (None)
* **Response Body (200 OK):**
  ```json
  {
      "message": "Member deleted successfully"
  }
  ```

## Savings Accounts

### 11. Get All Savings Accounts for a Member

* **Endpoint:** `GET /members/{member_id}/savings`
* **Description:** Retrieves all savings accounts associated with a specific member.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `member_id`: `integer` (The ID of the member)
* **Request Body:** (None)
* **Response Body (200 OK):** Array of savings account objects.
  ```json
  [
      {
          "id": "integer",
          "member_id": "integer",
          "account_type": "string (enum: pokok, wajib, sukarela)",
          "balance": "decimal",
          "created_at": "timestamp",
          "updated_at": "timestamp"
      }
  ]
  ```

### 12. Get Savings Account by ID

* **Endpoint:** `GET /savings/{id}`
* **Description:** Retrieves details of a specific savings account by its ID.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the savings account)
* **Request Body:** (None)
* **Response Body (200 OK):** Savings account object.

### 13. Create New Savings Account for a Member

* **Endpoint:** `POST /members/{member_id}/savings`
* **Description:** Creates a new savings account for a specified member.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `member_id`: `integer` (The ID of the member)
* **Request Body:**
  ```json
  {
      "account_type": "string (required, enum: pokok, wajib, sukarela)",
      "balance": "decimal (required, min:0)"
  }
  ```
* **Response Body (201 Created):** New savings account object.

### 14. Update Savings Account

* **Endpoint:** `PUT /savings/{id}`
* **Description:** Updates an existing savings account's details (e.g., balance).
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the savings account to update)
* **Request Body:**
  ```json
  {
      "balance": "decimal (optional, min:0)",
      "account_type": "string (optional, enum: pokok, wajib, sukarela)"
  }
  ```
* **Response Body (200 OK):** Updated savings account object.

## Transactions

### 15. Get All Transactions

* **Endpoint:** `GET /transactions`
* **Description:** Retrieves a list of all transactions. Can be filtered by `member_id`, `transaction_type`, `start_date`, and `end_date` query parameters.
* **Authentication:** Required (Bearer Token)
* **Query Parameters:**
  * `member_id`: `integer` (Optional, filter by member ID)
  * `transaction_type`: `string` (Optional, filter by type: deposit, withdrawal, shu_distribution, fee)
  * `start_date`: `YYYY-MM-DD` (Optional, filter by transaction date range start)
  * `end_date`: `YYYY-MM-DD` (Optional, filter by transaction date range end)
* **Request Body:** (None)
* **Response Body (200 OK):** Array of transaction objects.
  ```json
  [
      {
          "id": "integer",
          "savings_account_id": "integer",
          "member_id": "integer",
          "transaction_type": "string (enum: deposit, withdrawal, shu_distribution, fee)",
          "amount": "decimal",
          "description": "string",
          "transaction_date": "datetime",
          "created_at": "timestamp",
          "member": { ... }, // Member object
          "savings_account": { ... } // SavingsAccount object
      }
  ]
  ```

### 16. Get Transaction by ID

* **Endpoint:** `GET /transactions/{id}`
* **Description:** Retrieves details of a specific transaction by its ID.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the transaction)
* **Request Body:** (None)
* **Response Body (200 OK):** Transaction object.

### 17. Create New Transaction

* **Endpoint:** `POST /transactions`
* **Description:** Records a new transaction and updates the associated savings account balance.
* **Authentication:** Required (Bearer Token)
* **Request Body:**
  ```json
  {
      "savings_account_id": "integer (required, exists in savings_accounts table)",
      "member_id": "integer (required, exists in members table)",
      "transaction_type": "string (required, enum: deposit, withdrawal, shu_distribution, fee)",
      "amount": "decimal (required, min:0)",
      "description": "string (optional)",
      "transaction_date": "datetime (required)"
  }
  ```
* **Response Body (201 Created):** New transaction object.

## Meetings

### 18. Get All Meetings

* **Endpoint:** `GET /meetings`
* **Description:** Retrieves a list of all meetings.
* **Authentication:** Required (Bearer Token)
* **Request Body:** (None)
* **Response Body (200 OK):** Array of meeting objects.
  ```json
  [
      {
          "id": "integer",
          "title": "string",
          "description": "string",
          "meeting_date": "datetime",
          "location": "string",
          "created_at": "timestamp",
          "updated_at": "timestamp"
      }
  ]
  ```

### 19. Get Meeting by ID

* **Endpoint:** `GET /meetings/{id}`
* **Description:** Retrieves details of a specific meeting by its ID.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the meeting)
* **Request Body:** (None)
* **Response Body (200 OK):** Meeting object.

### 20. Create New Meeting

* **Endpoint:** `POST /meetings`
* **Description:** Creates a new meeting.
* **Authentication:** Required (Bearer Token)
* **Request Body:**
  ```json
  {
      "title": "string (required)",
      "description": "string (optional)",
      "meeting_date": "datetime (required)",
      "location": "string (optional)"
  }
  ```
* **Response Body (201 Created):** New meeting object.

### 21. Update Meeting

* **Endpoint:** `PUT /meetings/{id}`
* **Description:** Updates an existing meeting's details.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the meeting to update)
* **Request Body:** (Partial meeting object with fields to update)
* **Response Body (200 OK):** Updated meeting object.

### 22. Delete Meeting

* **Endpoint:** `DELETE /meetings/{id}`
* **Description:** Deletes a meeting.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the meeting to delete)
* **Request Body:** (None)
* **Response Body (200 OK):**
  ```json
  {
      "message": "Meeting deleted successfully"
  }
  ```

## Meeting Attendance

### 23. Get Meeting Attendance Records

* **Endpoint:** `GET /meetings/{meeting_id}/attendance`
* **Description:** Retrieves attendance records for a specific meeting.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `meeting_id`: `integer` (The ID of the meeting)
* **Request Body:** (None)
* **Response Body (200 OK):** Array of meeting attendance objects.
  ```json
  [
      {
          "id": "integer",
          "meeting_id": "integer",
          "member_id": "integer",
          "is_present": "boolean",
          "created_at": "timestamp",
          "updated_at": "timestamp",
          "member": { ... } // Member object
      }
  ]
  ```

### 24. Record Meeting Attendance

* **Endpoint:** `POST /meetings/{meeting_id}/attendance`
* **Description:** Records attendance for a member in a specific meeting.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `meeting_id`: `integer` (The ID of the meeting)
* **Request Body:**
  ```json
  {
      "member_id": "integer (required, exists in members table)",
      "is_present": "boolean (required)"
  }
  ```
* **Response Body (201 Created):** New meeting attendance object.

### 25. Update Meeting Attendance

* **Endpoint:** `PUT /meeting-attendance/{id}`
* **Description:** Updates an existing meeting attendance record.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the attendance record to update)
* **Request Body:**
  ```json
  {
      "is_present": "boolean (optional)"
  }
  ```
* **Response Body (200 OK):** Updated meeting attendance object.

## SHU Distributions

### 26. Get All SHU Distributions

* **Endpoint:** `GET /shu-distributions`
* **Description:** Retrieves a list of all SHU distributions.
* **Authentication:** Required (Bearer Token)
* **Request Body:** (None)
* **Response Body (200 OK):** Array of SHU distribution objects.
  ```json
  [
      {
          "id": "integer",
          "fiscal_year": "integer",
          "total_shu_amount": "decimal",
          "distribution_date": "YYYY-MM-DD",
          "notes": "string",
          "created_at": "timestamp",
          "updated_at": "timestamp"
      }
  ]
  ```

### 27. Get SHU Distribution by ID

* **Endpoint:** `GET /shu-distributions/{id}`
* **Description:** Retrieves details of a specific SHU distribution by its ID.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the SHU distribution)
* **Request Body:** (None)
* **Response Body (200 OK):** SHU distribution object.

### 28. Create New SHU Distribution

* **Endpoint:** `POST /shu-distributions`
* **Description:** Creates a new SHU distribution.
* **Authentication:** Required (Bearer Token)
* **Request Body:**
  ```json
  {
      "fiscal_year": "integer (required, unique)",
      "total_shu_amount": "decimal (required, min:0)",
      "distribution_date": "YYYY-MM-DD (required)",
      "notes": "string (optional)"
  }
  ```
* **Response Body (201 Created):** New SHU distribution object.

### 29. Update SHU Distribution

* **Endpoint:** `PUT /shu-distributions/{id}`
* **Description:** Updates an existing SHU distribution's details.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the SHU distribution to update)
* **Request Body:** (Partial SHU distribution object with fields to update)
* **Response Body (200 OK):** Updated SHU distribution object.

### 30. Delete SHU Distribution

* **Endpoint:** `DELETE /shu-distributions/{id}`
* **Description:** Deletes an existing SHU distribution.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the SHU distribution to delete)
* **Request Body:** (None)
* **Response Body (200 OK):**
  ```json
  {
      "message": "SHU distribution deleted successfully"
  }
  ```

## SHU Member Allocations

### 30. Get All SHU Member Allocations for a Distribution

* **Endpoint:** `GET /shu-distributions/{shu_distribution_id}/allocations`
* **Description:** Retrieves all SHU member allocations for a specific SHU distribution.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `shu_distribution_id`: `integer` (The ID of the SHU distribution)
* **Request Body:** (None)
* **Response Body (200 OK):** Array of SHU member allocation objects.
  ```json
  [
      {
          "id": "integer",
          "shu_distribution_id": "integer",
          "member_id": "integer",
          "amount_allocated": "decimal",
          "is_paid_out": "boolean",
          "payout_transaction_id": "integer (nullable)",
          "created_at": "timestamp",
          "updated_at": "timestamp",
          "member": { ... } // Member object
      }
  ]
  ```

### 31. Get SHU Member Allocation by ID

* **Endpoint:** `GET /shu-allocations/{id}`
* **Description:** Retrieves details of a specific SHU member allocation by its ID.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the SHU member allocation)
* **Request Body:** (None)
* **Response Body (200 OK):** SHU member allocation object.

### 32. Create New SHU Member Allocation

* **Endpoint:** `POST /shu-distributions/{shu_distribution_id}/allocations`
* **Description:** Creates a new SHU member allocation for a specified SHU distribution.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `shu_distribution_id`: `integer` (The ID of the SHU distribution)
* **Request Body:**
  ```json
  {
      "member_id": "integer (required, exists in members table)",
      "amount_allocated": "decimal (required, min:0)",
      "is_paid_out": "boolean (required)",
      "payout_transaction_id": "integer (nullable, exists in transactions table)"
  }
  ```
* **Response Body (201 Created):** New SHU member allocation object.

### 33. Update SHU Member Allocation

* **Endpoint:** `PUT /shu-allocations/{id}`
* **Description:** Updates an existing SHU member allocation's details.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the SHU member allocation to update)
* **Request Body:**
  ```json
  {
      "amount_allocated": "decimal (optional, min:0)",
      "is_paid_out": "boolean (optional)",
      "payout_transaction_id": "integer (nullable, exists in transactions table)"
  }
  ```
* **Response Body (200 OK):** Updated SHU member allocation object.

### 34. Delete SHU Member Allocation

* **Endpoint:** `DELETE /shu-allocations/{id}`
* **Description:** Deletes an existing SHU member allocation.
* **Authentication:** Required (Bearer Token)
* **Path Parameters:**
  * `id`: `integer` (The ID of the SHU member allocation to delete)
* **Request Body:** (None)
* **Response Body (200 OK):**
  ```json
  {
      "message": "SHU member allocation deleted successfully"
  }
  ```

## Testimonials

### 35. Get All Approved Testimonials

* **Endpoint:** `GET /testimonials`
* **Description:** Retrieves a list of all approved testimonials.
* **Authentication:** Required (Bearer Token)
* **Request Body:** (None)
* **Response Body (200 OK):** Array of testimonial objects.
  ```json
  [
      {
          "id": "integer",
          "member_id": "integer",
          "testimonial_text": "string",
          "is_approved": true,
          "submitted_at": "timestamp",
          "updated_at": "timestamp",
          "member": { ... } // Member object
      }
  ]
  ```

### 36. Submit Member Testimonial

* **Endpoint:** `POST /testimonials`
* **Description:** Allows an authenticated member to submit a testimonial.
* **Authentication:** Required (Bearer Token)
* **Request Body:**
  ```json
  {
      "testimonial_text": "string (required)"
  }
  ```
* **Response Body (201 Created):**
  ```json
  {
      "id": "integer",
      "member_id": "integer",
      "testimonial_text": "string",
      "is_approved": "boolean",
      "submitted_at": "timestamp",
      "updated_at": "timestamp"
  }
  ```
