---
title: "Get locations and currencies"
doc_id: 1740491571747841
path: "API Reference / Catalog Management / Get locations and currencies"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get locations and currencies

Use this endpoint to get the list of locations (country or region abbreviations) that are supported by Catalog Management API and the corresponding currency for each location.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/location_currency/get/| /v1.3/catalog/location_currency/get/|
```

## Request

**Endpoint** 
https://business-api.tiktok.com/open_api/v1.3/catalog/location_currency/get/

**Method** GET

**Header**

``` xtable
|Field{35%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/catalog/location_currency/get/' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```


## Response

``` xtable
|Field{35%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| code | number  |Return code. For the complete list of response codes and descriptions, see [Appendix - Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Return message. For details, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data|object| Returned data.|
#|list|object[]|List.|
##|location|string|Country or region code. <br><br>Example: `"DE"`. <br><br>To find out the corresponding region for a country or region code, refer to [Appendix - Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010). |
##|currency|string[]|List of currency codes that are supported for the location. <br><br>To find out the corresponding currency for a currency code, refer to [Appendix - Currency](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433). |
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
        "list": [
            {
                "location": "PL",
                "currency": [
                    "PLN"
                ]
            },
            {
                "location": "FR",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "MA",
                "currency": [
                    "MAD"
                ]
            },
            {
                "location": "HU",
                "currency": [
                    "HUF"
                ]
            },
            {
                "location": "AU",
                "currency": [
                    "AUD"
                ]
            },
            {
                "location": "GB",
                "currency": [
                    "GBP"
                ]
            },
            {
                "location": "UZ",
                "currency": [
                    "UZS"
                ]
            },
            {
                "location": "AE",
                "currency": [
                    "AED"
                ]
            },
            {
                "location": "TR",
                "currency": [
                    "TRY"
                ]
            },
            {
                "location": "AT",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "CZ",
                "currency": [
                    "CZK"
                ]
            },
            {
                "location": "CL",
                "currency": [
                    "CLP"
                ]
            },
            {
                "location": "ES",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "CH",
                "currency": [
                    "CHF"
                ]
            },
            {
                "location": "NO",
                "currency": [
                    "NOK"
                ]
            },
            {
                "location": "ID",
                "currency": [
                    "IDR"
                ]
            },
            {
                "location": "TW",
                "currency": [
                    "TWD"
                ]
            },
            {
                "location": "BE",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "IT",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "PE",
                "currency": [
                    "PEN"
                ]
            },
            {
                "location": "EG",
                "currency": [
                    "EGP"
                ]
            },
            {
                "location": "LU",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "JP",
                "currency": [
                    "JPY"
                ]
            },
            {
                "location": "KR",
                "currency": [
                    "KRW"
                ]
            },
            {
                "location": "TH",
                "currency": [
                    "THB"
                ]
            },
            {
                "location": "KW",
                "currency": [
                    "KWD"
                ]
            },
            {
                "location": "SA",
                "currency": [
                    "SAR"
                ]
            },
            {
                "location": "CA",
                "currency": [
                    "CAD"
                ]
            },
            {
                "location": "IN",
                "currency": [
                    "INR"
                ]
            },
            {
                "location": "DE",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "NZ",
                "currency": [
                    "NZD"
                ]
            },
            {
                "location": "NL",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "CO",
                "currency": [
                    "COP"
                ]
            },
            {
                "location": "AR",
                "currency": [
                    "ARS"
                ]
            },
            {
                "location": "US",
                "currency": [
                    "USD"
                ]
            },
            {
                "location": "UA",
                "currency": [
                    "UAH"
                ]
            },
            {
                "location": "LB",
                "currency": [
                    "LBP"
                ]
            },
            {
                "location": "PK",
                "currency": [
                    "PKR"
                ]
            },
            {
                "location": "MX",
                "currency": [
                    "MXN"
                ]
            },
            {
                "location": "DK",
                "currency": [
                    "DKK",
                    "EUR"
                ]
            },
            {
                "location": "PH",
                "currency": [
                    "PHP"
                ]
            },
            {
                "location": "SE",
                "currency": [
                    "SEK"
                ]
            },
            {
                "location": "JO",
                "currency": [
                    "JOD"
                ]
            },
            {
                "location": "RO",
                "currency": [
                    "RON",
                    "LEU",
                    "EUR"
                ]
            },
            {
                "location": "EC",
                "currency": [
                    "USD"
                ]
            },
            {
                "location": "FI",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "BR",
                "currency": [
                    "BRL"
                ]
            },
            {
                "location": "KZ",
                "currency": [
                    "KZT"
                ]
            },
            {
                "location": "OM",
                "currency": [
                    "OMR"
                ]
            },
            {
                "location": "SG",
                "currency": [
                    "SGD",
                    "BND"
                ]
            },
            {
                "location": "IE",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "ZA",
                "currency": [
                    "ZAR"
                ]
            },
            {
                "location": "IQ",
                "currency": [
                    "IQD"
                ]
            },
            {
                "location": "BY",
                "currency": [
                    "BYN"
                ]
            },
            {
                "location": "MY",
                "currency": [
                    "MYR"
                ]
            },
            {
                "location": "QA",
                "currency": [
                    "QAR"
                ]
            },
            {
                "location": "BH",
                "currency": [
                    "BHD"
                ]
            },
            {
                "location": "GR",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "PT",
                "currency": [
                    "EUR"
                ]
            },
            {
                "location": "KH",
                "currency": [
                    "KHR"
                ]
            },
            {
                "location": "IL",
                "currency": [
                    "ILS"
                ]
            },
            {
                "location": "RU",
                "currency": [
                    "RUB"
                ]
            },
            {
                "location": "VN",
                "currency": [
                    "VND"
                ]
            }
        ]
    }
}
(/code)
```

