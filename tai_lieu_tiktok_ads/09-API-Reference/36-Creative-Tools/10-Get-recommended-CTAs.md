---
title: "Get recommended CTAs"
doc_id: 1739362202742785
path: "API Reference / Creative Tools / Get recommended CTAs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get recommended CTAs

Use this endpoint to get recommended call-to-actions (CTAs) that you can use to create or update your ads. 

See [CTA recommendations](https://ads.tiktok.com/marketing_api/docs?id=1740307296329730) to find out the introduction of the two types of recommended CTAs (Standard Recommended CTAs and Dynamic CTAs).

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/creative/recommend_asset/get/|/v1.3/creative/cta/recommend/|
|Request parameter name|`external_type`|`content_type`|
|Request parameter data type |`advertiser_id`: number |`advertiser_id`: string|
|New request parameters | /|`new_version`<br> `objective_type`<br> `promotion_type`<br> `language`<br> `app_id`<br> `placements`<br> `region_codes`<br> `optimization_goal`<br> `ad_texts`<br> `landing_page_url` |
|Response parameter name and data type|`asset_id`: number[] |`asset_ids`: string[]|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/cta/recommend/

**Method** GET

**Header**

``` xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Type|Description|
|--- |--- |--- |
|advertiser_id {required}|string|Advertiser ID.|
| new_version | boolean | Whether to use the new version of the endpoint that supports retrieval of Dynamic CTAs based on your ad settings.<br><br>Supported values: `true`, `false`. <br>Default value: `false`. |
|asset_type {+Conditional} |string|<ul><li>Required when `new_version` is `false` or not passed.</li><li> Ignored when `new_version` is `true`.</li></ul> <br>The asset type. <br><br> Enum values：<ul><li> `CTA_NORMAL`: Standard Recommended CTAs.</li><li>`CTA_AUTO_OPTIMIZED`: Dynamic CTAs.</li></ul>|
|content_type {+Conditional} |string|<ul><li>Required when `new_version` is `false` or not passed.</li><li> Ignored when `new_version` is `true`.</li></ul> <br>The ads content type. <br><br>Enum values:<ul><li>`APP_DOWNLOAD`</li><li>`LANDING_PAGE`</li><li>`OTHER`</li><li>`MESSAGE` : a Lead Generation ad with promotion type as TikTok direct message.</li><li>`SOCIAL_MEDIA_APP_MESSAGE` : a Lead Generation ad with promotion type as social media app. </li><li>`PHONE_CALL`:  a Lead Generation ad with promotion type as phone call.</li></ul> |
| objective_type {+Conditional} | string |<ul><li>Required when `new_version` is `true`.</li><li>Not supported when `new_version` is `false` or not passed.</li></ul><br>Advertising objective. <br><br>For enum values and descriptions, see [Objectives](https://ads.tiktok.com/marketing_api/docs?id=1737585562434561). |
| promotion_type{+Conditional}  | string | <ul><li>Required when `new_version` is `true`.</li><li>Not supported when `new_version` is `false` or not passed.</li></ul><br> Promotion type (Optimization location). <br><br>For enum values, see [Enumeration - Promotion Type](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Promotion%20Type). |
| language | string | Valid only when `new_version` is `true`. <br><br>The code of language for the recommended CTAs. <br><br>To obtain the list of available language codes, use [/tool/language/](https://ads.tiktok.com/marketing_api/docs?id=1737188554152962). <br><br> Default value: `en`. <br><br> For example, if you set this field to `fr`, the returned `asset_content` will be in French. |
| app_id | string | Valid only when `new_version` is `true`.<br><br>The application ID of the promoted app. <br><br> To obtain the list of apps within your ad account, use [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786).  <br><br><p><span style="color:darkred"><b>Note</b></span>: Due to limited historical data for generating customized results, you might receive the same recommended CTAs even if you provide a different value for this field. |
| placements | string[] | Valid only when `new_version` is `true`. <br><br>The apps where you want to deliver your ads.<br><br>Enum values: <ul><li>`PLACEMENT_TIKTOK`: TikTok.</li><li>`PLACEMENT_PANGLE`: Pangle. </li><li>`PLACEMENT_GLOBAL_APP_BUNDLE`: Global App Bundle.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Due to limited historical data for generating customized results, you might receive the same recommended CTAs even if you provide a different value for this field. |
| region_codes | string[] | Valid only when `new_version` is `true`. <br><br>Codes of the regions that you want to target. <br><br>For enum values, see [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010). <br><br>Example: `US`. <br><br><p><span style="color:darkred"><b>Note</b></span>: Due to limited historical data for generating customized results, you might receive the same recommended CTAs even if you provide a different value for this field. |
| optimization_goal | string | Valid only when `new_version` is `true`.<br><br> The measurable results you'd like to drive with your ads. <br><br>For a complete list of enum values, see [Enumeration - Optimization Goal](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Optimization%20Goal). <br><br><p><span style="color:darkred"><b>Note</b></span>: Due to limited historical data for generating customized results, you might receive the same recommended CTAs even if you provide a different value for this field. |
| ad_texts | string[] | Valid only when `new_version` is `true`.<br><br> A list of ad texts. <br> Each ad text is shown to your audience as part of your ad creative, to deliver the message you intend to communicate to them. <ul><li>Ad text must be 1-100 characters long and cannot contain emoji.</li><li>Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.</li></ul><br> Recommended size: 1. <br><br>If you don't know how to create effective ad texts, you can try the [Smart Text](https://ads.tiktok.com/marketing_api/docs?id=1739084248002626) feature, which generates ad text recommendations based on the industry and language. <br><br><p><span style="color:darkred"><b>Note</b></span>: Due to limited historical data for generating customized results, you might receive the same recommended CTAs even if you provide a different value for this field. |
| landing_page_url | string | Valid only when `new_version` is `true`. <br><br>The landing page that users will be redirected to. <br><br><p><span style="color:darkred"><b>Note</b></span>: Due to limited historical data for generating customized results, you might receive the same recommended CTAs even if you provide a different value for this field. |
```

### Example
#### Retrieve recommended CTAs based on your ad settings
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/creative/cta/recommend/?advertiser_id={{advertiser_id}}&new_version=true&objective_type=WEB_CONVERSIONS&promotion_type=WEBSITE&language=en' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

#### Retrieve recommended CTAs not based on your ad settings
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/creative/cta/recommend/?advertiser_id={{advertiser_id}}&asset_type=CTA_AUTO_OPTIMIZED&content_type=APP_DOWNLOAD' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
|Field|Type|Description|
|-|-|-|
|message|string|The return message. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|The returned data.|
#|recommend_assets|object[]| The recommended assets. |
##|asset_ids|string[]|The asset IDs of recommended CTAs|
##|asset_content|string|The CTA text. For example, `"Learn More"`.|
|request_id |string|The request log ID|
```

### Example

``` xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "recommend_assets": [
            {
                "asset_ids": [
                    "201902",
                    "201404"
                ],
                "asset_content": "Download now"
            },
            {
                "asset_ids": [
                    "202114",
                    "201743"
                ],
                "asset_content": "Install now"
            },
            {
                "asset_ids": [
                    "201850",
                    "201352"
                ],
                "asset_content": "Install app"
            },
            {
                "asset_ids": [
                    "202141",
                    "201716"
                ],
                "asset_content": "Enroll today"
            },
            {
                "asset_ids": [
                    "201899",
                    "201401"
                ],
                "asset_content": "Check it now"
            },
            {
                "asset_ids": [
                    "201808",
                    "201598"
                ],
                "asset_content": "Check in app"
            },
            {
                "asset_ids": [
                    "201836",
                    "201628"
                ],
                "asset_content": "Try this new app"
            },
            {
                "asset_ids": [
                    "202116",
                    "201740"
                ],
                "asset_content": "Install app now"
            },
            {
                "asset_ids": [
                    "201934",
                    "202158"
                ],
                "asset_content": "Download"
            },
            {
                "asset_ids": [
                    "202124",
                    "201733"
                ],
                "asset_content": "Install it now"
            }
        ]
    }
}
(/code)
```
