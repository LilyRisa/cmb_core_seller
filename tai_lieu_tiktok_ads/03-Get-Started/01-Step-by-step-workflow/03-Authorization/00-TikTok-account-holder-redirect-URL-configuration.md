---
title: "TikTok account holder redirect URL configuration"
doc_id: 1832209711206401
path: "Get Started / Step-by-step workflow / Authorization / TikTok account holder redirect URL configuration"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# TikTok account holder redirect URL configuration

The redirect URL is a critical component in the OAuth 2.0 authorization framework, designed to ensure that authorization responses are securely delivered to a specified URL. Proper registration of redirect URLs helps prevent open redirector vulnerabilities, which can be exploited in phishing attacks and other malicious activities.

A **TikTok account holder redirect URL** is a URL where the TikTok account user is redirected after authorizing your developer app with TikTok account permissions. After the TikTok account user grants authorization, the redirect URL will be appended with an auth_code for generating a short-term access token. Developers can configure the redirect URL as a URL hosted on the developer's server to automatically parse the `auth_code`, eliminating the need for the TikTok account user to manually locate and share the auth_code with the developer.

You can configure up to 10 TikTok account holder redirect URLs per developer app. Each activated TikTok account holder redirect URL automatically generates a new TikTok account holder authorization URL. The system maintains one active URL at a time, marked in red. When only one TikTok account holder redirect URL exists, it becomes active by default. For multiple redirect URLs, activate a different one by clicking your desired URL.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/19f82efed154974ebcc5c35e8e7d2529" alt="TikTok account holder redirect URL" width="60%" style="color:#808080" />


## Formatting rules for redirect URLs
When defining a TikTok account holder redirect URL for your developer app, follow these formatting rules to ensure proper functionality:

```xtable
|  Rules {25%}| Correct example {32%}| Bad example{37%} |
|---|---|---|
|URLs must be absolute and end with /. | `https://dev.example.com/auth/callback/` | <ul><li> `https://dev.example.com/auth/callback` <ul><li> missing trailing `/`</li></ul></li><li> `/auth/callback/` <ul><li> missing scheme and domain </li></ul></li></ul>|
| URLs should be static, while parameters are denied and should be ignored. | `https://dev.example.com/auth/callback/` | <ul><li>`https://dev.example.com/auth/callback/?id=1`<ul><li> contains query parameter `?id=1`</li></ul></li></ul>|
|  URLs cannot include anchors #. | `https://dev.example.com/auth/callback/` | <ul><li>`https://dev.example.com/auth/callback/#1` <ul><li> contains anchor `#1`</li></ul></li></ul>|
| URLs must start with https://. | `https://dev.example.com/auth/callback/` | <ul><li>`http://dev.example.com/auth/callback/` <ul><li> uses `http` instead of `https` </li></ul></li></ul>|
| URLs cannot include ports. | `https://dev.example.com/auth/callback/` | <ul><li>` https://dev.example.com:3000/auth/callback/` <ul><li> contains port number `:3000`  </li></ul></li></ul> |
| The length of the registered URI should fall within the range of 10 to 512 characters. | N/A| N/A|
```

Otherwise, you will receive the error message as follows. You can edit your redirect URL accordingly. 

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/cff7c2eda1af4926c8f6658720cf8cf5" alt="error message" width="60%" style="color:#808080" />

## Migration instructions for redirect URL

To migrate your TikTok account holder redirect URL, follow these steps:

(1) Modify your redirect URL by removing any query parameters  (indicated by the `?` symbol) or fragments (indicated by the `#` symbol) that may be present at the end of the path. Your URL should end with a forward slash (`/`).

(2) If you remove query parameters from the redirect URL but want to achieve the same effect, use a `state` parameter in the TikTok account holder authorization URL. This involves encoding the query parameters as a JSON object and including them as the value of the `state` parameter in the authorization URL. Here are some examples of how to encode query parameters as a state parameter:

**Example 1**:
- Original TikTok account holder redirect URL: `https://www.tiktok.com/photo?source=tiktok`
- Query parameters: `?source=tiktok`
- Encoded `state` parameter to be added into the TikTok account holder authorization URL: `&state={"source":"tiktok"}`

**Example 2**:
- Original URL TikTok account holder redirect URL: `https://www.tiktok.com/photo?source=tiktok&platform=web`
- Query parameters: `?source=tiktok&platform=web`
- Encoded `state` parameter to be added into the TikTok account holder authorization URL: `&state={"source":"tiktok","platform":"web"}`

(3) Once you have received an authorization code in the redirect URL, extract the query parameter values from the `state` parameter in the TikTok account holder authorization URL. This involves decoding the JSON-encoded string from the `state` parameter into a dictionary or object, and then accessing the values of specific keys within that dictionary. 

Here's an example of how to decode the `state` parameter in Python:

```xcodeblock
(code curl http)
import json
from urllib.parse import unquote

state_value = '%7B%22source%22%3A%22tiktok%22%7D'
state = unquote(state_value)
state_dict = json.loads(state)
source = state_dict.get('source')
print(state_dict)

# Output: {'source': 'tiktok'}
(/code)
```


