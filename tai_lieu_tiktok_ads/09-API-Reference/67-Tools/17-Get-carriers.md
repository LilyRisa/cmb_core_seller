---
title: "Get carriers"
doc_id: 1737168013095938
path: "API Reference / Tools / Get carriers"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get carriers

Use this endpoint to get the carrier enumeration values that represents carriers in different countries or locations.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tools/carrier_v2/|/v1.3/tool/carrier/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter name and data type |`carrier_v2_id`(in the `carriers` object): number<br>`id`(in the `value` object): number|`carrier_id`: string<br>`hni_id` : string |
|New response parameter|/|`countries`|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/tool/carrier/

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
|advertiser_id {Required}|string| Advertiser ID |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
https://business-api.tiktok.com/open_api/v1.3/tool/carrier/
(/code)
```

## Response


```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message. For details, see[Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data |
#|countries|object[]|List of countries or locations.|
##|country_code|string| Country or location code.|
##|carriers|object[]| List of carriers in the country or location. |
###|carrier_id|string| Carrier ID. You can include it when creating an ad.|
###|in_use|boolean| Whether the carrier can be used for targeting|
###|name|string| Carrier name |
###|value|object[]| List of HNI (MCC/MNC) IDs |
####|hni_id|number| HNI (MCC/MNC) ID. A unique combination formed from the MNC (Mobile Network Code) and MCC (Mobile Country Code), to form a home network identity (HNI)|
####|in_use|boolean| Whether the HNI ID can be used for targeting|
|request_id |string|The log ID of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK

{
    "code": 0,
    "message": "OK",
    "request_id": "20220630234500010245242163145E6D44",
    "data": {
        "countries": [
            {
                "carriers": [
                    {
                        "in_use": true,
                        "value": [
                            {
                                "in_use": true,
                                "hni_id": "42402"
                            }
                        ],
                        "name": "AE: Etisalat",
                        "carrier_id": "4240000"
                    },
                    {
                        "in_use": true,
                        "value": [
                            {
                                "in_use": true,
                                "hni_id": "42403"
                            }
                        ],
                        "name": "AE: du",
                        "carrier_id": "4240001"
                    }
                ],
                "country_code": "AE"
            },
            {
                "carriers": [
                    {
                        "in_use": true,
                        "value": [
                            {
                                "in_use": true,
                                "hni_id": "722310"
                            },
                            {
                                "in_use": true,
                                "hni_id": "722320"
                            },
                            {
                                "in_use": true,
                                "hni_id": "722330"
                            }
                        ],
                        "name": "AR: Claro",
                        "carrier_id": "7220000"
                    },
                    ...
                ],
                "country_code": "AR",
            },
            ...
        ]
    }
}
(/code);
```
