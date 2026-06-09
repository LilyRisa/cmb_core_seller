---
title: "Get authorization from Creators"
doc_id: 1768506960432130
path: "Organic API / TikTok One API / Guides / Get authorization from Creators"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get authorization from Creators

# Steps

1. Share **TikTok account holder authorization URL** with the Creator.
2. Creator authorizes your developer App.
3. Make an API call to get an access token.
4. When the access token expires, use the refresh token call to get a new access token. The Access Token is valid for 24 hours and the refresh token is valid for one year. 
5. [Optional] Revoke the access token.

## Step 1: Share authorization URL with creator

Locate your Developer App's **TikTok account holder authorization URL** and share it with the Creator. The URL can be found by navigating to [**My Apps**](https://ads.tiktok.com/marketing_api/apps/) > **App Detail** > **Basic Information** > **TikTok account holder authorization URL**.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/13a8910ed4438d7183b243a399fc5016" width="80%" style="color:#808080"/>

To ensure that users can authorize the app without any errors, it's important to upload an **App logo** image that users will see during the authorization process. If you haven't uploaded an App logo image, you'll need to upload a JPG, JPEG, or PNG image that's no larger than 512 * 512px in size.
<br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/402a97ec04e1a5c4f8861c9e155c68cb" width="40%"/>

If you forget to upload the logo image, users will see an error page when they try to authorize the app. Here's an example of what the error page might look like:
<br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/55c5e18e263e9dd25edb9209b7143342" width="40%"/>

### Formatting rules for redirect URL
> <span style="color:DodgerBlue">**Note**</span><br>
> You can configure up to 10 TikTok account holder redirect URLs per developer app. Each activated TikTok account holder redirect URL automatically generates a new TikTok account holder authorization URL. The system maintains one active URL at a time, marked in red. When only one TikTok account holder redirect URL exists, it becomes active by default. For multiple redirect URLs, activate a different one by clicking your desired URL.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/574c90494cc6c87c8b66a1c49f98b69b" alt="Alternative Text" width="60%" style="color:#808080" />

When defining a **TikTok account holder redirect URL** for your application, follow these formatting rules to ensure proper functionality:
```xtable
| No. {7%} | Rules {24%}| Correct example {32%}| Bad example{37%} |
|---|---|---|---|
| 1 | URLs must be absolute and end with `/`. | `https://dev.example.com/auth/callback/` | <ul><li> `https://dev.example.com/auth/callback` <ul><li> missing trailing `/`</li></ul></li><li> `/auth/callback/` <ul><li> missing scheme and domain </li></ul></li></ul>|
| 2 | URLs should be static, while parameters are denied and should be ignored. | `https://dev.example.com/auth/callback/` | <ul><li>`https://dev.example.com/auth/callback/?id=1`<ul><li> contains query parameter `?id=1`</li></ul></li></ul>|
| 3 | URLs cannot include anchors `#`. | `https://dev.example.com/auth/callback/` | <ul><li>`https://dev.example.com/auth/callback/#1` <ul><li> contains anchor `#1`</li></ul></li></ul>|
| 4 | URLs must start with `https://`. | `https://dev.example.com/auth/callback/` | <ul><li>`http://dev.example.com/auth/callback/` <ul><li> uses `http` instead of `https` </li></ul></li></ul>|
| 5 | URLs cannot include ports. | https://dev.example.com/auth/callback/ | <ul><li>` https://dev.example.com:3000/auth/callback/` <ul><li> contains port number `:3000`  </li></ul></li></ul> |
| 6 |The length of the registered URI should fall within the range of 10 to 512 characters.| N/A| N/A|
```

Otherwise, you will receive the error message as follows. You can edit your redirect URL accordingly. 
<br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d28b609bd0c0ee9ccdc47ef9cbac4feb" width="80%" style="color:#808080"/>


## Step 2: Creator grants authorization

When opening the authorization URL, the creator will be asked to authorize your developer app. After clicking the **Authorize** button, the creator will be directed to the redirect URL of your developer application. The redirect URL will now include a `code= <param>`, which will be the `auth_code` parameter required for getting the access token. 

> <span style="color: DarkRed">**Important**</span><br>
> `auth_code` is only valid for 10 minutes and can be used only once. After the `auth_code` expires, you need to start over and perform the authorization steps again.

During authorization, please ensure the creator approves the following permissions:
- biz.creator.insights
- user.insights.creator
- biz.creator.info

Creator authorization UI:

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/175a299109431454e439ea01c2e9c26d" width="40%" style="color:#808080"/>

## Step 3: Get the access token

Call the [/tt_user/oauth2/token/](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1768493902119937#item-link-Get%20an%20access%20token) endpoint. Set the `grant_type` to `authorization_code`, and specify `auth_code` and `redirect_uri`. The ID and secret of your developer application can be found on the app settings page, above where you can find the TikTok Creator Authorization URL.  

### Example
``` xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tt_user/oauth2/token/' \
--header 'Content-Type: application/json' \
--data-raw '{
 "grant_type" : "authorization_code",
 "auth_code" : "{{auth_code}}",
 "client_secret": "{{client_secret}}",
 "client_id": "{{client_id}}",
 "redirect_uri": "{{redirect_url}}"
}
'
(/code)
```

The API response will include the following information. Please ensure that you save the `access_token`, `open_id` and `refresh_token`.
- `access_token`
- `scope`
- `expires_in` 
- `refresh_token`
- `refresh_token_expires_in`
- `open_id`

## Step 4: Renew the access token

Call the [/tt_user/oauth2/refresh_token/](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1768493902119937#item-link-Renew%20an%20access%20token) endpoint. Use the `refresh_token` you get from Step 3  to get a new access token. 

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tt_user/oauth2/refresh_token/' \
--header 'Content-Type: application/json' \
--data-raw '{
 "grant_type" : "refresh_token",
 "refresh_token" : "{{refresh_token}}",
 "client_secret": "{{client_secret}}",
 "client_id": "{{client_id}}"
}
'
(/code)
```

## Step 5: [Optional] Revoke the access token
If you want to revoke an access token, call the [/tt_user/oauth2/revoke/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Revoke%20a%20Creator%20access%20token) endpoint. 

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tt_user/oauth2/revoke/' \
--header 'Content-Type: application/json' \
--data '{
    "client_id":"{{client_id}}",
    "client_secret":"{{client_secret}}",
    "access_token":"{{access_token}}"
}'
(/code)
```


