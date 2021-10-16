# PumaApi API gateway for Puma services

## Micro API module designed to parse and validate REST requests with accordance to simple file structure defined in specific folder

<p>files are supposed to be placed in folowing order: ROOT/MANIFEST_FOLDER/REQUEST_METHOD/CONTROLLER/RESOURCE
API contract JSON files are placed in corresponding folders</p>

## Example:
for operation api.domain/users/by-id // with get method
folder structure should reflect
/__manifest/get/users/by-id.json

<pre><code>where by-id.json is contract file describing the operation:
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
}</code></pre>


## Global flags:
## NOTE: const value does not metter the system will check if const is defined or not
<pre><code>
const PUMA_API_LOG_EXCEPTIONS=true; // will php.log the exceptions, by default will not
const PUMA_API_SEND_EXCEPTIONS_IN_RESPONSE=true; // will send exceptions in JSON response instead of php.log
const PUMA_API_DO_NOT_VALIDATE_SSL = true; // will not send secure curl requests
</code></pre>

## NOTE: all global flags are intended for development environment on local machine should be kept on your local machine and never submitted to production.
with global flags absent API will act in most secure and discreet way possible.

## Usage example:
<pre><code>
use PumaAPI\Controller\API;
$Puma = new API({{MANIFEST_DIRECTORY}}); // manifest directory by default is ROOT/__manifest/
$cert = $Puma->getCertificate(); // gets certified request formatted according to certificate file
</code></pre>


