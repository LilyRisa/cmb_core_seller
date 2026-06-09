---
title: "Authorization"
doc_id: 1738373141733378
path: "Marketing API / Get started / Authorization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Authorization

Before you use the Marketing API, you (the developer) need to first get authorization from the advertiser to manage their accounts.

# Prerequisites
* You've created a TikTok For Business account. For details, see [Create a TikTok For Business account](https://ads.tiktok.com/marketing_api/docs?id=1738855099573250).
* You've registered as a developer. For details, see [Register as developer](https://ads.tiktok.com/marketing_api/docs?id=1738855176671234). 
* You've created a developer app. For details, see [Create a developer app](https://ads.tiktok.com/marketing_api/docs?id=1738855242728450).  

# Steps
1. On TikTok API For Business portal, navigate to **My Apps**, and click the app that you want to request authorization for. In the **Basic Information** section, find the **Advertiser authorization URL**. Send the URL to your advertiser so that they can approve the authorization. 
> <span style="color: DodgerBlue">**Note**</span><br>
> You can configure up to 10 advertiser redirect URLs,  including localhost. Once a new redirect URL is added, you can generate a new advertiser authorization URL. 

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f442994a9c7d7131b3b9998713f3ab39" alt="Advertiser authorization URL" width="60%" style="color:#808080" />

2. The advertiser enters the authorization URL in the browser. They will then be navigated to a list of permissions that you selected when creating your developer application.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2e717d029985805449eed98836e6e55a" alt="Permission page" width="60%" style="color:#808080"/>

3. The advertiser reviews the permissions, agrees to the Platform Service Agreement, and clicks **Confirm**. 

4. The advertiser clicks **Send Code** to receive a verification notification sent to the email address associated with their ad account.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d8024681df2ca9d4cd8cacf1b52f0521" alt="Send verification code" width="60%" style="color:#808080"/>

5. The advertiser verifies their identity by entering the verification code and clicking **Confirm**.



> <span style="color: DodgerBlue">**Note**</span><br>
> - The verification for an ad account that has previously authorized a developer app remains valid for 48 hours. Within this window, repeated authorization requests from the same developer app do not require additional verification. 
> - However, if the ad account attempts to authorize a different developer app, a new verification process will be necessary, regardless of any recent verifications related to another developer app.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/825b0ca91f6bfec4709238515ed0f950" alt="Verify identity" width="60%" style="color:#808080"/>

    
> <span style="color: DodgerBlue">**Note**</span><br>In situations where the system identifies your request as abnormal, a toast message signifying the failure of authorization will appear. Please try again later. If you believe your request should have been valid, feel free to submit a ticket via the [Developer Ticket Platform](https://ads.tiktok.com/athena/requester/boards?identify_key=b25033ef7782d41050cbac74f751d547e9d8ad35a39a541a0f0efbbd51fe722f) for further examination.

6. Once the verification is completed, the advertiser will then be redirected to the redirect URL that you specified when creating the developer application. The authorization code is appended to the redirect URL. 
	 
Example: `https://business-api.tiktok.com/portal/docs？state=your_custom_params&code=3c6dc21d2db289199737bcb8c006c23aaf000a1e&auth_code=1234c21d2db289199737bcb8c006c23aaf000a1e&id=1701890905779201`
	
7. The developer should implement a server-side endpoint that matches the specified `redirect_uri`. This endpoint will receive the authorization code (`auth_code`) from the query parameters of the redirect URL. <br><br>For example, if the redirect URL is `https://business-api.tiktok.com/portal/docs？state=your_custom_params&code=3c6dc21d2db289199737bcb8c006c23aaf000a1e&auth_code=1234c21d2db289199737bcb8c006c23aaf000a1e&id=1701890905779201`, then your endpoint should be configured to extract the authorization code from the incoming request. <br><br>In this case, the `code` parameter is `1234c21d2db289199737bcb8c006c23aaf000a1e`. Your server-side logic should extract this parameter and securely store or process it to exchange it for an access token.

8. With the `auth_code`, the developer can make a POST request to the [/oauth2/access_token/](https://ads.tiktok.com/marketing_api/docs?id=1739965703387137) endpoint to get a long-term access token. The developer also needs to supply the `app_id` and `secret` of your developer application in the request.

> <span style="color: DarkRed">**Important**</span><br>
> `auth_code` is only valid for one hour and can be used only once. After the `auth_code` expires, you need to start over and perform the authorization steps again.

# Next step
[Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162)

# FAQs/Common error messages
See [Authorization-FAQs](https://ads.tiktok.com/marketing_api/docs?id=1766037914914818&rid=uz955smt6w) for details.
