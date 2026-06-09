---
title: "Getting started"
doc_id: 1862119945765889
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Getting started"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Getting started

The following is a summary of steps required to install and configure the App Events SDK:
1. Add the SDK as a build dependency
2. Configure and initialize the SDK
3. Configure event logging and reporting
4. Enable Advanced Matching (optional)
5. Verify your integration
6. Compile and release your app

Before you start, you must:
1. Add the SDK as a data connection method for your app in Events Manager, and
2. Generate your TikTok App ID and SDK App Secret
## If your app is already created in Events Manager
### Access your app in Events Manager and enable the SDK connection
Select the app that you want to add SDK connection from existing data source, go to the **Settings** tab and choose SDK.

![Events Manager Settings tab](https://images.ctfassets.net/5jqet0ab9qci/1Vp53BzeaJBnaCKIpGdpXL/0dc6963493df9d7dad8965a4401dcb3a/Events_Manager_Settings_tab.png)

### Generate your TikTok App ID and App Secret
In the **Overview** tab: 
- Note down your **app ID**, and copy your **TikTok App ID**. Both are required for integration.
- Your **app ID** will be in either reverse DNS format (Android), or short numerical string (iOS) typically present in app store/play store URLs. Your **TikTok App ID** is a longer numerical string, and unique to your ad account.

![Obtain TikTok App ID](https://images.ctfassets.net/5jqet0ab9qci/5Ipa4i3kiSJ3R99778k1su/0533d663a16fb9d5a5cf4646d28683a0/Obtain_TikTok_App_ID.png)

**In the Settings tab:**
- Generate and note down your **App Secret**. This will be used during integration.

![Events Manager App Secret](https://images.ctfassets.net/5jqet0ab9qci/2dFnYBgXOsdOzblfmdCUrw/6f452a4335cf558967a675a29c3a4fad/Events_Manager_App_Secret.png)



## If your app has not yet been created in Events Manager
### Access Events Manager
To get started with app creation, navigate to **Tools** ➤ **Events Manager** in your TikTok Ads Manager account.

![Tools ➤ Events Manager](https://images.ctfassets.net/5jqet0ab9qci/6Ujf7d7Vgh3c0Y8imkzC8P/48f578efd1316760e8124cd967679cf1/Tools_â___Events_Manager.png)


![Welcome to Events Manager](https://images.ctfassets.net/5jqet0ab9qci/42A14DgHVLqQaP4tykXpkp/fb57a4f3f71b72baf4520f7fce067549/Welcome_to_Events_Manager.png)

### Create your app
- If you do not already have a data connection set up, select **"Get started"**.
- If you already have other data connections set up, select **"Connect data source"**.

![Events Manager Get started](https://images.ctfassets.net/5jqet0ab9qci/5OnF7y5FLeLLx14rrtEjhk/eda1f4338c60f7880cfb02ac2ed6d543/Events_Manager_Get_started.png)


![Events Manager Connect data source](https://images.ctfassets.net/5jqet0ab9qci/63J8BilaqBxmsGwaF4IbQ1/519feba9549b803836442270576a1438/Events_Manager_Connect_data_source.png)

- Select **"App"** and proceed.

![Events Manager Select App](https://images.ctfassets.net/5jqet0ab9qci/4QMAjywMHlWB2WKKnlUEMk/2788dcc7c65a7b65ae29bdfeb889ae7a/Events_Manager_Select_App.png)



- Select SDK as the signal partner.

![Select SDK as the signal partner](https://images.ctfassets.net/5jqet0ab9qci/6tiNYHNzPCim9uF450ktCC/e83c3c247602123d41d8ecf343a178a6/Select_SDK_as_the_signal_partner.png)



- Add your app by inputting the marketplace download URL.

![Input the marketplace download URL.](https://images.ctfassets.net/5jqet0ab9qci/3Ut0ihqKOW1edyZZblrEv0/f7766eb0923c14e2cf70dba4d1c5f1f1/Input_the_marketplace_download_URL..png)



- Integrate SDK to your app project code copy, or do it manually.

![Integrate SDK to your app project code copy](https://images.ctfassets.net/5jqet0ab9qci/3481AKDrc9Sl8DCCTb2otT/e3b9f835ced05d632b13e598e2251814/Integrate_SDK_to_your_app_project_code_copy.png)



- Note down your **TikTok App ID** and **App Secret** to initialize the SDK in your app.

![Note down your TikTok App ID and App Secret](https://images.ctfassets.net/5jqet0ab9qci/56bjPD0LtukZXlKIcAYyCC/d8f3f15298f2dd4f19921dcc97d3283a/Note_down_your_TikTok_App_ID_and_App_Secret.png)





