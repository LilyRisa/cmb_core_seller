---
title: "Get available locations by advertiser ID"
doc_id: 1773644763581441
path: "API Reference / Tools / Get available locations by advertiser ID"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get available locations by advertiser ID

Use this endpoint to get available locations that your ads can be delivered to **based on your advertiser IDs**.
<br>If you want to get available locations that your ads can be delivered to based on your objectives, placements, and optionally targeting operating systems, and brand safety settings, use the [/tool/region/](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1737189539571713) endpoint. 

## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/search/region/

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
| advertiser_id {Required} | string | Advertiser ID. |
| language | string | The language you want the returned region name to be translated into. Default value: `en`<br>Currently, we only support `zh`, `fr`, `es`, `ko`, `vi`, `en`, `hi`, `it`, `tr`, `ru`, `ja`, `id`, `de`, `ms`, `ar`, and `th`<br><p><span style="color:darkred"><b>Note</b></span>: If you do not pass in one of the supported values above, it will return as the default `en`.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/search/region/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}' \
--data-raw '{
    "advertiser_id": "{{advertiser_id}}",
    "language": "ja"
}'
(/code)
```

## Response
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data|object|Returned data.|
#|region_list|object[]|List of all available regions. |
##|region_id|string|Region ID.|
##|region_name|string|Region name. |
##|region_level|string|Region level. Enum values: `COUNTRY`(country or region level), `PROVINCE`, `CITY`, `DISTRICT`. <br>DMA and metropolitans are at the province level.|
##|country_code|string|Country code for the country of the region.|
##|parent_id|string|The upper-level region id. This field will not be returned if `region_level` is `COUNTRY`.|
##|area_type|string|Location type. Enum values: `ADMIN`, `METROPOLITAN_OR_DMA`.|
##|support_below_18|boolean|Whether the location supports ads to be delivered to people under 18 years of age.|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
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
        "region_list": [
             {
                "region_level": "COUNTRY",
                "support_below_18": true,
                "country_code": "US",
                "area_type": "ADMIN",
                "region_id": "6252001",
                "region_name": "アメリカ"
              },
              {
                 "region_level": "COUNTRY",
                 "support_below_18": true,
                 "country_code": "EC",
                 "area_type": "ADMIN",
                 "region_id": "3658394",
                 "region_name": "エクアドル"
              },
             {
                 "region_level": "COUNTRY",
                 "support_below_18": true,
                 "country_code": "QA",
                 "area_type": "ADMIN",
                 "region_id": "289688",
                 "region_name": "カタール"
            },
            …
        ]
    }
}
(/code)
```
