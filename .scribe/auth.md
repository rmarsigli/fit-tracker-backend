# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_BEARER_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

You can retrieve your authentication token by calling the <code>POST /api/v1/login</code> or <code>POST /api/v1/register</code> endpoints. Include the token in the <b>Authorization</b> header as <code>Bearer {token}</code> for all authenticated requests.
