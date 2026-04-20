# AT-AMS API Documentation

## Authentication Functions

### login($email_or_username, $password)
Authenticates user and creates session.

```php
if (login('username', 'password')) {
    redirect('dashboard.php');
}
```

**Parameters:**
- `email_or_username` (string) - User's email or username
- `password` (string) - User's password

**Returns:** `bool` - true on success, false on failure

**Side Effects:**
- Creates session variables: `user_id`, `user_role`, `user_name`, `department_id`
- Upgrades plain text passwords to bcrypt hash

---

### logout()
Destroys session and redirects to login.

```php
logout();
```

---

### isLoggedIn()
Checks if user is authenticated.

```php
if (!isLoggedIn()) {
    redirect('login.php');
}
```

**Returns:** `bool`

---

### getUser()
Gets current user data from database.

```php
$user = getUser();
echo $user['first_name'];
```

**Returns:** `array|null` - User data or null if not logged in

---

### requireLogin()
Redirects to login if user not authenticated.

```php
requireLogin();
```

---

## CSRF Security

### generateCSRF()
Generates CSRF token for forms.

```php
<input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
```

**Returns:** `string` - CSRF token

---

### validateCSRF($token)
Validates CSRF token.

```php
if (!validateCSRF($_POST['csrf_token'])) {
    die('Security error');
}
```

**Parameters:**
- `token` (string) - Token to validate

**Returns:** `bool`

---

## Database Functions

### getDB()
Returns MySQLi database connection.

```php
$conn = getDB();
$result = $conn->query("SELECT * FROM users");
```

**Returns:** `mysqli` - Database connection object

---

## Utility Functions

### redirect($url)
Redirects to specified URL.

```php
redirect('dashboard.php');
```

---

### generateRefNumber()
Generates document reference number.

```php
$ref = generateRefNumber(); // AT-2026-0001
```

**Returns:** `string` - Reference number in format AT-YYYY-NNNN

---

## HTTP Endpoints

### GET /pages/auth/get_cities.php
Fetch cities by wilaya ID.

**Parameters:**
- `wilaya_id` (int) - Wilaya ID

**Response:**
```json
[
  {"id": 1, "name": "Alger Centre"},
  {"id": 2, "name": "Bab El Oued"}
]
```

---

## Form Actions

### POST /pages/auth/register_action.php
Processes registration form.

**Required Fields:**
- first_name, last_name, username, email
- phone, password, confirm_password
- nif, rc, rib, bank_name
- wilaya_id, city_id, address
- csrf_token

**Success:** Redirects to login with `registered=1`

**Error:** Redirects to register with `error=message`

---

## Session Variables

| Variable | Description |
|----------|-------------|
| `user_id` | Current user ID |
| `user_role` | super_admin, dept_admin, internal_staff, enterprise |
| `user_name` | First name + Last name |
| `department_id` | User's department (if applicable) |
| `csrf_token` | CSRF security token |

---

## Role Permissions

### super_admin
- All permissions
- Access to settings
- Access to audit logs

### dept_admin
- Manage department users
- Manage department documents
- Approve enterprises

### internal_staff
- Upload documents
- View documents
- Manage own documents

### enterprise
- Register company
- Upload documents
- View own documents only

---

*API Documentation - AT-AMS v1.0.0*
