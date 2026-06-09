---
title: "Get available contextual tags"
doc_id: 1747747118654465
path: "API Reference / Tools / Get available contextual tags"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get available contextual tags

Use this endpoint to get the contextual tags that you can target. You need to specify advertiser ID, advertising objective and region codes in your query to get the available tags.

See [here](https://ads.tiktok.com/marketing_api/docs?id=1745292519923713) to find out how to use contextual targeting. 
><span style="color:DodgerBlue">**Note**</span><br> Contextual targeting is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.


## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/tool/contextual_tag/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {required} | string | Advertiser ID. |
| objective_type {required} | string | Advertising objective. Only supports `REACH`, `VIDEO_VIEWS`, `RF_REACH`. |
| region_codes | string[] | Country or region codes. |
| brand_safety_type | string | Brand safety type. Default value: `STANDARD_INVENTORY`. Enum values: <ul><li>`EXPANDED_INVENTORY`: Use TikTok's brand safety solution. Expanded inventory means that your ads will appear next to content where most inappropriate content has been removed, and may contain some mature themes. In the next API version, `EXPANDED_INVENTORY` will replace `NO_BRAND_SAFETY` and will be the default brand safety option.</li><li>`STANDARD_INVENTORY`: Use TikTok's brand safety solution. Standard inventory means that ads will appear next to content that is appropriate for most brands. </li><li>`LIMITED_INVENTORY`: Use TikTok's brand safety solution. Limited inventory means that your ads will not appear next to content that contains mature themes.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Pre-bid first-party Brand Safety solutions for `APP_PROMOTION`,  `WEB_CONVERSIONS`, `TRAFFIC`,  `LEAD_GENERATION` objectives in Auction ads, and pre-bid third-party brand safety solutions are currently allowlist-only features. If you would like to access them, please contact your TikTok representative. </li><li> See [Brand safety](https://ads.tiktok.com/marketing_api/docs?id=1739381752981505) to learn about the supported advertising objectives for pre-bid Brand Safety filtering. </li></ul>|
| rf_campaign_type | string |<p><span style="color:darkred"><b>Note</b></span>: <ul><li>When `objective_type` is specified as `RF_REACH`, use this field to set the campaign as a TikTok Pulse campaign, then you can get available premium contextual tags. </li><li>Do not pass in this field when `objective_type` is not specified as `RF_REACH`.</li></ul> Enum values: `STANDARD` (Reach & Frequency campaign), `PULSE`（TikTok Pulse campaign）. |
```

 ### Example
 ```xcodeblock
 (code curl http)
 curl --location --get 'https://business-api.tiktok.com/open_api/v1.3/tool/contextual_tag/get/?advertiser_id={{advertiser_id}}&objective_type=RF_REACH&region_codes=[%22US%22]&brand_safety_type=LIMITED_INVENTORY&rf_campaign_type=PULSE' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
(/code)
```

## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| contextual_tag_list | object[] | Contextual tag list. <br><p><span style="color:darkred"><b>Note</b></span>: Contextual targeting is currently an allowlist-only feature. The returned value will be empty(`[]`) if you don't apply for the allowlisting. |
##| contextual_tag_id | string | Contextual tag ID. <br><p><span style="color:darkred"><b>Note</b></span>: Currently contextual tags can only be used together with audience targeting settings that target the following countries: <ul><li>General tags: `US` (the United States), `CA` (Canada), `GB` (the United Kingdom), `DE` (Germany), `FR` (France), `IT` (Italy) , `ES` (Spain), `JP` (Japan), `KR` (South Korea), `AU` (Australia)，`BR` (Brazil), `MX`(Mexico).</li><li>Premium tags: `US` (the United States), `CA` (Canada), `GB` (the United Kingdom), `DE` (Germany), `FR` (France), `IT` (Italy) , `ES` (Spain), `JP` (Japan), `KR` (South Korea), `AU` (Australia)，`BR` (Brazil), `MX`(Mexico).</li></ul> |
##| name | string | Contextual tag name. |
##| type | string | Contextual tag category. Enum values: `GENERAL` (general category), `PREMIUM`(premium category). |
##| content_lineup_type | string | Content lineup types.<br>Enum values: <ul><li>`MAX_PULSE` (Max Pulse: Your ads will show next to top content from any topics on TikTok.) </li><li>`CUSTOM` (Category Lineups: Your ads will show next to top content from the categories or seasonal events you select.)</li></ul><p><span style="color:darkred"><b>Note</b></span>: Currently, you can run Max Pulse targeting `AE` (United Arab Emirates), `AU` (Australia), `BR` (Brazil), `CA` (Canada), `DE` (Germany), `ES` (Spain), `FR` (France), `GB` (United Kingdom), `IT` (Italy), `MX` (Mexico), `SA` (Saudi Arabia), `TR` (Turkey), and `US` (United States), and Category Lineups targeting `US`(the United States), `CA`(Canada), `BR`(Brazil), `AU`(Australia), `GB`(the United Kingdom), `FR`(France), `IT`(Italy), `ES`(Spain), `DE`(Germany). Both features are allowlist only. If you want to use them in the above markets, please contact your TikTok representative. |
##| status | string | Contextual tag status. Enum: `ONLINE`, `OFFLINE`. Only `ONLINE` tags can be used to create ads. |
##| description | string | Contextual tag description. |
##| objective_types | string[] | The advertising objectives that the contextual tag supports. |
##| region_codes | string[] | The country or region codes that the contextual tag supports. |
```

### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "contextual_tag_list": [
            {
                "type": "PREMIUM",
                "name": "{{name}}",
                "status": "ONLINE",
                "region_codes": [
                    "US",
                    "CA",
                    "GB",
                    "DE",
                    "FR",
                    "IT",
                    "ES",
                    "BR",
                    "AU",
                    "MX"
                ],
                "description": "{{description}}",
                "objective_types": [
                    "RF_REACH"
                ],
                "content_lineup_type": "CUSTOM",
                "contextual_tag_id": "7"
            }
        ]
    }
}
(/code)
```
