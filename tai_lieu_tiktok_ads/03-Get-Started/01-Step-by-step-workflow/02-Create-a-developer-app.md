---
title: "Create a developer app"
doc_id: 1738855242728450
path: "Get Started / Step-by-step workflow / Create a developer app"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a developer app

Before you can start integration, you need to create a developer app. Complete the following steps to create a developer app.

> <span style="color: DodgerBlue">**Note**</span><br>Before you apply for a developer app, ensure that your developer profile has been approved. Otherwise, an error will occur. See [here](https://business-api.tiktok.com/portal/docs?id=1738855176671234) for more information about registration.

# Steps

1. Log in to TikTok For Business Developers portal and click **Create an App**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e0d90552f04f9d372ffb81f2e4beff72" width="60%" style="color:#808080"/>

2. Specify a descriptive name for your application. For example, "Video Upload Tool", "TikTok Media Buy Platform", etc.
> <span style="color: DodgerBlue">**Note**</span><br>This is the name of your developer app, rather than the name of a mobile app. If you integrate API to manage TikTok ads promoting multiple mobile apps, you can manage within one developer app, and don't have to create multiple developer apps for them.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4a090814befaaf3db3ffc36e358ecc6a" width="60%" style="color:#808080"/>

3. Under **App description**, provide information about the intended uses of the application, as well as any advertisers that you may be working with. We recommend that your information is in the format below:
	-  **Intended Uses**: Describe the key intended functions and usage scenarios of the developer App. Explain what core permissions are technically required to enable the main functions.
	- **Developer App Access Controls**: Clarify whether the developer App access is limited only to internal organizational use, or shared with other external accounts or organizations as well.<br>

	Here is an example for your reference: 
	<br>This developer app serves as our in-house TikTok ad buying tool, which will be used by our user acquisition team. By integrating TikTok's APIs for ad creation, creative upload and reporting, this app improves the efficiency of ad operations.

4. Specify an **advertiser redirect URL**.
	After the advertiser approves the authorization, they will be redirected to the redirect URL that is configured here. The authentication code will be appended to the redirect URL. Ensure your URL is valid.
	> <span style="color: DodgerBlue">**Note**</span><br>You can configure up to 10 advertiser redirect URLs, including localhost. Once a new redirect URL is added, you can generate a new advertiser authorization URL. 

5. Under **Scope of permission**, select the permissions that you'll need in your application. 
	The permissions determine the API endpoints you have access to. Ensure that your selected scope of permission is relevant to your app description. You can find the **scope of permission** of every endpoint in the last column of the [List of available endpoints](https://business-api.tiktok.com/portal/docs?id=1735713875563521) table. 
> <span style="color:DodgerBlue">**Note**</span><br>
> To ensure proper use of the Accounts API, starting March 20, 2026 at 00:00 (GMT+0), developers must complete the [Accounts API Access Application Form](https://bytedance.sg.larkoffice.com/share/base/form/shrlgu4WEvtSXpEDLcCw56u4Rfc) before submitting a new developer app or requesting a scope increase that includes the "TikTok Accounts" permission scope. Please follow the instructions carefully and provide clear, accurate information to help facilitate the application review process. For more details on Accounts API use cases, please refer to the [Accounts API](https://business-api.tiktok.com/portal/docs?id=1737944384433218) documentation.

6. After you select all permissions that you need, click **Confirm**. 

7. You have now submitted your developer application for review. The review may take **2 to 3 business days**. 
- To check the status of your Application, click the red button on the top right, navigate to **My Apps** and check under **Verification Status**. If your application is rejected, you can edit the request and submit it again. Once the app is approved, you can use the authorization code to get a long-term access token.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b07963fb42c045cd07175dc39553628d" width="60%" style="color:#808080"/>

- If you need to create more than one developer application, you can navigate to **My Apps** and click **Create App**. Each developer can have up to five developer applications. 

# Next step
[Authorization](https://business-api.tiktok.com/portal/docs?id=1781891256601602)



