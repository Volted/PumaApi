# PumaAPI

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)
![License](https://img.shields.io/badge/license-mit.svg)

## API Gateway for Puma Services

**PumaAPI** is a lightweight micro API module designed to parse, validate, and authenticate REST requests based on a simple, file-driven contract architecture. It leverages JWT (JSON Web Token) authentication and provides a clean, declarative way to define your API endpoints through JSON contract files.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Architecture Overview](#architecture-overview)
- [Manifest Structure](#manifest-structure)
    - [Directory Layout](#directory-layout)
    - [Contract File Format](#contract-file-format)
- [Validation Rules](#validation-rules)
- [JWT Authentication](#jwt-authentication)
- [Service Configuration](#service-configuration)
- [Making Outbound API Calls](#making-outbound-api-calls)
- [Certificate Object](#certificate-object)
- [Error Handling](#error-handling)
- [Global Configuration Flags](#global-configuration-flags)
- [Complete Usage Example](#complete-usage-example)
- [Security Considerations](#security-considerations)
- [API Reference](#api-reference)

---

## Features

- 📁 **File-Based Contract System** - Define API contracts using simple JSON files organized by HTTP method
- 🔐 **JWT Authentication** - Built-in JWT parsing, validation, and signature verification
- ✅ **Request Validation** - Automatic validation of headers, body, and JWT payloads against contracts
- 🧹 **Request Sanitization** - Returns only contracted fields, preventing data leakage
- 📤 **Outbound API Caller** - Built-in HTTP client for making authenticated requests to other services
- 🎯 **RESTful Design** - Supports GET, POST, PUT, and DELETE methods
- ⚡ **Lightweight** - Minimal dependencies (only `ext-json` and `ext-curl`)
- 🛡️ **Secure by Default** - Production-ready security with optional development flags

---

## Requirements

- PHP 5.6 or higher
- `ext-json` extension
- `ext-curl` extension
- Apache with mod_rewrite (recommended)

---

## Installation

```bash
composer require pumasoft/puma-api
```

### Manual Installation

1. Clone or download the repository
2. Include the autoloader or manually require the necessary files

```php
require_once 'path/to/PumaAPI/Controller/API.php';
```

---

## Quick Start

```php
$cert = $Puma->getCertificate(); // Access validated request data
$method = $cert->getRequestedMethod(); // e.g., 'get'
$controller = $cert->getRequestedRoot(); // e.g., 'auth'
$resource = $cert->getRequestedResource(); // e.g., 'bearing_username'
$body = $cert->getRequestBody(); // Validated request body
$jwtPayload = $cert->getRequestedJWTPayload(); // JWT claims
```

---

## Architecture Overview

PumaAPI follows a contract-first approach where API behavior is defined through JSON manifest files:

```
┌─────────────────────────────────────────────────────────────────┐
│ Incoming Request                                                │
└─────────────────────────────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────────┐
│ API Controller                                                  │
│ ┌──────────────┐ ┌──────────────┐ ┌────────────────────────┐     │
│ │Parse Request │→│Load Contract │→│ Validate & Authenticate│     │
│ └──────────────┘ └──────────────┘ └────────────────────────┘     │
└─────────────────────────────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────────┐
│ Certificate Object                                              │
│ (Sanitized request data + Response contract)                    │
└─────────────────────────────────────────────────────────────────┘
```

### Core Components

| Component     | Description                                             |
|---------------|---------------------------------------------------------|
| `API`         | Main controller that orchestrates the request lifecycle |
| `Request`     | Parses and structures incoming HTTP requests            |
| `Contract`    | Loads and validates requests against JSON contracts     |
| `Validator`   | Performs field-level validation based on rules          |
| `Tokenizer`   | Handles JWT generation, parsing, and verification       |
| `Certificate` | Contains validated request data for controller use      |
| `Caller`      | Makes outbound HTTP requests to other services          |
| `Rawr`        | Custom exception handler with HTTP response support     |

---

## Manifest Structure

### Directory Layout

The manifest directory follows a RESTful hierarchy:

```
__manifest/
├── service.ini # Service configuration & JWT secrets
├── get/
│   ├── auth/
│   │   ├── login.json
│   │   └── refresh.json
│   └── users/
│       ├── profile.json
│       └── list.json
├── post/
│   ├── auth/
│   │   └── register.json
│   └── users/
│       └── create.json
├── put/
│   └── users/
│       └── update.json
└── delete/
    └── users/
        └── remove.json
```

**URL Mapping:**

| HTTP Request           | Contract File                         |
|------------------------|---------------------------------------|
| `GET /auth/login`      | `__manifest/get/auth/login.json`      |
| `POST /users/create`   | `__manifest/post/users/create.json`   |
| `PUT /users/update`    | `__manifest/put/users/update.json`    |
| `DELETE /users/remove` | `__manifest/delete/users/remove.json` |

### Contract File Format

Each JSON contract defines the expected request format and response structure:

```json
{
  "Request": {
    "Headers": {
      "Content-Type": "application/json",
      "Authorization": {
        "Header": {
          "alg": "<<validAlgorithm>>",
          "typ": "<<validTokenType>>"
        },
        "Payload": {
          "iss": "<<validIssuer>>",
          "exp": "<<validUnixTimestamp>>"
        },
        "Signature": "<<validSignature>>"
      }
    },
    "Body": {
      "username": "<<notEmptyString>>",
      "password": "<<notEmptyString>>"
    }
  },
  "Response": {
    "Controller": "Auth",
    "Headers": {
      "Content-Type": "application/json"
    },
    "Body": {
      "result": "<<any>>"
    }
  }
}
```

---

## Validation Rules

Validation rules are specified using the `<<ruleName>>` syntax. Available built-in rules:

| Rule                     | Description                        | Example                            |
|--------------------------|------------------------------------|------------------------------------|
| `<<notEmptyString>>`     | Non-empty string value             | `"username": "<<notEmptyString>>"` |
| `<<integer>>`            | Integer value                      | `"age": "<<integer>>"`             |
| `<<validAlgorithm>>`     | JWT algorithm matching service.ini | `"alg": "<<validAlgorithm>>"`      |
| `<<validTokenType>>`     | JWT type matching service.ini      | `"typ": "<<validTokenType>>"`      |
| `<<validIssuer>>`        | Issuer registered in service.ini   | `"iss": "<<validIssuer>>"`         |
| `<<validUnixTimestamp>>` | Valid Unix timestamp               | `"exp": "<<validUnixTimestamp>>"`  |

### Exact Value Matching

If no rule syntax is used, the validator expects an exact match:

```json
{
  "Content-Type": "application/json"
}
```

This requires the `Content-Type` header to be exactly `application/json`.

---

## JWT Authentication

PumaAPI uses JWT Bearer tokens for authentication. Tokens must be sent in the `Authorization` header:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Token Structure

```
┌─────────────────────────────────────────────────────────────┐
│ Header.Payload.Signature                                    │
│ ───────┬───────────────┬──────────────                     │
│        │               │                                      │
│ ┌────┴────┐ ┌─────┴─────┐ ┌────────────────┐                │
│ │ {"alg": │ │ {"iss":   │ │ HMACSHA256(  │                │
│ │ "HS256",│ │ "app",    │ │ header+"."+  │                │
│ │ "typ":  │ │ "exp":    │ │ payload,     │                │
│ │ "JWT"}  │ │ 1234567}  │ │ secret)      │                │
│ └─────────┘ └───────────┘ └────────────────┘                │
└─────────────────────────────────────────────────────────────┘
```

### Generating Tokens

Use the `Tokenizer` class to generate new tokens:

```php
use PumaAPI\Model\Tokenizer;

$tokenizer = new Tokenizer('/path/to/__manifest');
$token = $tokenizer->generateNewToken(
    $tokenizer->getCurrentIssuer(),
    [
        'alg' => $tokenizer->getCurrentAlgorithm(),
        'typ' => $tokenizer->getCurrentTokenType()
    ],
    [
        'iss' => $tokenizer->getCurrentIssuer(),
        'exp' => time() + 3600
    ]
);
```

---

## Service Configuration

Create a `service.ini` file in your manifest directory:

```ini
[ident]
iss = my-service-name

[token]
head[alg] = HS256
head[typ] = JWT

[auth]
my-app = your-secret-key-here
partner-service = another-secret-key
trusted-client = client-specific-secret
```

### Configuration Sections

| Section   | Purpose                                  |
|-----------|------------------------------------------|
| `[ident]` | Service identity (issuer name)           |
| `[token]` | Default JWT header configuration         |
| `[auth]`  | Registered issuers and their secret keys |

---

## Making Outbound API Calls

Use the `Caller` class to make authenticated requests to other services:

```php
use PumaAPI\Model\Caller;
use PumaAPI\Model\Tokenizer;

// Generate a token for the target service
$tokenizer = new Tokenizer();
$jwt = $tokenizer->generateNewToken(
    $tokenizer->getCurrentIssuer(),
    [
        'alg' => $tokenizer->getCurrentAlgorithm(),
        'typ' => $tokenizer->getCurrentTokenType()
    ],
    [
        'iss' => $tokenizer->getCurrentIssuer(),
        'exp' => time() + 3600
    ]
);

// Create the caller
$caller = new Caller(
    Caller::POST,
    'https://api.partner.com/users/create',
    ['Content-Type' => 'application/json'],
    $jwt,
    ['name' => 'John Doe', 'email' => 'john@example.com']
);

// Execute the request
$caller->initRequest();

// Get the response
$response = $caller->getResponse(); // Returns: ['Code' => 200, 'Headers' => [...], 'Body' => '...']

// Parse JWT from response headers
$responseJWT = $caller->getJWTResponse(); // Returns: ['Head' => [...], 'Payload' => [...], 'Signature' => '...']
```

---

## Certificate Object

After successful validation, the `Certificate` object provides access to sanitized request data:

```php
$cert = $Puma->getCertificate();

// Request routing
$cert->getRequestedMethod(); // HTTP method (lowercase)
$cert->getRequestedRoot(); // Controller/root path
$cert->getRequestedResource(); // Resource identifier

// Request data (only contracted fields)
$cert->getRequestHeaders(); // Validated headers
$cert->getRequestBody(); // Validated body

// JWT data
$cert->getRequestedJWTHead(); // JWT header claims
$cert->getRequestedJWTPayload(); // JWT payload claims

// Response contract
$cert->getResponseContract(); // Expected response structure
```

---

## Error Handling

PumaAPI uses the `Rawr` exception class for structured error handling:

### HTTP Status Codes

| Code | Constant                   | Description                              |
|------|----------------------------|------------------------------------------|
| 400  | `Rawr::BAD_REQUEST`        | Invalid request format or missing fields |
| 401  | `Rawr::UNAUTHORIZED`       | Invalid or missing authentication        |
| 403  | `Rawr::FORBIDDEN`          | Access denied                            |
| 404  | `Rawr::NOT_FOUND`          | Resource or endpoint not found           |
| 405  | `Rawr::METHOD_NOT_ALLOWED` | HTTP method not supported                |
| 500  | `Rawr::INTERNAL_ERROR`     | Server-side error                        |

### Error Response Format

**Production (default):**

```json
{
  "error": "bad request"
}
```

**Development (with `PUMA_API_SEND_EXCEPTIONS_IN_RESPONSE`):**

```json
{
  "client": {"error": "bad request"},
  "server": "body variable 'username' is missing"
}
```

---

## Global Configuration Flags

> ⚠️ **Warning:** These flags are intended for development only. Never enable them in production!

```php
// Log exceptions to PHP error log
define('PUMA_API_LOG_EXCEPTIONS', true);

// Include detailed error messages in JSON response
define('PUMA_API_SEND_EXCEPTIONS_IN_RESPONSE', true);

// Disable SSL certificate verification for cURL requests
define('PUMA_API_DO_NOT_VALIDATE_SSL', true);
```

Define these constants in your bootstrap file **before** instantiating the API class. The presence of the constant enables the feature (the value is ignored).

---

## Complete Usage Example

### 1. Project Structure

```
project/
├── __manifest/
│   ├── service.ini
│   └── post/
│       └── auth/
│           └── login.json
├── controllers/
│   └── AuthController.php
├── index.php
└── .htaccess
```

### 2. Apache Rewrite Rules (.htaccess)

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```

### 3. Contract File (__manifest/post/auth/login.json)

```json
{
  "Request": {
    "Headers": {
      "Content-Type": "application/json",
      "Authorization": {
        "Header": {
          "alg": "<<validAlgorithm>>",
          "typ": "<<validTokenType>>"
        },
        "Payload": {
          "iss": "<<validIssuer>>",
          "exp": "<<validUnixTimestamp>>"
        }
      }
    },
    "Body": {
      "username": "<<notEmptyString>>",
      "password": "<<notEmptyString>>"
    }
  },
  "Response": {
    "Controller": "Auth",
    "Action": "login"
  }
}
```

### 4. Service Configuration (__manifest/service.ini)

```ini
[ident]
iss = my-api

[token]
head[alg] = HS256
head[typ] = JWT

[auth]
my-api = your-super-secret-key-here
mobile-app = mobile-app-secret-key
```

### 5. Entry Point (index.php)

```php
$cert = $Puma->getCertificate();

// Route to appropriate controller
$controllerName = $cert->getResponseContract()['Controller'] ?? 'Default';
$controllerClass = "Controllers\\{$controllerName}Controller";

if (class_exists($controllerClass)) {
    $controller = new $controllerClass($cert);
    $controller->handle();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Controller not found']);
}
```

### 6. Controller (controllers/AuthController.php)

```php
namespace Controllers;

use PumaAPI\Model\Tokenizer;

class AuthController {
    private $cert;

    public function __construct($cert) {
        $this->cert = $cert;
    }

    public function handle() {
        $body = $this->cert->getRequestBody();
        $username = $body['username'];
        $password = $body['password'];

        // Validate credentials (implement your logic)
        if ($this->validateCredentials($username, $password)) {
            $tokenizer = new Tokenizer();
            $token = $tokenizer->generateNewToken(
                $tokenizer->getCurrentIssuer(),
                [
                    'alg' => $tokenizer->getCurrentAlgorithm(),
                    'typ' => $tokenizer->getCurrentTokenType()
                ],
                [
                    'iss' => $tokenizer->getCurrentIssuer(),
                    'exp' => time() + 3600,
                    'sub' => $username
                ]
            );

            header('Content-Type: application/json');
            header('Authorization: Bearer ' . $token);
            echo json_encode(['result' => 'success', 'message' => 'Login successful']);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    }

    private function validateCredentials($username, $password) {
        // Implement your authentication logic
        return true;
    }
}
```

---

## Security Considerations

1. **Keep service.ini secure** - This file contains secret keys; ensure it's not web-accessible
2. **Use HTTPS** - Always use SSL/TLS in production
3. **Rotate secrets regularly** - Update JWT signing keys periodically
4. **Set short expiration times** - Use reasonable `exp` values for tokens
5. **Disable development flags** - Never use `PUMA_API_*` constants in production
6. **Validate all input** - Define comprehensive contracts for all endpoints

---

## API Reference

### PumaAPI\Controller\API

| Method                               | Returns       | Description                            |
|--------------------------------------|---------------|----------------------------------------|
| `__construct($manifestPath = false)` | `API`         | Initialize with optional manifest path |
| `getCertificate()`                   | `Certificate` | Get validated request certificate      |

### PumaAPI\Model\Certificate

| Method                     | Returns  | Description                  |
|----------------------------|----------|------------------------------|
| `getRequestedMethod()`     | `string` | HTTP method (lowercase)      |
| `getRequestedRoot()`       | `string` | Controller/root segment      |
| `getRequestedResource()`   | `string` | Resource path                |
| `getRequestHeaders()`      | `array`  | Validated request headers    |
| `getRequestBody()`         | `array`  | Validated request body       |
| `getRequestedJWTHead()`    | `array`  | JWT header claims            |
| `getRequestedJWTPayload()` | `array`  | JWT payload claims           |
| `getResponseContract()`    | `array`  | Response contract definition |

### PumaAPI\Model\Tokenizer

| Method                                        | Returns     | Description                     |
|-----------------------------------------------|-------------|---------------------------------|
| `__construct($configPath = false)`            | `Tokenizer` | Initialize with config path     |
| `generateNewToken($issuer, $head, $body)`     | `string`    | Generate complete JWT           |
| `generateSignatureFor($issuer, $head, $body)` | `string`    | Generate signature only         |
| `getCurrentIssuer()`                          | `string`    | Get configured issuer           |
| `getCurrentAlgorithm()`                       | `string`    | Get configured algorithm        |
| `getCurrentTokenType()`                       | `string`    | Get configured token type       |
| `isAuthentic($tokenContent, $issuer)`         | `bool`      | Verify token signature          |
| `validExpiryDate($tokenHead)`                 | `bool`      | Check if token is expired       |
| `extractJWT($headers)`                        | `array`     | Parse JWT from headers (static) |

### PumaAPI\Model\Caller

| Method                                              | Returns  | Description                        |
|-----------------------------------------------------|----------|------------------------------------|
| `__construct($method, $url, $headers, $jwt, $body)` | `Caller` | Initialize request                 |
| `initRequest()`                                     | `void`   | Execute the HTTP request           |
| `getResponse()`                                     | `array`  | Get response (code, headers, body) |
| `getJWTResponse()`                                  | `array`  | Parse JWT from response            |

---