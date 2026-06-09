---
title: "Instant Page Editor SDK"
doc_id: 1740307202170881
path: "Marketing API / Creatives / Guides / Creative tools / Instant Page Editor SDK"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Instant Page Editor SDK

The TikTok Instant Page (TIP) Editor SDK is a lightweight tool that allows you to create various types of [instant pages](https://business-api.tiktok.com/portal/docs?id=1739953260466178) by adding a small JavaScript snippet to your HTML page. This SDK doesn't require downloading or installing any packages. You can load the SDK asynchronously, ensuring other page elements are not delayed. Once the SDK is loaded, you can open the TIP Editor pop-up and create Instant Pages directly, eliminating the need to use TikTok Ads Manager for page creation.

Currently, the TIP Editor SDK supports creating the following types of instant pages: Customized Pages, Instant Forms, App Profile Pages, and Pop-up Forms. 
After you create an instant page using the SDK, you can get the page ID of the instant page by using the [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) endpoint. The page ID can be used to create an ad.

> <span style="color:dodgerblue">**Note**</span><br>
> Instant Page Editor does not support Sandbox accounts.


# Create instant pages using TIP Editor SDK
## Prerequisites

You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://business-api.tiktok.com/portal/docs?id=1735713609895937) for details.

## Steps
1. Generate a TIP Editor SDK access token by using [/oauth2/access_token/tip_sdk/create/](https://business-api.tiktok.com/portal/docs?id=1820826755283969). 

Each TIP Editor SDK access token is valid for 24 hours. You can verify whether an SDK token expires using [/oauth2/access_token/tip_sdk/validate/](https://business-api.tiktok.com/portal/docs?id=1820826826000385) and renew the token using [/oauth2/access_token/tip_sdk/renew/](https://business-api.tiktok.com/portal/docs?id=1820826847199234) after the token expires.

2. Initialize and load the SDK.  

Place the following code within the `<body>` tags of your HTML. 
  
Replace `your_appid` with your own App ID. To find the **App ID** of your developer app, navigate to **[My Apps](https://ads.tiktok.com/marketing_api/apps/)** > **App Detail** > **Basic Information**.


```xcodeblock
(code HTML html)
<script>
  // Initialize SDK
  window.InstantPageAsyncInit = (val) => {
    InstantPage.init({
      appId: 'your_appid', //App ID of your developer app
      version: 'v1.0' // Only v1.0 for now
    });
  };
</script>

<script async defer crossorigin="anonymous" src="https://sf-unpkg.tiktokcdn.com/obj/unpkg-va/latest/byted/instant_page_sdk/dist/index.js"></script>
(/code)
```

3. Launch the TIP Editor.
Call the SDK function `InstantPage.start()` at the appropriate time to launch the Instant Page Editor pop-up. The code needs to be in this format: `InstantPage.start(params, callback)`.

**Example**
```xcodeblock
(code JavaScript JS)
InstantPage.start(
  {
    accountId: 'your_adv_id', //advertiser id
    appId: 'appId', //App ID of your developer app
    ixAccessToken: 'your_SDK_token', //TIP Editor SDK access token
    businessType: 1, // Support 1=Instant Form, 4=App Profile Page, 6=Customized Page, 14=pop-up form, default value: 1
    display: 'popup', // Only popup for now
    actionType: 'create', 
    language: 'en' //  Support ar,de,en,es,fr,hi,id,it,ja,ko,ms,ru,th,tr,vi,zh,zh-Hant, default value: en
  },
  (response) => {
    console.log(response);
    // pageId
    // status 
    // accountId
    // ...
  }
);
(/code)
```

The following table outlines the parameters available for the function.

```xtable
| Field {30%}| Type{15%} | Description{55%}  |
| --|---|---|
|accountId {Required}|string| Advertiser ID. |
|appId {Required}|string| ID of your developer application. <br><br>To find the <b>App ID</b>, navigate to <b>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</b> > <b>App Detail</b> > <b>Basic Information</b>.|
|ixAccessToken {Required}|string|TIP Editor SDK access token.<br><br>To obtain a TIP Editor SDK access token, use [/oauth2/access_token/tip_sdk/create/](https://business-api.tiktok.com/portal/docs?id=1820826755283969).|
|actionType {Required}| string | Operation type. <br><br>Enum values: `create`. |
|display  {Required}| string | Display format. <br><br>Currently, the only supported value is `popup`. |
| businessType | number | Page type. <br><br>Enum values: <ul><li>`1`: Instant Form.</li><li>`4`: App Profile Page.</li><li>`6`: Customized TikTok Instant Page. </li><li>`14`: Pop-up Form.</li></ul>Default value: `1`.  |
| language  | string | The language of the Editor. <br><br>Default value: `en`.<br><br>To learn about the supported values, see <a href="#item-link-Supported values for language">Supported values for language</a>.|
```
### Supported values for `language`
```xtable
| Value {30%}| Description {70%} |
|---|---|
| `ar` | Arabic |
| `de` | German |
| `en` | English |
| `es` | Spanish |
| `fr` | French |
| `hi` | Hindi |
| `id` | Indonesian |
| `it` | Italian |
| `ja` | Japanese |
| `ko` | Korean |
| `ms` | Malay |
| `ru` | Russian |
| `th` | Thai |
| `tr` | Turkish |
| `vi` | Vietnamese |
| `zh` | Simplified Chinese |
| `zh-Hant` | Traditional Chinese |
```

The following table outlines the response parameters of the callback function.
```xtable
| Field {30%}| Type{15%} | Description{55%}  |
|---|---|---|
| pageId | string | Page ID. |
| status | number | Status of the instant page.<br><br>Enum value:<ul><li>`2`: Ready.</li></ul>|
| accountId | string | Advertiser ID. |
```

## Example complete HTML code

```xcodeblock
(code HTML html)
<!DOCTYPE html>
<html lang="en">
<head>
    <title>InstantPage SDK Test</title>
</head>
<style>
    .title {
        display: flex;
        justify-content: center;
        width: 100%;
    }
</style>
<body>
<h1>InstantPage SDK Test</h1>

<!-- select page source -->
<div class="title">
    <button style="margin-top: 32px;font-size: 16px" onclick="start()">Start</button>
</div>

<script>
    /// Initialize SDK
    window.InstantPageAsyncInit = (val) => {
        InstantPage.init({
              appId: 'your_appid', //App ID of your developer app
              version: 'v1.0' // Only v1.0 for now
        });
    };

    function start() {
        // Launch the SDK at the appropriate time
        InstantPage.start(
            {
                accountId: 'your_adv_id', //advertiser id
                appId: 'appId', //App ID of your developer app
                ixAccessToken: 'your_SDK_token', //TIP Editor SDK access token
                businessType: 1, // Support 1=Instant Form, 4=App Profile Page, 6=Customized Page, 14=pop-up form, default value: 1
                display: 'popup', // Only popup for now
                actionType: 'create', // create
                language: 'en' //  Support ar,de,en,es,fr,hi,id,it,ja,ko,ms,ru,th,tr,vi,zh,zh-Hant, default value: en
            },
            (response) => {
                console.log(response);
                // pageId
                // status 2 ready
                // accountId
                // ...
            }
        );
    };
</script>
<script src="https://sf-unpkg.tiktokcdn.com/obj/unpkg-va/latest/byted/instant_page_sdk/dist/index.js"></script>
</body>
</html>
(/code)
```

# FAQ

## Q1: How can I update the SDK?

A1: You don't need to manually update the SDK because the SDK link always points to the latest version. 

## Q2: When will the callback function be triggered?

A2: The callback function is triggered when the user finishes creating the instant page in the TIP Editor and closes it. If the user closes the editor without finishing creating the instant page, the callback function will not be triggered.

# Related endpoints

**Page**
- [Get the Page ID](https://business-api.tiktok.com/portal/docs?id=1820826387779586)
- [Create a TIP Editor SDK access token](https://business-api.tiktok.com/portal/docs?id=1820826755283969)
- [Validate a TIP Editor SDK access token](https://business-api.tiktok.com/portal/docs?id=1820826826000385)
- [Renew a TIP Editor SDK access token](https://business-api.tiktok.com/portal/docs?id=1820826847199234)

**Test leads**
* [Create a test lead](https://business-api.tiktok.com/portal/docs?id=1739047132614722)
* [Get a test lead](https://business-api.tiktok.com/portal/docs?id=1739048460493826)
* [Delete a test lead](https://business-api.tiktok.com/portal/docs?id=1739049313198081)

**Download leads**
* [Create a lead download task](https://business-api.tiktok.com/portal/docs?id=1739052936772610)
* [Download leads](https://business-api.tiktok.com/portal/docs?id=1739053563877378)

**Subscribe to leads**
* [Create a subscription](https://business-api.tiktok.com/portal/docs?id=1739092028876801)
* [Get subscription details](https://business-api.tiktok.com/portal/docs?id=1739093832125442)
* [Cancel a subscription](https://business-api.tiktok.com/portal/docs?id=1739094758789122)

**Migrate leads**
* [Migrate leads to a BC](https://business-api.tiktok.com/portal/docs?id=1740029931324417)
* [Get form libraries](https://business-api.tiktok.com/portal/docs?id=1740029915327489)

**Others**
* [Get the fields of an Instant Form](https://business-api.tiktok.com/portal/docs?id=1739054080455681)
* [Get Instant Page events](https://business-api.tiktok.com/portal/docs?id=1740898639412226)
