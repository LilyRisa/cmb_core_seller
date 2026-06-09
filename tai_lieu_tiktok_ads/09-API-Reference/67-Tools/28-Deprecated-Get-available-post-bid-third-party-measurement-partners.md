---
title: "(Deprecated) Get available post-bid third-party measurement partners"
doc_id: 1755436556897281
path: "API Reference / Tools / (Deprecated) Get available post-bid third-party measurement partners"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Get available post-bid third-party measurement partners

Use this endpoint to get available post-bid third-party measurement partners, and the reason why you cannot choose a certain partner.

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/tool/vast_option/>

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string| Request message type <br>Allowed format:`application/json`|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID |
|objective_type {Required}|string| Your objective type. For enum values and descriptions, see [Objectives](https://ads.tiktok.com/marketing_api/docs?id=1737585562434561).|
| region_codes|string[]| List of country or region codes. For example: `&#91;"US", "JP", "KR"&#93;`. <br><br>See [Appendix - Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010) for enum values.|
|brand_safety_type|string|Brand safety type. The allowed enum values: <ul><li>`STANDARD_INVENTORY`: Use TikTok's brand safety solution. Standard inventory means that ads will appear next to content that is appropriate for most brands. </li><li>`LIMITED_INVENTORY`: Use TikTok's brand safety solution. Limited inventory means that your ads will not appear next to content that contains mature themes. </li></ul> To get the countries and regions that your ads can be deployed to based on your brand safety settings, use the [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) endpoint.|
```


## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| The log ID of a request, which uniquely identifies the request.|
|data |object|Returned data. |
#| option_list | object[] | The list of post-bid third-party measurement partners. |
##| partner | string | Post-bid third-party brand safety or viewability measurement partner. Enum values: `MOAT`, `DOUBLE_VERIFY`, `IAS`, `ZEFR`.  <br>You can set post-bid third-party measurement partners at the ad level via `viewability_postbid_partner` and `brand_safety_postbid_partner` when calling [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). |
##| partner_name | string | Name of the post-bid third-party brand safety or viewability measurement partner. |
##| vast_type | string | The type of post-bid third-party measurement that the partner offers. Enum values: `VIEWABILITY` (viewability measurement), `BRAND_SAFETY` (brand safety measurement). |
##| is_require_url | boolean | Whether you need to provide a VAST URL when you choose the post-bid third-party measurement partner. |
##| is_available | boolean | Whether you can choose the post-bid third-party measurement partner when creating or updating ads. |
##| unavailable_reason | object[] | The information about why you cannot select the post-bid third-party measurement partner. Returned when `is_available` = `False`. |
###| reason | string | The reason why you cannot choose the post-bid third-party measurement partner. |
###| reason_type | string | The reason type. Enum values: <ul><li>`UNSUPPORTED_PARTNER`: You are not authorized to choose this partner. </li><li>`UNSUPPORTED_OBJ_TYPE`: The partner is not supported for the specified advertising objective. </li><li>`UNSUPPORTED_COUNTRY`: The partner is not supported for one or more of the specified country or region codes. </li><li>`UNSUPPORTED_PREBID`: The partner is not supported for post-bid third-party measurement because of your pre-bid brand safety setting via `brand_safety_type` in the request. </li></ul>|
```


