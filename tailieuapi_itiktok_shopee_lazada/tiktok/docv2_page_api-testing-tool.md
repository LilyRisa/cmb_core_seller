# API testing tool

> Source: https://partner.tiktokshop.com/docv2/page/api-testing-tool
> Section: Developer Guide
> Scraped: 2026-05-21T00:24:37.881Z

---

# Latest Features

| Estimated Launch Time | Description |
| --- | --- |
| 
8/22/2023

 | 

1\. Support selecting API version for testing.  
2\. Support platform key to call all read (GET) interfaces.  
3\. Keep the store authorization restrictions of the app consistent with the online store (i.e., if your app only has a certificate for the UK, it cannot authorize stores in other regions).  
4\. Skip regional restriction verification for sandbox stores and allow authorization.

 |

# What is API Testing Tool?

The API Testing Tool enables developers to efficiently access the exact request and response parameters directly on Partner Center.  
Developers can use the API Testing Tool to efficiently test APIs

-   Simplifies the calling of API request and response parameters.
-   Streamlines the testing and code-writing process by offering sample code for direct use.
-   Serves as a reliable means to test API responses. You can use the response as a guide to resolve development issues.

# API Testing Tool Entry Points

There are two entry points to API Testing Tool on Partner Center:

-   Go to **Partner Console >> Development Kits >> API Testing Tool**
-   Go to **Partner Center >> Development >> API Testing Tool**

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/d3684103477c4bb3a3d9f457c315ea70~tplv-k9wyc2ijk0-image.image)

# Using the API Testing Tool to test APIs

To test APIs using the API Testing Tool, you can use an app key provided by our platform, or use the app key of your app.

## Using platform app key

The platform provides developers who have not yet registered an opportunity to try our APIs without an app key. Using the platform app key, developers can test the basic API capabilities provided by the platform.  
💡**The app key provided by platform only supports calling GET method APIs. Using the platform app key to call API, the API Testing Tool will not provide a demo of the request.**  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/9e21eb8c8dd44ef9975d674b9e4a1402~tplv-k9wyc2ijk0-image.image)

## Using Developer's Own App Key

After logging in, developers can choose to use an **App Key** from an app they created to test APIs.

> ⚠️ **Important**: Before calling any API, you **must configure the API Scope** for your App Key and complete the authorization process. Only apps with the appropriate API access permissions can successfully call the API.

### Steps to Use Your App Key

1.  Log in to the [TikTok Shop Partner Center Console](https://partner-sso.tiktok.com/account/login).
2.  Go to **App & Service** and select your app.
3.  In the **API Management** section, configure the required **API Scope**.
4.  Complete the authorization process, so your App Key has access to the corresponding APIs.
5.  Select the API you want to test and use your App Key to make requests.

Only after completing these steps will your App Key be able to successfully call the corresponding APIs.  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/c2371170c1bd43448520bf229e02d2a4~tplv-k9wyc2ijk0-image.image)  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/738b36d9fe5742bc9973332a97eafe9e~tplv-k9wyc2ijk0-image.image)  
The developer's own app key needs to get shop authorization when using the testing tool, either by using the authorization function provided by the tool or by following the platform [Authorization Guide](authorization-guide-202309) to get the access\_token and backfill it to the test tool.  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/1fda59c4387849929800a303c123519f~tplv-k9wyc2ijk0-image.image)

# Tips for using the API Testing Tool

| Tip | Description | Demo |
| --- | --- | --- |
| 
Document guide function

 | 

API documentation guidance has also been done in the API testing tool.

 | 

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/37b9f831e52344e68d3f7f0993e0d9d5~tplv-k9wyc2ijk0-image.image)  
Documentation guide when selecting API  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/4ce09024c7d44e1f95e138e720167200~tplv-k9wyc2ijk0-image.image)  
API error documentation guide

 |
| 

Using sample code

 | 

When using the developer's own app key, you can directly copy the request demo (curl) generated in the API test tool and send the request on the terminal.

 | 

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/e3a14a2ac2c94a599a3aded5951b2dfa~tplv-k9wyc2ijk0-image.image)  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/b9bfe8f266684c0fbabff04531123b7e~tplv-k9wyc2ijk0-image.image)

 |
