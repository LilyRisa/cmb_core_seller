---
title: "Authorization"
doc_id: 1832184159540418
path: "Business Messaging API / Guides / Get started / Authorization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Authorization

Before you use the Business Messaging API, you (the developer) need to obtain authorization from the business to manage their TikTok Business Accounts.

## Prerequisites
- You've created a TikTok For Business account. For details, see [Create a TikTok For Business account](https://business-api.tiktok.com/portal/docs?id=1738855099573250).
- You've registered as a developer. For details, see [Register as developer](https://business-api.tiktok.com/portal/docs?id=1738855176671234).
- You've created a developer app. For details, see [Create developer app](https://business-api.tiktok.com/portal/docs?id=1738855242728450).
- You've uploaded an App logo that users will see during authorization to ensure a smooth authorization process.
  - If you haven't uploaded an App logo image, you'll need to upload a JPG, JPEG, or PNG image that's no larger than 512 * 512 pixels in size.
  
<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/55903f89bc0dd3f4c3860cfefde8e6b5" alt="App logo" width="60%" style="color:#808080" />

  - If you forget to upload the logo image, users will see an error page when they try to authorize the app. Here's an example of what the error page might look like:
  
<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a7577753ab342a7206e601dcf2ca96ed" alt="error page" width="30%" style="color:#808080" />

- Your developer app’s TikTok account holder redirect URL is properly configured to receive the authorization code during the authorization process. For details, see [TikTok account holder redirect URL configuration](https://business-api.tiktok.com/portal/docs?id=1832209711206401).
- You [have obtained access to Business Messaging API](https://business-api.tiktok.com/portal/docs?id=1832184145137922).


## Workflow

### Step 1: Share authorization URL with the business
Share the TikTok account holder authorization URL with the business (the TikTok Business Account user). Ensure that you have selected the **Business Messaging** permission for your developer app.

To find the URL, navigate to [My Apps](https://ads.tiktok.com/marketing_api/apps/) > **App Detail** > **Basic Information** > **TikTok account holder authorization URL**.

> <span style="color:DodgerBlue">**Note**</span><br>
> For enhanced security, you can include a unique state query parameter in the authorization URL to mitigate CSRF attacks. This parameter will be echoed back to your application as a query parameter upon user redirect.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/46e5d0ec97e99b4aed99dc30f45a5ce8" alt="TikTok account holder authorization URL" width="60%" style="color:#808080" />

If you cannot find the TikTok account holder authorization URL under [My Apps](https://ads.tiktok.com/marketing_api/apps/) > **App Detail** > **Basic Information**, navigate to **Authorization** > **Scope of permission**. Make sure that you have selected the "TikTok Accounts" permission for your developer App.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/9a9265a43c710f839cdc657386ec14ab" alt="TikTok Accounts permission" width="60%" style="color:#808080" />

### Step 2: Business grants authorization
When the business opens the authorization URL, they will be prompted to authorize your developer app. Make sure that the business authorizes the following permissions before you can make Business Messaging API calls on behalf of them:
- **Send messages or interactions to other accounts on your behalf**
- **Read messages in your inbox (including direct messages)**
- **Read your TikTok account type**
- **Read and manage messages in your inbox (including direct messages)**

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/257d0b8ff62e8f8e11193998cdc4476a" alt="Business grants authorization" width="60%" style="color:#808080" />


- After clicking **Authorize**, the business will be redirected to the **TikTok account holder redirect URL** of your developer app. The redirect URL is specified under [My Apps](https://ads.tiktok.com/marketing_api/apps/) > **App Detail** > **Basic Information** > **TikTok account holder redirect URLs**. 
- The redirect URL will include a `code=<param>`, which is the `auth_code` required to subsequently generate an access token for API access through [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token).

> <span style="color:DodgerBlue">**Note**</span><br>
> - If the TikTok account user has previously authorized the developer app for the same permissions, the permission scope review and approval page will be skipped. Instead, the TikTok account user will be directly redirected to the redirect URL without `auth_code` appended. 
> - To disable the automatic redirection mechanism for TikTok account users who have authorized, manually append `&disable_auto_auth=1` to the TikTok account holder authorization URL before sharing the URL with the TikTok account user.<br>For instance, if the original authorization URL is <code>https://www.tiktok.com/v2/auth/authorize?{parameters}</code>, the developer needs to share <code>https://www.tiktok.com/v2/auth/authorize?{parameters}&disable_auto_auth=1</code>. 

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/87722bd864015784c274974bb6c72d81" alt="TikTok account holder redirect URLs" width="60%" style="color:#808080" />

## Next step
[Subscribe to Business Messaging Webhook events via Webhooks API](https://business-api.tiktok.com/portal/docs?id=1832190670631937)
