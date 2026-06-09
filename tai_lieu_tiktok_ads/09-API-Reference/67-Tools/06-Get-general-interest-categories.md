---
title: "Get general interest categories"
doc_id: 1737174348712961
path: "API Reference / Tools / Get general interest categories"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get general interest categories

Use this endpoint to get general interest category enumeration values.

> <span style="color:DodgerBlue">**Note**</span><br>
> We recommend that you use the Consolidated Interest and Behavior API ([/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1796638342793218)) to search for or list targeting categories and hashtags for interests and behaviors. This API provides a consolidated and efficient approach to access the necessary targeting information.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tools/interest_category/|/v1.3/tool/interest_category/|
|Request parameter name|<ul><li>`placement`</li><li>`industry_types`|<ul><li> `placements`</li><li>`special_industries` |
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter name|<ul><li>`name`( in the `interest_categories`object)</li><li>`placement`</li><li>`industry_types`|<ul><li> `interest_category_name`( in the `interest_categories`object)</li><li>`placements`</li><li>`special_industries` |
|Response parameter name and data type|<ul><li>`id`(in the `interest_categories`object): number </li><li>`children`( in the `interest_categories`object): number[]|<ul><li> `interest_category_id`：string</li><li>`sub_category_ids`: string[] |
```
## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/tool/interest_category/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|version |number| Version of interest category. <br><br>Enum values:`1`(`interest_category`), `2` (`interest_category_v2`)。Default: `2`. |
|language|string| Category name language in repsonse. <br><br>Default value: `en`. <br><br> Supported languages: `en`, ` zh`, ` ja`, ` de`, ` es`, ` fr`, ` id`, ` it`, ` ko`, ` ru`, ` th`, ` tr`, ` vi`, ` ar`, ` pt`, ` ms`.<br><br>For details, see [Enumeration - Language Code](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
|placements |string[]|The apps where you want to deliver your ads. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). <br>The interest categories available for different placements may be different, and the field only takes effect when `version` = `2`.</span> |
|special_industries|string[]| Special ad categories. <br><br>Enum values:<ul><li>`HOUSING`: Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.</li><li>`EMPLOYMENT`: Ads for job offers, internship, professional certification programs or other related opportunities.</li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities. </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: This field is generally available to advertisers registered in America or Canada. Advertisers who are not registered in America or Canada but want to target these countries with special ad categories need to apply for an additional allowlist.|
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "version=VERSION" \
https://business-api.tiktok.com/open_api/v1.3/tool/interest_category/
(/code)
```

## Response

```xtable
|Field{35%}|Data Type{10%}|Description{55%}|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message. For details, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Return Data |
#|interest_categories|object[]| Interest category list. <br>To determine the corresponding names for gaming-related interest category IDs, see [Interest category](https://business-api.tiktok.com/portal/docs?id=1738865030529025).|
##|interest_category_id|string| Interest category ID |
##|interest_category_name|string| Interest category name |
##|level|number| Level |
##|sub_category_ids|string[]| Subcategory ID list |
##|special_industries|string[]| Special ad categories.  <br><br>Enum values:<ul><li>`HOUSING`: Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.</li><li>`EMPLOYMENT`: Ads for job offers, internship, professional certification programs or other related opportunities.</li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If the returned value is empty(`[]`), it means that the interest categories do not support any special ad categories. </li></ul>|
##|placements |string[]|Available placement for this interest category. Returning an empty list means unlimited. Please refer to [Enumeration-Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details of enum values.|
|request_id |string|The log id of a request, which uniquely identifies the request.|

```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "202206302233010102452460660B5E36E5",
    "data": {
        "interest_categories": [
            {
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "placements": [],
                "interest_category_id": "10",
                "level": 1,
                "interest_category_name": "Education",
                "sub_category_ids": [
                    "10100",
                    "10101",
                    "10102",
                    "10103",
                    "10104",
                    "10105",
                    "10106"
                ]
            },
            {
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "placements": [],
                "interest_category_id": "11",
                "level": 1,
                "interest_category_name": "Vehicles & Transportation",
                "sub_category_ids": [
                    "11100",
                    "11101",
                    "11102",
                    "11103",
                    "11104",
                    "11105",
                    "11106",
                    "11107",
                    "11108",
                    "11109",
                    "11111",
                    "11112"
                ]
            },
            ...
        ]
    }
}
(/code);
```
