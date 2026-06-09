---
title: "Authorization"
doc_id: 1825103730490434
path: "Organic API / Mentions API / Guides / Get started / Authorization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Authorization

Before you use the Accounts API, you (the developer) need to first get authorization from the business to manage their accounts.  

# Prerequisites
* You've created a TikTok For Business account. For details, see [Create a TikTok For Business account](https://business-api.tiktok.com/portal/docs?id=1738855099573250).
* You've registered as a developer. For details, see [Register as developer](https://business-api.tiktok.com/portal/docs?id=1738855176671234). 
* You've created a developer app. For details, see [Create developer app](https://business-api.tiktok.com/portal/docs?id=1738855242728450).  

 
# Workflow 

## Step 1: Share authorization URL with business
The developer shares the authorization URL v2 with the TikTok Business Account user (the business). You can find the URL by navigating to **[My Apps](https://ads.tiktok.com/marketing_api/apps/)** > **App Detail** > **Basic Information** > **TikTok account holder authorization URL**.

<br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/13a8910ed4438d7183b243a399fc5016" alt="TikTok account holder authorization URL" width="80%" style="color:#808080" />

To ensure that users can authorize the app without any errors, it's important to upload an **App logo** image that users will see during the authorization process. If you haven't uploaded an App logo image, you'll need to upload a JPG, JPEG, or PNG image that's no larger than 512 * 512px in size.

<br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/402a97ec04e1a5c4f8861c9e155c68cb" alt="App logo" width="60%" style="color:#808080" />

If you forget to upload the logo image, users will see an error page when they try to authorize the app. Here's an example of what the error page might look like:

<br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/55c5e18e263e9dd25edb9209b7143342" alt="Error page" width="40%" style="color:#808080" />

Note that if you cannot find the **TikTok account holder authorization URL** under  **[My Apps](https://ads.tiktok.com/marketing_api/apps/)** > **App Detail** > **Basic Information** , navigate to **Authorization** > **Scope of permission**. Make sure that you have selected the "TikTok Accounts" permission for your developer App.

<br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2c06bcd2811d3e521fe7b56501636957" alt="TikTok Accounts permission" width="60%" style="color:#808080" />

> <span style="color: dodgerblue">**Note**</span><br>For security reasons, you can add a unique `state` query parameter to the authorization URL as a mechanism to mitigate CSRF attacks. It will be echoed back to your application as a query parameter upon user redirect.

**Formatting rules for redirect URL**

> <span style="color:DodgerBlue">**Note**</span><br>
> You can configure up to 10 TikTok account holder redirect URLs per developer app. Each activated TikTok account holder redirect URL automatically generates a new TikTok account holder authorization URL. The system maintains one active URL at a time, marked in red. When only one TikTok account holder redirect URL exists, it becomes active by default. For multiple redirect URLs, activate a different one by clicking your desired URL.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/574c90494cc6c87c8b66a1c49f98b69b" alt="Activated and deactivated TikTok account holder redirect URLs" width="80%" style="color:#808080" />

When defining a **TikTok account holder redirect URL** for your application, follow these formatting rules to ensure proper functionality:
```xtable
| No. {7%} | Rules {24%}| Correct example {32%}| Bad example{37%} |
|---|---|---|---|
| 1 | URLs must be absolute and end with `/`. | `https://dev.example.com/auth/callback/` | <ul><li> `https://dev.example.com/auth/callback` <ul><li> missing trailing `/`</li></ul></li><li> `/auth/callback/` <ul><li> missing scheme and domain </li></ul></li></ul>|
| 2 | URLs should be static, while parameters are denied and should be ignored. | `https://dev.example.com/auth/callback/` | <ul><li>`https://dev.example.com/auth/callback/?id=1`<ul><li> contains query parameter `?id=1`</li></ul></li></ul>|
| 3 | URLs cannot include anchors `#`. | `https://dev.example.com/auth/callback/` | <ul><li>`https://dev.example.com/auth/callback/#1` <ul><li> contains anchor `#1`</li></ul></li></ul>|
| 4 | URLs must start with `https://`. | `https://dev.example.com/auth/callback/` | <ul><li>`http://dev.example.com/auth/callback/` <ul><li> uses `http` instead of `https` </li></ul></li></ul>|
| 5 | URLs cannot include ports. | `https://dev.example.com/auth/callback/` | <ul><li>` https://dev.example.com:3000/auth/callback/` <ul><li> contains port number `:3000`  </li></ul></li></ul> |
| 6 |The length of the registered URI should fall within the range of 10 to 512 characters. | N/A| N/A|
```

Otherwise, you will receive the error message as follows. You can edit your redirect URL accordingly. 

<br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d28b609bd0c0ee9ccdc47ef9cbac4feb" alt="Error message for redirect URLs" width="80%" style="color:#808080"/>

**Migration instructions for redirect URL**

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
## Step 2: Business grants the authorization

When opening the authorization URL, the business will be asked to authorize your developer app. After clicking the **Authorize** button, the business will be directed to the redirect URL of your developer application. The redirect URL will include a `code=<param>`, which will be the `auth_code` parameter required for getting the access token. The application's redirect URL is specified under **[My Apps](https://ads.tiktok.com/marketing_api/apps/)** > **App Detail** > **Basic Information** > **TikTok account holder redirect URLs**.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/13a8910ed4438d7183b243a399fc5016" alt="TikTok account holder redirect URLs" width="80%" style="color:#808080" />

Business authorization UI:

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/175a299109431454e439ea01c2e9c26d" alt="Business authorization UI" width="40%" style="color:#808080" />

This authorization code is necessary to subsequently generate an access token for API access through [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1825105003772930#item-link-Obtain%20an%20access%20token).

