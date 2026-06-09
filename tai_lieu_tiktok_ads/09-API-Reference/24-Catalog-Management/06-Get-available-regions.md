---
title: "Get available regions"
doc_id: 1740491257516034
path: "API Reference / Catalog Management / Get available regions"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get available regions

Use this endpoint to query for the countries and regions that ads for a catalog can be delivered to.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/available_countries/get/| /v1.3/catalog/available_country/get/|
|Request parameter data type |`bc_id`: number|`bc_id`: string|
|Response parameter name |`countries`|`region_codes`|
```

## Request

**Endpoint** 
https://business-api.tiktok.com/open_api/v1.3/catalog/available_country/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```
**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string| Business Center ID.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/catalog/available_country/get/?bc_id={{bc_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
| Field | Type | Description |
|--- |--- |--- |
| code | number  |Return code. For the complete list of response codes and descriptions, [Appendix - Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Return message. For details, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data|object| Data.|
#|region_codes|string[]|List of codes representing countries and regions that ads for this catalog can be delivered to. <br><br>To find out the corresponding region for a country or region code, refer to [Appendix - Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010).|
| request_id | string | The log id of a request, which uniquely identifies the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "region_codes": [
            "US",
            "GB",
            "FR",
            "IT",
            "DE",
            "ES",
            "ID",
            "VN",
            "TH",
            "MY",
            "PH",
            "AU",
            "JP",
            "RU",
            "BR",
            "SA",
            "AE",
            "TR",
            "CA",
            "MX",
            "SG",
            "KR",
            "NZ",
            "PL",
            "TW",
            "NO",
            "DK",
            "FI",
            "AT",
            "NL",
            "CH",
            "PT",
            "SE",
            "EG",
            "ZA",
            "PK",
            "UA",
            "CL",
            "AR",
            "PE",
            "CO",
            "RO",
            "KZ",
            "BY",
            "GR",
            "IL",
            "BH",
            "IQ",
            "KW",
            "MA",
            "OM",
            "QA",
            "IE",
            "BE",
            "CZ",
            "UY",
            "EC",
            "HU"
        ]
    }
}
(/code);
```

