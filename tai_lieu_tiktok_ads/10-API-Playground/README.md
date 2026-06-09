---
title: "API Playground"
doc_id: 1826537506816001
path: "API Playground"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# API Playground

The API Playground provides a streamlined environment for testing API endpoints and validating payloads. This interactive tool helps developers verify API behavior in real-time, enabling seamless integration.

> <span style="color:DodgerBlue">**Note**</span><br>
> The API Playground currently offers support for a limited selection of Marketing API endpoints. For a list of available endpoints, see [Available endpoints in API Playground](https://business-api.tiktok.com/portal/docs?id=1826537624043586).

# Work with API Playground
## Prerequisites
- You've created a TikTok For Business account. For details, see [Create a TikTok For Business account](https://business-api.tiktok.com/portal/docs?id=1738855099573250).
- You've registered as a developer. For details, see [Register as developer](https://business-api.tiktok.com/portal/docs?id=1738855176671234).
- You've created a developer app. For details, see [Create a developer app](https://business-api.tiktok.com/portal/docs?id=1738855242728450).
- You've completed Marketing API authorization. For details, see [Authorization](https://business-api.tiktok.com/portal/docs?id=1738373141733378).
- To test payloads for different endpoints, you need relevant permissions. See [API Reference](https://business-api.tiktok.com/portal/docs?id=1735713875563521) to find out permissions required for endpoints and see [Update app permissions](https://business-api.tiktok.com/portal/docs?id=1738855280338946) to find out how to configure permissions.

## Steps

### 1. Access the API Playground
Navigate directly to the [API Playground](https://business-api.tiktok.com/portal/api-playground). Note that login is required to access this tool.

### 2. Generate a Marketing API access token
The default testing page for [/oauth2/access_token/](https://business-api.tiktok.com/portal/docs?id=1739965703387137) will be displayed.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b2d191d0e44f457f315ed2041407ec6f" alt="default testing page" width="60%" style="color:#808080" />

i. Under the **Request payload** tab, enter the `app_id`, `auth_code`, and `secret` as obtained from [Authorization](https://business-api.tiktok.com/portal/docs?id=1738373141733378) steps.

ii. Click **Submit**. The **Response body** tab will now display the `access_token` necessary for calling Marketing API endpoints.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0ccb035eeff8b532a2bb60930c75c2ac" alt="Get the access_token" width="60%" style="color:#808080" />


### 3. Test additional Marketing API endpoints
i. Use the search bar to locate specific endpoints by API name or path, such as `/v1.3/bc/get/`.

ii. Click **API Doc** to access the complete schema for the selected endpoint.
<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/976b964841071dcfe88adf84e85c26da" alt="Access the complete schema" width="60%" style="color:#808080" />

iii. If necessary, enter the access token obtained from <a href="#item-link-2. Generate a Marketing API access token">Step 2</a> under the **Request Header** tab.

> <span style="color:DodgerBlue">**Note**</span><br>
> Once you enter the access token on the testing page for a particular endpoint, the **Access Token** field across all other endpoint pages will automatically populate with the same token until you refresh the pages. This feature can help you test multiple endpoints efficiently.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3630a0079f8a39ac5fb1b0b49846573b" alt="Enter the access token" width="60%" style="color:#808080" />

iv. Under the **Request payload** tab, customize the payload based on your needs. 

For instance, if you want to retrieve all the Business Centers that you can access, remove `bc_id` from the payload.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8bf6ef9c631c600371c0d5b571ee460d" alt="Customize the payload" width="60%" style="color:#808080" />

v. Click **Submit**. You can now see the response under the **Request body** tab.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8493aeca8624867b87d43a951b760ee0" alt="See the response" width="60%" style="color:#808080" />


### 4. Copy the code example
i. On the top right corner of the **Code example** tab, click the copy button to obtain the code example in curl format.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/82ac75aa330d49ed8d196d7cdb48d9ef" alt="Copy the code example" width="60%" style="color:#808080" />

ii. Import the code examples into your integration tools for further usage.
