---
title: "Get available locations by different settings"
doc_id: 1737189539571713
path: "API Reference / Tools / Get available locations by different settings"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get available locations by different settings

Use this endpoint to get codes of locations that your ads can be delivered to <strong>based on your objectives, placements, and optionally targeting operating systems, and brand safety settings. </strong>
<br>If you want to get available locations that your ads can be delivered to based on your advertiser IDs, use the [/search/region/](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1773644763581441) endpoint. 

> <span style="color: dodgerblue">**Note**</span><br>
>  <ul><li>Targeting the newly available countries or regions (Azerbaijan, Bulgaria, Bolivia, Cyprus, Algeria, Estonia, Croatia, Kenya, Sri Lanka, Lithuania, Latvia, Nigeria, Puerto Rico, Paraguay, Serbia, Slovenia, Slovakia) using the advertising objective `TRAFFIC`, `LEAD_GENERATION`, `APP_PROMOTION`, or `WEB_CONVERSIONS` and the TikTok placement are currently separate allowlist-only features. If you would like to access them, contact your TikTok representative. You need to apply for a separate allowlist for each newly available country or region.</li><li>Targeting the newly available countries or regions (Azerbaijan, Bulgaria, Bolivia, Cyprus, Algeria, Estonia, Croatia, Kenya, Sri Lanka, Lithuania, Latvia, Puerto Rico, Paraguay, Serbia, Slovenia, Slovakia) using the advertising objective `REACH`, `VIDEO_VIEWS`, or `ENGAGEMENT` and the TikTik placement are currently separate allowlist-only features. If you would like to access them, contact your TikTok representative. You need to apply for a separate allowlist for each newly available country or region. <ul><li> When you target these countries or regions using the advertising objective `REACH`, `VIDEO_VIEWS`, or `ENGAGEMENT`, pre-bid third-party Brand Safety solutions are not supported. Therefore, you cannot set `brand_safety_type` to `THIRD_PARTY`.</li></ul></li></ul>

The level of locations is shown in the diagram below. Province, DMA(Designated Market Area), and Metropolitan are at the same level. Province, City, and District are administrative areas, so `area_type` for them is `ADMIN`. For DMA and Metropolitan, `area_type` is `METROPOLITAN_OR_DMA`.

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/40722691b00281190ec4d84256a4529e" width="30%" />

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tools/regions/|/v1.3/tool/region/|
|Request parameter name| <ul><li>`placement`</li><li>`brand_safety`     |<ul><li>`placements`</li><li>`brand_safety_type` |
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|New request parameters |/|`rf_campaign_type`<br>`promotion_target_type`<br>`shopping_ads_type`<br>`promotion_type`<br>`language`|
|Response parameter data type|<ul><li>`parent_id`: number</li><li>`next_level_ids`: number[]|<ul><li>`parent_id`: string</li><li>`next_level_ids`: string[]|
|Response parameter name and data type|`id`(in the `region_info`object): number|`location_id`: string|
```


## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/tool/region/>

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID |
|placements {Required}|string[]| The apps where you want to deliver your ads. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138)|
|objective_type {Required}|string| Your objective type. For enum values and descriptions, see [Objectives](https://ads.tiktok.com/marketing_api/docs?id=1737585562434561).|
|level_range|string| Location level you want to get. Enum values: <ul><li>`ALL`: all levels</li><li>`TO_COUNTRY`: only country or region level</li><li> `TO_PROVINCE`: country and province level; DMA and Metropolitan are at province level.</li><li>`TO_CITY`: country, province, and city levels</li><li>`TO_DISTRICT`: country, province, city, and district levels.</li></ul>|
|language|string|The language of the returned location name (`name`). <br><br>Default value: `en` (English).<br><br>To find out the allowed values, see <a href="#item-link-List of values for language">List of values for `language`</a>.<br><br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>If the specified language is not supported, it will be ignored and the default value `en` will be used.</li><li>If a supported non-English language is specified, the names of locations outside the native country or region associated with that language might not be translated. For example, if you set `language` to `ja` (Japanese), the names of locations outside Japan might not be translated.</li></ul>|
|shopping_ads_type |string|Valid only when `objective_type` is `PRODUCT_SALES`. <br><br>Shopping ads type. <br><br>Enum values: <ul><li>`VIDEO`: Video Shopping Ads.</li><li>`LIVE`: Live Shopping Ads.</li><li>`PRODUCT_SHOPPING_ADS`: Product Shopping Ads.</li></ul> <br>To find out the introduction to different Shopping Ads types and the steps of creating [Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1741942291730434), [Live Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1754162402455554), and [Product Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1785886237030401), see [Create Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1741942179356738).|
|promotion_type|string|Promotion type (Optimization location).<br><br>For enum values, see [Enumeration - Promotion Type](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Promotion%20Type). |
|promotion_target_type |string| Valid when `objective_type` is `LEAD_GENERATION`. <br>The promotion type for Lead Generation objective. <br>Enum values: <ul><li> `INSTANT_PAGE`：Instant Form. To create a fast-loading in-app TikTok Instant Form to collect more leads.</li><li> `EXTERNAL_WEBSITE`：Website Form. To use a landing page that has the Website Form or the TikTok Instant Page that redirects to the website with the Website Form to collect more leads.</li></ul> |
|operating_system|string| Operating systems that you want to target. Enum values: `ANDROID`, `IOS`.|
|brand_safety_type|string|Brand safety type. Valid only when `placements` is set as `PLACEMENT_TIKTOK`. Default value: `NO_BRAND_SAFETY`. Enum values: <ul><li>`NO_BRAND_SAFETY`:  To not use any brand safety solution.  Full inventory, which means your ads may appear next to some content featuring mature themes. </li><li>`EXPANDED_INVENTORY`: Use TikTok's brand safety solution. Expanded inventory means that your ads will appear next to content where most inappropriate content has been removed, and that does not contain mature themes. In the next API version, `EXPANDED_INVENTORY` will replace `NO_BRAND_SAFETY` and will be the default brand safety option.</li><li>`STANDARD_INVENTORY`: Use TikTok's brand safety solution. Standard inventory means that ads will appear next to content that is appropriate for most brands. </li><li>`LIMITED_INVENTORY`: Use TikTok's brand safety solution. Limited inventory means that your ads will not appear next to content that contains mature themes.</li><li>`THIRD_PARTY`: Use a third-party brand safety solution. You also need to pass in a value to the `brand_safety_partner` field.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Pre-bid first-party Brand Safety solutions for `APP_PROMOTION`, `WEB_CONVERSIONS`, `TRAFFIC`,  `LEAD_GENERATION` objectives in Auction ads, and pre-bid third-party brand safety solutions are currently allowlist-only features. If you would like to access them, please contact your TikTok representative. </li><li> See [Brand safety](https://ads.tiktok.com/marketing_api/docs?id=1739381752981505) to learn about the supported advertising objectives for pre-bid Brand Safety filtering. </li></ul>|
|brand_safety_partner{+Conditional}|string|Brand safety partner. Required when `brand_safety_type` is `THIRD_PARTY`, and `placements` needs to be set as `PLACEMENT_TIKTOK`. Enum values: `IAS`, `OPEN_SLATE`(The partner is named <strong>DoubleVerify</strong> on TikTok Ads Manager because the partner has been acquired by DoubleVerify).<br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Pre-bid third-party brand safety solutions are currently allowlist-only features. If you would like to access them, please contact your TikTok representative. |
|rf_campaign_type |string| When `objective_type` is specified as `RF_REACH`, use this field to set the campaign as a TikTok Pulse campaign, then you can get available premium contextual tags. <br><br>Enum values: `STANDARD` (Reach & Frequency campaign), `PULSE` (TikTok Pulse campaign). <br><br>If you set this field as `PULSE`, only one location ID (`location_id`) will be returned.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Do not pass in this field when `objective_type` is not specified as `RF_REACH`.</li><li>You need to pass the `location_id` value returned in this endpoint to `location_ids` when you use [/adgroup/rf/create/](https://ads.tiktok.com/marketing_api/docs?id=1738235338194945) to create a TikTok Pulse ad group.</li></ul>|
```

### List of values for `language`
```xtable
| Value {20%}| Description {60%}|
|---|---|
| `af` | Afrikaans |
| `am` | Amharic |
| `ar` | Arabic |
| `az` | Azerbaijani |
| `be` | Belarusian |
| `bg` | Bulgarian |
| `bn` | Bengali |
| `bs` | Bosnian |
| `ca` | Catalan |
| `cs` | Czech |
| `da` | Danish |
| `de` | German |
| `el` | Greek |
| `en` | English |
| `es` | Spanish |
| `et` | Estonian |
| `eu` | Basque |
| `fa` | Farsi |
| `fi` | Finnish |
| `fil` | Filipino |
| `fr` | French |
| `gl` | Galician |
| `gu` | Gujarati |
| `hi` | Hindi |
| `hr` | Croatian |
| `hu` | Hungarian |
| `hy` | Armenian |
| `id` | Indonesian |
| `is` | Icelandic |
| `it` | Italian |
| `ja` | Japanese |
| `ka` | Georgian |
| `kk` | Kazakh |
| `km` | Khmer |
| `kn` | Kannada |
| `ko` | Korean |
| `ky` | Kyrgyz |
| `lo` | Lao |
| `lt` | Lithuanian |
| `lv` | Latvian |
| `mk` | Macedonian |
| `ml` | Malayalam |
| `mn` | Mongolian |
| `mr` | Marathi |
| `ms` | Malay |
| `my` | Burmese |
| `ne` | Nepali |
| `nl` | Dutch |
| `no` | Norwegian |
| `pa` | Punjabi |
| `pl` | Polish |
| `pt` | Portuguese |
| `pt-BR` | Portuguese (Brazil) |
| `ro` | Romanian |
| `ru` | Russian |
| `si` | Sinhalese |
| `sk` | Slovak |
| `sl` | Slovenian |
| `sq` | Albanian |
| `sr` | Serbian |
| `sv` | Swedish |
| `sw` | Swahili |
| `ta` | Tamil |
| `te` | Telugu |
| `th` | Thai |
| `tr` | Turkish |
| `uk` | Ukrainian |
| `ur` | Urdu |
| `uz` | Uzbek |
| `vi` | Vietnamese |
| `zh` | Chinese |
| `zh-CN` | Chinese (Simplified) |
| `zh-HK` | Chinese (Hong Kong) |
| `zh-TW` | Chinese (Traditional) |
| `zu` | Zulu |
```


### Example
```xcodeblock
(code cURL HTTP)
curl --location -g --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/region/?advertiser_id={ADVERTISER_ID}}&placements=["PLACEMENT_TIKTOK"]&objective_type={{objective_type}}' \
--header 'Access-Token: xxxx' \
--header 'Content-Type: application/json' \
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data. |
#|region_list|string[]| List of country or region codes |
#|region_info|object[]| Information about the available locations|
##|location_id|string| Location ID|
##|name|string|Location name|
##|parent_id|string| ID of the parent location. Returns 0 if no parents|
##|region_code|string|Country or region code. For example, "DE".|
##|next_level_ids|string[]|List of children locations.|
##|area_type|string| Location type. Enum values: `ADMIN`, `METROPOLITAN_OR_DMA`.|
##|level|string|Location level. Enum values: `COUNTRY`(country or region level), `PROVINCE`, `CITY`(city or county level), `DISTRICT`(district or city level). DMA and metropolitans are at the province level.|
##|support_below_18|boolean|Whether the location supports ads to be delivered to people under 18 years of age.|
|request_id|string| The log ID of a request, which uniquely identifies the request.|
```
### Example

```xcodeblock
(code JSON json)
{
    "code": 0,
    "message": "OK",
    "request_id": "2022070115265201024524610404180ECA",
    "data": {
        "region_info": [
            {
                "next_level_ids": [],
                "area_type": "ADMIN",
                "level": "COUNTRY",
                "region_code": "PE",
                "location_id": "3932488",
                "name": "Peru",
                "parent_id": "0",
                "support_below_18": true
            },
            {
                "next_level_ids": [
                    "662",
                    "525",
                    "532",
                    ...
                ],
                "area_type": "ADMIN",
                "level": "COUNTRY",
                "region_code": "US",
                "location_id": "6252001",
                "name": "United States",
                "parent_id": "0",
                "support_below_18": true
            },
            ...
        ],
        "region_list": [
            "PE",
            "US",
            "CL",
            "MX",
            "BY",
            "VN",
            ...
        ]
    }
}
(/code)
```
