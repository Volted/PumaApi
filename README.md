# PumaApi API gateway for Apache PHP Puma services

### Micro API module designed to parse and validate REST requests with accordance to simple file structure defined in specific folder

files are supposed to be placed in following order:
#####{ROOT}/{MANIFEST_FOLDER}/{REQUEST_METHOD}/{CONTROLLER}/{RESOURCE}
API contract JSON files are placed in corresponding folders
### Example:
for operation 
* url: api.domain/auth/bearing_username
* method: GET
<p>folder structure should reflect:</p>
<p>/__manifest/get/auth/bearing_username.json</p>
<p>where by-id.json is contract file describing the operation:</p>

```javascript

{
	"Request":  {
		"Headers": {
			"Content-Type":  "application/json",
			"Authorization": {
				"Header":    {
					"alg": "<<validAlgorithm>>",
					"typ": "<<validTokenType>>"
				},
				"Payload":   {
					"iss":      "<<validIssuer>>",
					"exp":      "<<validUnixTimestamp>>"
				},
				"Signature": "<<validSignature>>"
			}
		},
		"Body":    {
			"username": "<<notEmptyString>>",
			"password": "<<notEmptyString>>"
		}
	},
	"Response": {
		"Controller": "Auth",
		"Headers":    {
			"Content-Type":  "application/json",
			"Authorization": {
				"Header":    {
					"alg": "HS256",
					"typ": "JWT"
				},
				"Payload":   {
					"iss":   "<<validIssuer>>",
					"exp":   "<<validUnixTimestamp>>",
					"name":  "<<applicantName>>",
					"roles": "<<applicantRoles>>",
					"ref":   "<<refreshToken>>"
				},
				"Signature": "<<valid_signature>>"
			}
		},
		"Body":       {
			"result": "<<operationResult>>"
		}
	}
}

```

## Global flags:
> :warning: **constants values are discarded the system will check if const is defined or not**
```php

const PUMA_API_LOG_EXCEPTIONS=true; // will php.log the exceptions, by default will not
const PUMA_API_SEND_EXCEPTIONS_IN_RESPONSE=true; // will send exceptions in JSON response instead of php.log
const PUMA_API_DO_NOT_VALIDATE_SSL = true; // will not send secure curl requests
```

> :warning: **all global flags are intended for development environment on local machine should be kept on your local machine and never submitted to production.**

with global flags absent API will act in most secure and discreet way possible.

### Usage example:
```php
use PumaAPI\Controller\API;
$Puma = new API({{MANIFEST_DIRECTORY}}); // manifest directory by default is ROOT/__manifest/
$cert = $Puma->getCertificate(); // gets certified request formatted according to certificate file
```


