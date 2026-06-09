---
title: "Get region calling codes and region codes for phone numbers"
doc_id: 1774488637039618
path: "API Reference / Tools / Get region calling codes and region codes for phone numbers"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get region calling codes and region codes for phone numbers

Use this endpoint to retrieve the region codes, region names, and region calling codes for phone numbers.



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/phone_region_code/

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
```

### Example

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/phone_region_code/?advertiser_id={{advertiser_id}}' \
--header 'Access-Token: {{Access-Token}}' \
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
#| phone_region_code_infos | object[] | Information about the region codes, region names, and region calling codes for phone numbers.|
##| phone_region_name| string | The name of the region of a phone number.<br>Example: `"Austria"`. |
##| phone_region_code | string | The region code for a phone number.<br>Example: `"AT"`. |
##| phone_region_calling_code| string | The region calling code for a phone number.<br>Example: `"+43"`. |
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
        "phone_region_code_infos": [
            {
                "phone_region_code": "AC",
                "phone_region_name": "Antigua and Barbuda",
                "phone_region_calling_code": "+247"
            },
            {
                "phone_region_code": "AD",
                "phone_region_name": "Andorra",
                "phone_region_calling_code": "+376"
            },
            {
                "phone_region_code": "AE",
                "phone_region_name": "United Arab Emirates",
                "phone_region_calling_code": "+971"
            },
            {
                "phone_region_code": "AF",
                "phone_region_name": "Afghanistan",
                "phone_region_calling_code": "+93"
            },
            {
                "phone_region_code": "AG",
                "phone_region_name": "Antigua and Barbuda",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "AI",
                "phone_region_name": "Anguilla",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "AL",
                "phone_region_name": "Albania",
                "phone_region_calling_code": "+355"
            },
            {
                "phone_region_code": "AM",
                "phone_region_name": "Armenia",
                "phone_region_calling_code": "+374"
            },
            {
                "phone_region_code": "AO",
                "phone_region_name": "Angola",
                "phone_region_calling_code": "+244"
            },
            {
                "phone_region_code": "AR",
                "phone_region_name": "Argentina",
                "phone_region_calling_code": "+54"
            },
            {
                "phone_region_code": "AS",
                "phone_region_name": "American Samoa",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "AT",
                "phone_region_name": "Austria",
                "phone_region_calling_code": "+43"
            },
            {
                "phone_region_code": "AU",
                "phone_region_name": "Australia",
                "phone_region_calling_code": "+61"
            },
            {
                "phone_region_code": "AW",
                "phone_region_name": "Aruba",
                "phone_region_calling_code": "+297"
            },
            {
                "phone_region_code": "AX",
                "phone_region_name": "Åland Islands",
                "phone_region_calling_code": "+358"
            },
            {
                "phone_region_code": "AZ",
                "phone_region_name": "Azerbaijan",
                "phone_region_calling_code": "+994"
            },
            {
                "phone_region_code": "BA",
                "phone_region_name": "Bosnia and Herzegovina",
                "phone_region_calling_code": "+387"
            },
            {
                "phone_region_code": "BB",
                "phone_region_name": "Barbados",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "BD",
                "phone_region_name": "Bangladesh",
                "phone_region_calling_code": "+880"
            },
            {
                "phone_region_code": "BE",
                "phone_region_name": "Belgium",
                "phone_region_calling_code": "+32"
            },
            {
                "phone_region_code": "BF",
                "phone_region_name": "Burkina Faso",
                "phone_region_calling_code": "+226"
            },
            {
                "phone_region_code": "BG",
                "phone_region_name": "Bulgaria",
                "phone_region_calling_code": "+359"
            },
            {
                "phone_region_code": "BH",
                "phone_region_name": "Bahrain",
                "phone_region_calling_code": "+973"
            },
            {
                "phone_region_code": "BI",
                "phone_region_name": "Burundi",
                "phone_region_calling_code": "+257"
            },
            {
                "phone_region_code": "BJ",
                "phone_region_name": "Benin",
                "phone_region_calling_code": "+229"
            },
            {
                "phone_region_code": "BL",
                "phone_region_name": "Saint Barthélemy",
                "phone_region_calling_code": "+590"
            },
            {
                "phone_region_code": "BM",
                "phone_region_name": "Bermuda",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "BN",
                "phone_region_name": "Brunei Darussalam",
                "phone_region_calling_code": "+673"
            },
            {
                "phone_region_code": "BO",
                "phone_region_name": "Bolivia, Plurinational State of",
                "phone_region_calling_code": "+591"
            },
            {
                "phone_region_code": "BQ",
                "phone_region_name": "Bonaire, Sint Eustatius and Saba",
                "phone_region_calling_code": "+599"
            },
            {
                "phone_region_code": "BR",
                "phone_region_name": "Brazil",
                "phone_region_calling_code": "+55"
            },
            {
                "phone_region_code": "BS",
                "phone_region_name": "Bahamas",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "BT",
                "phone_region_name": "Bhutan",
                "phone_region_calling_code": "+975"
            },
            {
                "phone_region_code": "BW",
                "phone_region_name": "Botswana",
                "phone_region_calling_code": "+267"
            },
            {
                "phone_region_code": "BY",
                "phone_region_name": "Belarus",
                "phone_region_calling_code": "+375"
            },
            {
                "phone_region_code": "BZ",
                "phone_region_name": "Belize",
                "phone_region_calling_code": "+501"
            },
            {
                "phone_region_code": "CA",
                "phone_region_name": "Canada",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "CC",
                "phone_region_name": "Cocos (Keeling) Islands",
                "phone_region_calling_code": "+61"
            },
            {
                "phone_region_code": "CF",
                "phone_region_name": "Central African Republic",
                "phone_region_calling_code": "+236"
            },
            {
                "phone_region_code": "CG",
                "phone_region_name": "Congo",
                "phone_region_calling_code": "+242"
            },
            {
                "phone_region_code": "CH",
                "phone_region_name": "Switzerland",
                "phone_region_calling_code": "+41"
            },
            {
                "phone_region_code": "CK",
                "phone_region_name": "Cook Islands",
                "phone_region_calling_code": "+682"
            },
            {
                "phone_region_code": "CL",
                "phone_region_name": "Chile",
                "phone_region_calling_code": "+56"
            },
            {
                "phone_region_code": "CM",
                "phone_region_name": "Cameroon",
                "phone_region_calling_code": "+237"
            },
            {
                "phone_region_code": "CN",
                "phone_region_name": "China",
                "phone_region_calling_code": "+86"
            },
            {
                "phone_region_code": "CO",
                "phone_region_name": "Colombia",
                "phone_region_calling_code": "+57"
            },
            {
                "phone_region_code": "CR",
                "phone_region_name": "Costa Rica",
                "phone_region_calling_code": "+506"
            },
            {
                "phone_region_code": "CV",
                "phone_region_name": "Cape Verde",
                "phone_region_calling_code": "+238"
            },
            {
                "phone_region_code": "CW",
                "phone_region_name": "Curaçao",
                "phone_region_calling_code": "+599"
            },
            {
                "phone_region_code": "CX",
                "phone_region_name": "Christmas Island",
                "phone_region_calling_code": "+61"
            },
            {
                "phone_region_code": "CY",
                "phone_region_name": "Cyprus",
                "phone_region_calling_code": "+357"
            },
            {
                "phone_region_code": "CZ",
                "phone_region_name": "Czech Republic",
                "phone_region_calling_code": "+420"
            },
            {
                "phone_region_code": "DE",
                "phone_region_name": "Germany",
                "phone_region_calling_code": "+49"
            },
            {
                "phone_region_code": "DJ",
                "phone_region_name": "Djibouti",
                "phone_region_calling_code": "+253"
            },
            {
                "phone_region_code": "DK",
                "phone_region_name": "Denmark",
                "phone_region_calling_code": "+45"
            },
            {
                "phone_region_code": "DM",
                "phone_region_name": "Dominica",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "DO",
                "phone_region_name": "Dominican Republic",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "DZ",
                "phone_region_name": "Algeria",
                "phone_region_calling_code": "+213"
            },
            {
                "phone_region_code": "EC",
                "phone_region_name": "Ecuador",
                "phone_region_calling_code": "+593"
            },
            {
                "phone_region_code": "EE",
                "phone_region_name": "Estonia",
                "phone_region_calling_code": "+372"
            },
            {
                "phone_region_code": "EG",
                "phone_region_name": "Egypt",
                "phone_region_calling_code": "+20"
            },
            {
                "phone_region_code": "EH",
                "phone_region_name": "Western Sahara",
                "phone_region_calling_code": "+212"
            },
            {
                "phone_region_code": "ER",
                "phone_region_name": "Eritrea",
                "phone_region_calling_code": "+291"
            },
            {
                "phone_region_code": "ES",
                "phone_region_name": "Spain",
                "phone_region_calling_code": "+34"
            },
            {
                "phone_region_code": "ET",
                "phone_region_name": "Ethiopia",
                "phone_region_calling_code": "+251"
            },
            {
                "phone_region_code": "FI",
                "phone_region_name": "Finland",
                "phone_region_calling_code": "+358"
            },
            {
                "phone_region_code": "FJ",
                "phone_region_name": "Fiji",
                "phone_region_calling_code": "+679"
            },
            {
                "phone_region_code": "FK",
                "phone_region_name": "Falkland Islands (Malvinas)",
                "phone_region_calling_code": "+500"
            },
            {
                "phone_region_code": "FM",
                "phone_region_name": "Micronesia, Federated States of",
                "phone_region_calling_code": "+691"
            },
            {
                "phone_region_code": "FO",
                "phone_region_name": "Faroe Islands",
                "phone_region_calling_code": "+298"
            },
            {
                "phone_region_code": "FR",
                "phone_region_name": "France",
                "phone_region_calling_code": "+33"
            },
            {
                "phone_region_code": "GA",
                "phone_region_name": "Gabon",
                "phone_region_calling_code": "+241"
            },
            {
                "phone_region_code": "GD",
                "phone_region_name": "Grenada",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "GE",
                "phone_region_name": "Georgia",
                "phone_region_calling_code": "+995"
            },
            {
                "phone_region_code": "GF",
                "phone_region_name": "French Guiana",
                "phone_region_calling_code": "+594"
            },
            {
                "phone_region_code": "GG",
                "phone_region_name": "Guernsey",
                "phone_region_calling_code": "+44"
            },
            {
                "phone_region_code": "GH",
                "phone_region_name": "Ghana",
                "phone_region_calling_code": "+233"
            },
            {
                "phone_region_code": "GI",
                "phone_region_name": "Gibraltar",
                "phone_region_calling_code": "+350"
            },
            {
                "phone_region_code": "GL",
                "phone_region_name": "Greenland",
                "phone_region_calling_code": "+299"
            },
            {
                "phone_region_code": "GM",
                "phone_region_name": "Gambia",
                "phone_region_calling_code": "+220"
            },
            {
                "phone_region_code": "GN",
                "phone_region_name": "Guinea",
                "phone_region_calling_code": "+224"
            },
            {
                "phone_region_code": "GP",
                "phone_region_name": "Guadeloupe",
                "phone_region_calling_code": "+590"
            },
            {
                "phone_region_code": "GQ",
                "phone_region_name": "Equatorial Guinea",
                "phone_region_calling_code": "+240"
            },
            {
                "phone_region_code": "GR",
                "phone_region_name": "Greece",
                "phone_region_calling_code": "+30"
            },
            {
                "phone_region_code": "GT",
                "phone_region_name": "Guatemala",
                "phone_region_calling_code": "+502"
            },
            {
                "phone_region_code": "GU",
                "phone_region_name": "Guam",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "GW",
                "phone_region_name": "Guinea-Bissau",
                "phone_region_calling_code": "+245"
            },
            {
                "phone_region_code": "GY",
                "phone_region_name": "Guyana",
                "phone_region_calling_code": "+592"
            },
            {
                "phone_region_code": "HK",
                "phone_region_name": "Hong Kong",
                "phone_region_calling_code": "+852"
            },
            {
                "phone_region_code": "HN",
                "phone_region_name": "Honduras",
                "phone_region_calling_code": "+504"
            },
            {
                "phone_region_code": "HR",
                "phone_region_name": "Croatia",
                "phone_region_calling_code": "+385"
            },
            {
                "phone_region_code": "HT",
                "phone_region_name": "Haiti",
                "phone_region_calling_code": "+509"
            },
            {
                "phone_region_code": "HU",
                "phone_region_name": "Hungary",
                "phone_region_calling_code": "+36"
            },
            {
                "phone_region_code": "ID",
                "phone_region_name": "Indonesia",
                "phone_region_calling_code": "+62"
            },
            {
                "phone_region_code": "IE",
                "phone_region_name": "Ireland",
                "phone_region_calling_code": "+353"
            },
            {
                "phone_region_code": "IL",
                "phone_region_name": "Israel",
                "phone_region_calling_code": "+972"
            },
            {
                "phone_region_code": "IM",
                "phone_region_name": "Isle of Man",
                "phone_region_calling_code": "+44"
            },
            {
                "phone_region_code": "IN",
                "phone_region_name": "India",
                "phone_region_calling_code": "+91"
            },
            {
                "phone_region_code": "IO",
                "phone_region_name": "British Indian Ocean Territory",
                "phone_region_calling_code": "+246"
            },
            {
                "phone_region_code": "IQ",
                "phone_region_name": "Iraq",
                "phone_region_calling_code": "+964"
            },
            {
                "phone_region_code": "IS",
                "phone_region_name": "Iceland",
                "phone_region_calling_code": "+354"
            },
            {
                "phone_region_code": "IT",
                "phone_region_name": "Italy",
                "phone_region_calling_code": "+39"
            },
            {
                "phone_region_code": "JE",
                "phone_region_name": "Jersey",
                "phone_region_calling_code": "+44"
            },
            {
                "phone_region_code": "JM",
                "phone_region_name": "Jamaica",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "JO",
                "phone_region_name": "Jordan",
                "phone_region_calling_code": "+962"
            },
            {
                "phone_region_code": "JP",
                "phone_region_name": "Japan",
                "phone_region_calling_code": "+81"
            },
            {
                "phone_region_code": "KE",
                "phone_region_name": "Kenya",
                "phone_region_calling_code": "+254"
            },
            {
                "phone_region_code": "KG",
                "phone_region_name": "Kyrgyzstan",
                "phone_region_calling_code": "+996"
            },
            {
                "phone_region_code": "KH",
                "phone_region_name": "Cambodia",
                "phone_region_calling_code": "+855"
            },
            {
                "phone_region_code": "KI",
                "phone_region_name": "Kiribati",
                "phone_region_calling_code": "+686"
            },
            {
                "phone_region_code": "KM",
                "phone_region_name": "Comoros",
                "phone_region_calling_code": "+269"
            },
            {
                "phone_region_code": "KN",
                "phone_region_name": "Saint Kitts and Nevis",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "KR",
                "phone_region_name": "Korea, Republic of",
                "phone_region_calling_code": "+82"
            },
            {
                "phone_region_code": "KW",
                "phone_region_name": "Kuwait",
                "phone_region_calling_code": "+965"
            },
            {
                "phone_region_code": "KY",
                "phone_region_name": "Cayman Islands",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "KZ",
                "phone_region_name": "Kazakhstan",
                "phone_region_calling_code": "+7"
            },
            {
                "phone_region_code": "LA",
                "phone_region_name": "Lao People's Democratic Republic",
                "phone_region_calling_code": "+856"
            },
            {
                "phone_region_code": "LB",
                "phone_region_name": "Lebanon",
                "phone_region_calling_code": "+961"
            },
            {
                "phone_region_code": "LC",
                "phone_region_name": "Saint Lucia",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "LI",
                "phone_region_name": "Liechtenstein",
                "phone_region_calling_code": "+423"
            },
            {
                "phone_region_code": "LK",
                "phone_region_name": "Sri Lanka",
                "phone_region_calling_code": "+94"
            },
            {
                "phone_region_code": "LS",
                "phone_region_name": "Lesotho",
                "phone_region_calling_code": "+266"
            },
            {
                "phone_region_code": "LT",
                "phone_region_name": "Lithuania",
                "phone_region_calling_code": "+370"
            },
            {
                "phone_region_code": "LU",
                "phone_region_name": "Luxembourg",
                "phone_region_calling_code": "+352"
            },
            {
                "phone_region_code": "LV",
                "phone_region_name": "Latvia",
                "phone_region_calling_code": "+371"
            },
            {
                "phone_region_code": "LY",
                "phone_region_name": "Libya",
                "phone_region_calling_code": "+218"
            },
            {
                "phone_region_code": "MA",
                "phone_region_name": "Morocco",
                "phone_region_calling_code": "+212"
            },
            {
                "phone_region_code": "MC",
                "phone_region_name": "Monaco",
                "phone_region_calling_code": "+377"
            },
            {
                "phone_region_code": "MD",
                "phone_region_name": "Moldova, Republic of",
                "phone_region_calling_code": "+373"
            },
            {
                "phone_region_code": "ME",
                "phone_region_name": "Montenegro",
                "phone_region_calling_code": "+382"
            },
            {
                "phone_region_code": "MF",
                "phone_region_name": "Saint Martin (French part)",
                "phone_region_calling_code": "+590"
            },
            {
                "phone_region_code": "MG",
                "phone_region_name": "Madagascar",
                "phone_region_calling_code": "+261"
            },
            {
                "phone_region_code": "MH",
                "phone_region_name": "Marshall Islands",
                "phone_region_calling_code": "+692"
            },
            {
                "phone_region_code": "MK",
                "phone_region_name": "Macedonia, the Former Yugoslav Republic of",
                "phone_region_calling_code": "+389"
            },
            {
                "phone_region_code": "ML",
                "phone_region_name": "Mali",
                "phone_region_calling_code": "+223"
            },
            {
                "phone_region_code": "MN",
                "phone_region_name": "Mongolia",
                "phone_region_calling_code": "+976"
            },
            {
                "phone_region_code": "MO",
                "phone_region_name": "Macao",
                "phone_region_calling_code": "+853"
            },
            {
                "phone_region_code": "MP",
                "phone_region_name": "Northern Mariana Islands",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "MQ",
                "phone_region_name": "Martinique",
                "phone_region_calling_code": "+596"
            },
            {
                "phone_region_code": "MR",
                "phone_region_name": "Mauritania",
                "phone_region_calling_code": "+222"
            },
            {
                "phone_region_code": "MS",
                "phone_region_name": "Montserrat",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "MT",
                "phone_region_name": "Malta",
                "phone_region_calling_code": "+356"
            },
            {
                "phone_region_code": "MU",
                "phone_region_name": "Mauritius",
                "phone_region_calling_code": "+230"
            },
            {
                "phone_region_code": "MV",
                "phone_region_name": "Maldives",
                "phone_region_calling_code": "+960"
            },
            {
                "phone_region_code": "MW",
                "phone_region_name": "Malawi",
                "phone_region_calling_code": "+265"
            },
            {
                "phone_region_code": "MX",
                "phone_region_name": "Mexico",
                "phone_region_calling_code": "+52"
            },
            {
                "phone_region_code": "MY",
                "phone_region_name": "Malaysia",
                "phone_region_calling_code": "+60"
            },
            {
                "phone_region_code": "MZ",
                "phone_region_name": "Mozambique",
                "phone_region_calling_code": "+258"
            },
            {
                "phone_region_code": "NA",
                "phone_region_name": "Namibia",
                "phone_region_calling_code": "+264"
            },
            {
                "phone_region_code": "NC",
                "phone_region_name": "New Caledonia",
                "phone_region_calling_code": "+687"
            },
            {
                "phone_region_code": "NE",
                "phone_region_name": "Niger",
                "phone_region_calling_code": "+227"
            },
            {
                "phone_region_code": "NF",
                "phone_region_name": "Norfolk Island",
                "phone_region_calling_code": "+672"
            },
            {
                "phone_region_code": "NG",
                "phone_region_name": "Nigeria",
                "phone_region_calling_code": "+234"
            },
            {
                "phone_region_code": "NI",
                "phone_region_name": "Nicaragua",
                "phone_region_calling_code": "+505"
            },
            {
                "phone_region_code": "NL",
                "phone_region_name": "Netherlands",
                "phone_region_calling_code": "+31"
            },
            {
                "phone_region_code": "NO",
                "phone_region_name": "Norway",
                "phone_region_calling_code": "+47"
            },
            {
                "phone_region_code": "NP",
                "phone_region_name": "Nepal",
                "phone_region_calling_code": "+977"
            },
            {
                "phone_region_code": "NR",
                "phone_region_name": "Nauru",
                "phone_region_calling_code": "+674"
            },
            {
                "phone_region_code": "NU",
                "phone_region_name": "Niue",
                "phone_region_calling_code": "+683"
            },
            {
                "phone_region_code": "NZ",
                "phone_region_name": "New Zealand",
                "phone_region_calling_code": "+64"
            },
            {
                "phone_region_code": "OM",
                "phone_region_name": "Oman",
                "phone_region_calling_code": "+968"
            },
            {
                "phone_region_code": "PA",
                "phone_region_name": "Panama",
                "phone_region_calling_code": "+507"
            },
            {
                "phone_region_code": "PE",
                "phone_region_name": "Peru",
                "phone_region_calling_code": "+51"
            },
            {
                "phone_region_code": "PF",
                "phone_region_name": "French Polynesia",
                "phone_region_calling_code": "+689"
            },
            {
                "phone_region_code": "PG",
                "phone_region_name": "Papua New Guinea",
                "phone_region_calling_code": "+675"
            },
            {
                "phone_region_code": "PH",
                "phone_region_name": "Philippines",
                "phone_region_calling_code": "+63"
            },
            {
                "phone_region_code": "PK",
                "phone_region_name": "Pakistan",
                "phone_region_calling_code": "+92"
            },
            {
                "phone_region_code": "PL",
                "phone_region_name": "Poland",
                "phone_region_calling_code": "+48"
            },
            {
                "phone_region_code": "PM",
                "phone_region_name": "Saint Pierre and Miquelon",
                "phone_region_calling_code": "+508"
            },
            {
                "phone_region_code": "PR",
                "phone_region_name": "Puerto Rico",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "PS",
                "phone_region_name": "Palestine, State of",
                "phone_region_calling_code": "+970"
            },
            {
                "phone_region_code": "PT",
                "phone_region_name": "Portugal",
                "phone_region_calling_code": "+351"
            },
            {
                "phone_region_code": "PW",
                "phone_region_name": "Palau",
                "phone_region_calling_code": "+680"
            },
            {
                "phone_region_code": "PY",
                "phone_region_name": "Paraguay",
                "phone_region_calling_code": "+595"
            },
            {
                "phone_region_code": "QA",
                "phone_region_name": "Qatar",
                "phone_region_calling_code": "+974"
            },
            {
                "phone_region_code": "RE",
                "phone_region_name": "Réunion",
                "phone_region_calling_code": "+262"
            },
            {
                "phone_region_code": "RO",
                "phone_region_name": "Romania",
                "phone_region_calling_code": "+40"
            },
            {
                "phone_region_code": "RS",
                "phone_region_name": "Serbia",
                "phone_region_calling_code": "+381"
            },
            {
                "phone_region_code": "RU",
                "phone_region_name": "Russian Federation",
                "phone_region_calling_code": "+7"
            },
            {
                "phone_region_code": "RW",
                "phone_region_name": "Rwanda",
                "phone_region_calling_code": "+250"
            },
            {
                "phone_region_code": "SA",
                "phone_region_name": "Saudi Arabia",
                "phone_region_calling_code": "+966"
            },
            {
                "phone_region_code": "SB",
                "phone_region_name": "Solomon Islands",
                "phone_region_calling_code": "+677"
            },
            {
                "phone_region_code": "SC",
                "phone_region_name": "Seychelles",
                "phone_region_calling_code": "+248"
            },
            {
                "phone_region_code": "SE",
                "phone_region_name": "Sweden",
                "phone_region_calling_code": "+46"
            },
            {
                "phone_region_code": "SG",
                "phone_region_name": "Singapore",
                "phone_region_calling_code": "+65"
            },
            {
                "phone_region_code": "SH",
                "phone_region_name": "Saint Helena, Ascension and Tristan da Cunha",
                "phone_region_calling_code": "+290"
            },
            {
                "phone_region_code": "SI",
                "phone_region_name": "Slovenia",
                "phone_region_calling_code": "+386"
            },
            {
                "phone_region_code": "SJ",
                "phone_region_name": "Svalbard and Jan Mayen",
                "phone_region_calling_code": "+47"
            },
            {
                "phone_region_code": "SK",
                "phone_region_name": "Slovakia",
                "phone_region_calling_code": "+421"
            },
            {
                "phone_region_code": "SL",
                "phone_region_name": "Sierra Leone",
                "phone_region_calling_code": "+232"
            },
            {
                "phone_region_code": "SM",
                "phone_region_name": "San Marino",
                "phone_region_calling_code": "+378"
            },
            {
                "phone_region_code": "SN",
                "phone_region_name": "Senegal",
                "phone_region_calling_code": "+221"
            },
            {
                "phone_region_code": "SO",
                "phone_region_name": "Somalia",
                "phone_region_calling_code": "+252"
            },
            {
                "phone_region_code": "SR",
                "phone_region_name": "Suriname",
                "phone_region_calling_code": "+597"
            },
            {
                "phone_region_code": "SS",
                "phone_region_name": "South Sudan",
                "phone_region_calling_code": "+211"
            },
            {
                "phone_region_code": "ST",
                "phone_region_name": "Sao Tome and Principe",
                "phone_region_calling_code": "+239"
            },
            {
                "phone_region_code": "SV",
                "phone_region_name": "El Salvador",
                "phone_region_calling_code": "+503"
            },
            {
                "phone_region_code": "SX",
                "phone_region_name": "Sint Maarten (Dutch part)",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "SZ",
                "phone_region_name": "Swaziland",
                "phone_region_calling_code": "+268"
            },
            {
                "phone_region_code": "TC",
                "phone_region_name": "Turks and Caicos Islands",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "TD",
                "phone_region_name": "Chad",
                "phone_region_calling_code": "+235"
            },
            {
                "phone_region_code": "TG",
                "phone_region_name": "Togo",
                "phone_region_calling_code": "+228"
            },
            {
                "phone_region_code": "TH",
                "phone_region_name": "Thailand",
                "phone_region_calling_code": "+66"
            },
            {
                "phone_region_code": "TJ",
                "phone_region_name": "Tajikistan",
                "phone_region_calling_code": "+992"
            },
            {
                "phone_region_code": "TK",
                "phone_region_name": "Tokelau",
                "phone_region_calling_code": "+690"
            },
            {
                "phone_region_code": "TL",
                "phone_region_name": "Timor-Leste",
                "phone_region_calling_code": "+670"
            },
            {
                "phone_region_code": "TM",
                "phone_region_name": "Turkmenistan",
                "phone_region_calling_code": "+993"
            },
            {
                "phone_region_code": "TN",
                "phone_region_name": "Tunisia",
                "phone_region_calling_code": "+216"
            },
            {
                "phone_region_code": "TO",
                "phone_region_name": "Tonga",
                "phone_region_calling_code": "+676"
            },
            {
                "phone_region_code": "TR",
                "phone_region_name": "Turkey",
                "phone_region_calling_code": "+90"
            },
            {
                "phone_region_code": "TT",
                "phone_region_name": "Trinidad and Tobago",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "TV",
                "phone_region_name": "Tuvalu",
                "phone_region_calling_code": "+688"
            },
            {
                "phone_region_code": "TW",
                "phone_region_name": "Taiwan",
                "phone_region_calling_code": "+886"
            },
            {
                "phone_region_code": "TZ",
                "phone_region_name": "Tanzania, United Republic of",
                "phone_region_calling_code": "+255"
            },
            {
                "phone_region_code": "UA",
                "phone_region_name": "Ukraine",
                "phone_region_calling_code": "+380"
            },
            {
                "phone_region_code": "UG",
                "phone_region_name": "Uganda",
                "phone_region_calling_code": "+256"
            },
            {
                "phone_region_code": "UK",
                "phone_region_name": "United Kingdom",
                "phone_region_calling_code": "+44"
            },
            {
                "phone_region_code": "US",
                "phone_region_name": "United States",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "UY",
                "phone_region_name": "Uruguay",
                "phone_region_calling_code": "+598"
            },
            {
                "phone_region_code": "UZ",
                "phone_region_name": "Uzbekistan",
                "phone_region_calling_code": "+998"
            },
            {
                "phone_region_code": "VA",
                "phone_region_name": "Holy See (Vatican City State)",
                "phone_region_calling_code": "+39"
            },
            {
                "phone_region_code": "VC",
                "phone_region_name": "Saint Vincent and the Grenadines",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "VE",
                "phone_region_name": "Venezuela, Bolivarian Republic of",
                "phone_region_calling_code": "+58"
            },
            {
                "phone_region_code": "VG",
                "phone_region_name": "Virgin Islands, British",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "VI",
                "phone_region_name": "Virgin Islands, U.S.",
                "phone_region_calling_code": "+1"
            },
            {
                "phone_region_code": "VN",
                "phone_region_name": "Viet Nam",
                "phone_region_calling_code": "+84"
            },
            {
                "phone_region_code": "VU",
                "phone_region_name": "Vanuatu",
                "phone_region_calling_code": "+678"
            },
            {
                "phone_region_code": "WF",
                "phone_region_name": "Wallis and Futuna",
                "phone_region_calling_code": "+681"
            },
            {
                "phone_region_code": "WS",
                "phone_region_name": "Samoa",
                "phone_region_calling_code": "+685"
            },
            {
                "phone_region_code": "XK",
                "phone_region_name": "Kosovo",
                "phone_region_calling_code": "+383"
            },
            {
                "phone_region_code": "YE",
                "phone_region_name": "Yemen",
                "phone_region_calling_code": "+967"
            },
            {
                "phone_region_code": "YT",
                "phone_region_name": "Mayotte",
                "phone_region_calling_code": "+262"
            },
            {
                "phone_region_code": "ZA",
                "phone_region_name": "South Africa",
                "phone_region_calling_code": "+27"
            },
            {
                "phone_region_code": "ZM",
                "phone_region_name": "Zambia",
                "phone_region_calling_code": "+260"
            },
            {
                "phone_region_code": "GB",
                "phone_region_name": "United Kingdom",
                "phone_region_calling_code": "+44"
            }
        ]
    }
}
(/code)
```
