---
title: "Get time zones"
doc_id: 1738455961470977
path: "API Reference / Tools / Get time zones"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get time zones

Use this endpoint to get time zone enumeration values, and their offsets from GMT.

Be aware that the returned offsets (`gmt_offset`) remain fixed and do not account for daylight saving time changes. For instance, for New York (`America/New_York`), the offset will always be `GMT-05:00`, regardless of whether New York is currently observing daylight saving time.

## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tools/timezone/|/v1.3/tool/timezone/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter name|`time_zone_list`|`timezones`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/timezone/

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
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET https://business-api.tiktok.com/open_api/v1.3/tool/timezone/?advertiser_id={{advertiser_id}}
--header 'Access-Token: {{Access-Token}}'
--header 'Content-Type: application/json'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data |object|Returned data.|
#|timezones|object[]| Time Zone list.|
##|timezone|string|Time zone. For example, "Pacific/Auckland".|
##|gmt_offset|string| Offset from GMT. For example, "GMT+12:00".|
```

### Example

```xcodeblock
(code JSON JSON)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "202107161139170102310210720317DD89",
    "data": {
        "timezones": [
            {
                "gmt_offset": "GMT+12:00",
                "timezone": "Pacific/Auckland"
            },
            {
                "gmt_offset": "GMT+12:00",
                "timezone": "Pacific/Majuro"
            },
            {
                "gmt_offset": "GMT+11:00",
                "timezone": "Asia/Magadan"
            },
            {
                "gmt_offset": "GMT+12:00",
                "timezone": "Asia/Kamchatka"
            },
            {
                "gmt_offset": "GMT+10:00",
                "timezone": "Australia/Sydney"
            },
            {
                "gmt_offset": "GMT+10:00",
                "timezone": "Asia/Vladivostok"
            },
            {
                "gmt_offset": "GMT+09:00",
                "timezone": "Australia/Broken_Hill"
            },
            {
                "gmt_offset": "GMT+09:00",
                "timezone": "Asia/Jayapura"
            },
            {
                "gmt_offset": "GMT+09:00",
                "timezone": "Asia/Tokyo"
            },
            {
                "gmt_offset": "GMT+09:00",
                "timezone": "Asia/Seoul"
            },
            {
                "gmt_offset": "GMT+09:00",
                "timezone": "Asia/Yakutsk"
            },
            {
                "gmt_offset": "GMT+08:00",
                "timezone": "Australia/Perth"
            },
            {
                "gmt_offset": "GMT+08:00",
                "timezone": "Asia/Shanghai"
            },
            {
                "gmt_offset": "GMT+08:00",
                "timezone": "Asia/Hong_Kong"
            },
            {
                "gmt_offset": "GMT+08:00",
                "timezone": "Asia/Makassar"
            },
            {
                "gmt_offset": "GMT+08:00",
                "timezone": "Asia/Kuala_Lumpur"
            },
            {
                "gmt_offset": "GMT+08:00",
                "timezone": "Asia/Manila"
            },
            {
                "gmt_offset": "GMT+08:00",
                "timezone": "Asia/Irkutsk"
            },
            {
                "gmt_offset": "GMT+08:00",
                "timezone": "Asia/Singapore"
            },
            {
                "gmt_offset": "GMT+08:00",
                "timezone": "Asia/Taipei"
            },
            {
                "gmt_offset": "GMT+07:00",
                "timezone": "Asia/Jakarta"
            },
            {
                "gmt_offset": "GMT+07:00",
                "timezone": "Asia/Krasnoyarsk"
            },
            {
                "gmt_offset": "GMT+07:00",
                "timezone": "Asia/Bangkok"
            },
            {
                "gmt_offset": "GMT+07:00",
                "timezone": "Asia/Ho_Chi_Minh"
            },
            {
                "gmt_offset": "GMT+06:00",
                "timezone": "Asia/Dhaka"
            },
            {
                "gmt_offset": "GMT+06:00",
                "timezone": "Asia/Omsk"
            },
            {
                "gmt_offset": "GMT+05:30",
                "timezone": "Asia/Kolkata"
            },
            {
                "gmt_offset": "GMT+05:30",
                "timezone": "Asia/Colombo"
            },
            {
                "gmt_offset": "GMT+05:00",
                "timezone": "Indian/Maldives"
            },
            {
                "gmt_offset": "GMT+05:00",
                "timezone": "Asia/Karachi"
            },
            {
                "gmt_offset": "GMT+05:00",
                "timezone": "Asia/Yekaterinburg"
            },
            {
                "gmt_offset": "GMT+04:00",
                "timezone": "Asia/Dubai"
            },
            {
                "gmt_offset": "GMT+04:00",
                "timezone": "Indian/Mauritius"
            },
            {
                "gmt_offset": "GMT+04:00",
                "timezone": "Asia/Muscat"
            },
            {
                "gmt_offset": "GMT+03:00",
                "timezone": "Asia/Bahrain"
            },
            {
                "gmt_offset": "GMT+03:00",
                "timezone": "Asia/Baghdad"
            },
            {
                "gmt_offset": "GMT+03:00",
                "timezone": "Africa/Nairobi"
            },
            {
                "gmt_offset": "GMT+03:00",
                "timezone": "Asia/Kuwait"
            },
            {
                "gmt_offset": "GMT+03:00",
                "timezone": "Asia/Qatar"
            },
            {
                "gmt_offset": "GMT+03:00",
                "timezone": "Europe/Moscow"
            },
            {
                "gmt_offset": "GMT+04:00",
                "timezone": "Europe/Samara"
            },
            {
                "gmt_offset": "GMT+03:00",
                "timezone": "Asia/Riyadh"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Europe/Sofia"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Asia/Nicosia"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Europe/Tallinn"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Africa/Cairo"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Europe/Helsinki"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Europe/Athens"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Asia/Jerusalem"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Asia/Amman"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Asia/Beirut"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Europe/Vilnius"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Europe/Riga"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Asia/Gaza"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Europe/Bucharest"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Europe/Kaliningrad"
            },
            {
                "gmt_offset": "GMT+03:00",
                "timezone": "Europe/Istanbul"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Europe/Kiev"
            },
            {
                "gmt_offset": "GMT+02:00",
                "timezone": "Africa/Johannesburg"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Vienna"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Sarajevo"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Brussels"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Zurich"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Prague"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Berlin"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Copenhagen"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Madrid"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Paris"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Zagreb"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Budapest"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Rome"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Luxembourg"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Skopje"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Malta"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Africa/Lagos"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Amsterdam"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Oslo"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Warsaw"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Belgrade"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Stockholm"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Ljubljana"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Bratislava"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Africa/Tunis"
            },
            {
                "gmt_offset": "GMT+00:00",
                "timezone": "Atlantic/Canary"
            },
            {
                "gmt_offset": "GMT+00:00",
                "timezone": "Europe/London"
            },
            {
                "gmt_offset": "GMT+00:00",
                "timezone": "Africa/Accra"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Europe/Dublin"
            },
            {
                "gmt_offset": "GMT+00:00",
                "timezone": "Atlantic/Reykjavik"
            },
            {
                "gmt_offset": "GMT+01:00",
                "timezone": "Africa/Casablanca"
            },
            {
                "gmt_offset": "GMT+00:00",
                "timezone": "Europe/Lisbon"
            },
            {
                "gmt_offset": "GMT-01:00",
                "timezone": "Atlantic/Azores"
            },
            {
                "gmt_offset": "GMT-02:00",
                "timezone": "America/Noronha"
            },
            {
                "gmt_offset": "GMT-03:00",
                "timezone": "America/Argentina/San_Luis"
            },
			...
        ]
    }
}
(/code)
```
