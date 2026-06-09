---
title: "Create an MMM data request"
doc_id: 1825299567855617
path: "API Reference / Media Mix Modeling / Create an MMM data request"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an MMM data request

Use this endpoint to create a Media Mix Modeling (MMM) data request.

After you create the data request, wait for up to 24 hours for the data processing to complete. Then use [/mmm/api/check/](https://business-api.tiktok.com/portal/docs/check-the-status-of-an-mmm-data-request/v1.3) to check the data request status.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/mmm/api/create/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_ids {Required} | string[] | A list of advertiser IDs to request MMM data for. <br><br> We recommend limiting the number of advertiser IDs to 5. <br><br> To obtain a list of ad accounts within a Business Center that you have access to, use[ /bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401) and set `asset_type` to `ADVERTISER`. |
| request_info {Required} | object | Details of the data request. |
#| targets {Required} | object[] | Details of the ad accounts and campaigns. <br><br> The `advertiser_id` value within `targets` should correspond to the specified `advertiser_ids`. |
##| advertiser_id {Required} | string | Advertiser ID. |
##| campaign_ids| string[] | The ID list of campaigns within the ad account to filter the data by. <br><br> If you don't specify `campaign_ids`, data for all campaigns within the ad account will be returned. <br><br>To obtain a list of campaigns within an ad account, use [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986).|
##| brand | string | Brand. <br>You can use this field to distinguish between data requests for different brands.<br><br>If you specify this field, the value will be filled in the "Brand" column of the data file. |
##| sub_brand | string | Sub-brand. <br>You can use this field to distinguish between data requests for different sub-brands.<br><br>If you specify this field, the value will be filled in the "Sub-brand" column of the data file. |
#| from_date {Required} | string | Start date, in the format of `YYYY-MM-DD` (UTC+0 time).<br><br> The start date cannot be earlier than four years ago. |
#| to_date {Required} | string | End date, in the format of `YYYY-MM-DD` (UTC+0 time). |
#| country_code | string[] | Two-letter code of the country or region.<br><br>For enum values, see <a href="#item-link-List of values for country_code">List of values for `country_code`</a>. <br><br>If this field is not specified, data for all countries and regions will be returned. <br><br> Example: `US`. |
#| media_type | string | Media type.<br><br> Enum values: <ul><li>`All` (recommended): Both paid and earned media.</li><li> `Paid`: Paid media. Advertising you have paid for directly, such as TV commercials and social media ads.</li><li>`Earned`: Earned media. Advertising gained through organic means, such as social media mentions.</li></ul>Default value: `All`.|
| email | string | The email address to receive the notification when the data is ready for download. |
```
### List of values for `country_code`
```xtable
| Value{30%} | Description{70%} |
|---|---|
| `AD` | Andorra |
| `AE` | United Arab Emirates |
| `AF` | Afghanistan |
| `AG` | Antigua and Barbuda |
| `AI` | Anguilla |
| `AL` | Albania |
| `AM` | Armenia |
| `AO` | Angola |
| `AQ` | Antarctica |
| `AR` | Argentina |
| `AS` | American Samoa |
| `AT` | Austria |
| `AU` | Australia |
| `AW` | Aruba |
| `AX` | Åland |
| `AZ` | Azerbaijan |
| `BA` | Bosnia & Herzegovina |
| `BB` | Barbados |
| `BD` | Bangladesh |
| `BE` | Belgium |
| `BF` | Burkina Faso |
| `BG` | Bulgaria |
| `BH` | Bahrain |
| `BI` | Burundi |
| `BJ` | Benin |
| `BL` | Saint Barthélemy |
| `BM` | Bermuda |
| `BN` | Brunei |
| `BO` | Bolivia |
| `BQ` | Caribbean Netherlands |
| `BR` | Brazil |
| `BS` | Commonwealth of the Bahamas |
| `BT` | Bhutan |
| `BV` | Bouvet Island |
| `BW` | Botswana |
| `BY` | Belarus |
| `BZ` | Belize |
| `CA` | Canada |
| `CC` | Cocos (Keeling) Islands |
| `CD` | Democratic Republic of the Congo |
| `CF` | Central African Republic |
| `CG` | Republic of the Congo |
| `CH` | Switzerland |
| `CI` | Côte D'Ivoire |
| `CK` | Cook Islands |
| `CL` | Chile |
| `CM` | Cameroon |
| `CN` | the Chinese mainland |
| `CO` | Colombia |
| `CR` | Costa Rica |
| `CU` | Cuba |
| `CV` | Cape Verde |
| `CX` | Christmas Island |
| `CY` | Cyprus |
| `CZ` | Czech Republic |
| `DE` | Germany |
| `DJ` | Djibouti |
| `DK` | Denmark |
| `DM` | Dominica |
| `DO` | Dominican Republic |
| `DZ` | Algeria |
| `EC` | Ecuador |
| `EE` | Estonia |
| `EG` | Egypt |
| `EH` | Western Sahara |
| `ER` | Eritrea |
| `ES` | Spain |
| `ET` | Ethiopia |
| `FI` | Finland |
| `FJ` | Fiji |
| `FK` | Falkland Islands |
| `FM` | Micronesia |
| `FO` | Faroe Islands |
| `FR` | France |
| `GA` | Gabon |
| `GB` | United Kingdom |
| `GD` | Grenada |
| `GE` | Georgia |
| `GF` | French Guiana |
| `GG` | Guernsey |
| `GH` | Ghana |
| `GI` | Gibraltar |
| `GL` | Greenland |
| `GM` | Gambia |
| `GN` | Guinea |
| `GP` | Guadeloupe |
| `GQ` | Equatorial Guinea |
| `GR` | Greece |
| `GS` | South Georgia and the South Sandwich Islands |
| `GT` | Guatemala |
| `GU` | Guam |
| `GW` | Guinea-Bissau |
| `GY` | Guyana |
| `HK` | Hong Kong, China |
| `HM` | Heard Island and McDonald Islands |
| `HN` | Honduras |
| `HR` | Croatia |
| `HT` | Haiti |
| `HU` | Hungary |
| `ID` | Indonesia |
| `IE` | Ireland |
| `IL` | Israel |
| `IM` | Isle of Man |
| `IN` | India |
| `IO` | British Indian Ocean Territory |
| `IQ` | Iraq |
| `IR` | Iran |
| `IS` | Iceland |
| `IT` | Italy |
| `JE` | Jersey |
| `JM` | Jamaica |
| `JO` | Jordan |
| `JP` | Japan |
| `KE` | Kenya |
| `KG` | Kyrgyzstan |
| `KH` | Cambodia |
| `KI` | Kiribati |
| `KM` | Comoros |
| `KN` | Saint Christopher and Nevis |
| `KP` | North Korea |
| `KR` | South Korea |
| `KW` | Kuwait |
| `KY` | Cayman Islands |
| `KZ` | Kazakhstan |
| `LA` | Laos |
| `LB` | Lebanon |
| `LC` | Saint Lucia |
| `LI` | Liechtenstein |
| `LK` | Sri Lanka |
| `LR` | Liberia |
| `LS` | Lesotho |
| `LT` | Lithuania |
| `LU` | Luxembourg |
| `LV` | Latvia |
| `LY` | Libya |
| `MA` | Morocco |
| `MC` | Monaco |
| `MD` | Moldova |
| `ME` | Montenegro |
| `MG` | Madagascar |
| `MH` | Marshall Islands |
| `MK` | Macedonia |
| `ML` | Mali |
| `MM` | Myanmar |
| `MN` | Mongolia |
| `MO` | Macao, China |
| `MP` | Northern Mariana Islands |
| `MQ` | Martinique |
| `MR` | Mauritania |
| `MS` | Montserrat |
| `MT` | Malta |
| `MU` | Mauritius |
| `MV` | Maldives |
| `MW` | Malawi |
| `MX` | Mexico |
| `MY` | Malaysia |
| `MZ` | Mozambique |
| `NA` | Namibia |
| `NC` | New Caledonia |
| `NE` | Niger |
| `NF` | Norfolk Island |
| `NG` | Nigeria |
| `NI` | Nicaragua |
| `NL` | Netherlands |
| `NO` | Norway |
| `NP` | Nepal |
| `NR` | Nauru |
| `NU` | Niue |
| `NZ` | New Zealand |
| `OM` | Oman |
| `PA` | Panama |
| `PE` | Peru |
| `PF` | French Polynesia |
| `PG` | Papua New Guinea |
| `PH` | Philippines |
| `PK` | Pakistan |
| `PL` | Poland |
| `PM` | Saint Pierre and Miquelon |
| `PN` | Pitcairn Islands |
| `PR` | Puerto Rico |
| `PS` | Palestine |
| `PT` | Portugal |
| `PW` | Palau |
| `PY` | Paraguay |
| `QA` | Qatar |
| `RE` | Réunion |
| `RO` | Romania |
| `RS` | Serbia |
| `RU` | Russia |
| `RW` | Rwanda |
| `SA` | Saudi Arabia |
| `SB` | Solomon Islands |
| `SC` | Seychelles |
| `SD` | Sudan |
| `SE` | Sweden |
| `SG` | Singapore |
| `SH` | Saint Helena |
| `SI` | Slovenia |
| `SJ` | Svalbard and Jan Mayen |
| `SK` | Slovakia |
| `SL` | Sierra Leone |
| `SM` | San Marino |
| `SN` | Senegal |
| `SO` | Somalia |
| `SR` | Suriname |
| `SS` | South Sudan |
| `ST` | São Tomé and Príncipe |
| `SV` | El Salvador |
| `SY` | Syria |
| `SZ` | Swaziland |
| `TC` | Turks and Caicos Islands |
| `TD` | Chad |
| `TF` | French Southern and Antarctic Lands |
| `TG` | Togo |
| `TH` | Thailand |
| `TJ` | Tajikistan |
| `TK` | Tokelau |
| `TL` | Timor-Leste |
| `TM` | Turkmenistan |
| `TN` | Tunisia |
| `TO` | Tonga |
| `TR` | Turkey |
| `TT` | Trinidad and Tobago |
| `TV` | Tuvalu |
| `TW` | Taiwan, China |
| `TZ` | Tanzania |
| `UA` | Ukraine |
| `UG` | Uganda |
| `UM` | United States Minor Outlying Islands |
| `US` | United States |
| `UY` | Uruguay |
| `UZ` | Uzbekistan |
| `VA` | Vatican City |
| `VC` | Saint Vincent and The Grenadines |
| `VE` | Venezuela |
| `VG` | British Virgin Islands |
| `VI` | Virgin Islands of the United States |
| `VN` | Vietnam |
| `VU` | Vanuatu |
| `WF` | Wallis and Futuna |
| `WS` | Samoa |
| `YE` | Yemen |
| `YT` | Mayotte |
| `ZA` | South Africa |
| `ZM` | Zambia |
| `ZW` | Zimbabwe |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/mmm/api/create/' \
--header 'Access-Token: {{Access-Token}}'
--header 'Content-Type: application/json' \
--data-raw '{
    "advertiser_ids": [
        "{{advertiser_id}}"
    ],
    "request_info": {
        "targets": [
            {
                "advertiser_id": "{{advertiser_id}}"
            }
        ],
        "from_date": "2024-01-01",
        "to_date": "2024-01-03",
        "country_code": [
            "JP","US","CA"
        ],
        "media_type": "All"
    },
    "email": "{{email}}"
}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| mmm_request_id | string | Unique identifier for the MMM data request.<br><br>To check the data request status, wait for up to 24 hours for the data processing to complete, and then pass this value of this field to `mmm_request_id` in [/mmm/api/check/](https://business-api.tiktok.com/portal/docs/check-the-status-of-an-mmm-data-request/v1.3). |
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
        "mmm_request_id": "{{mmm_request_id}}"
    }
}
(/code)
```

