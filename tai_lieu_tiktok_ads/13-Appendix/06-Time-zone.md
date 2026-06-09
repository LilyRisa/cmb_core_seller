---
title: "Time zone"
doc_id: 1737586324313089
path: "Appendix / Time zone"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Time zone

The following table lists the available time zone codes and the corresponding time zones. To retrieve the complete list of time zone codes, use [/tool/timezone/](https://business-api.tiktok.com/portal/docs?id=1738455961470977).

You can use the time zone codes in the following API operations:
- Automated Rules:  
  - While creating, updating, or retrieving Automated Rules via [/optimizer/rule/create/](https://business-api.tiktok.com/portal/docs?id=1738767670852610), [/optimizer/rule/update/](https://business-api.tiktok.com/portal/docs?id=1738769123170306), or [/optimizer/rule/list/](https://business-api.tiktok.com/portal/docs?id=1738768861976578), pass the time zone code to the request parameter `tzone`.
- Change log download:
  - While creating a change log download task via [/changelog/task/create/](https://business-api.tiktok.com/portal/docs?id=1738811219460097), pass the time zone code to the request parameter `timezone`.
- Business Center:
  - While creating an ad account in a Business Center via [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721),  pass the time zone code to the request parameter `timezone`.
- Custom Audiences:
  - While retrieving the latest application log of custom audiences via [/dmp/custom_audience/apply/log/](https://business-api.tiktok.com/portal/docs?id=1758615737388033),  pass the time zone code to the request parameter `timezone`.
- Catalog feed:
  - While creating a catalog feed via [/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377),  pass the time zone code to the request parameter `timezone`.
  
  
```xtable
|Code{40%}|Time{60%}|
| --- | --- |
|Africa/Accra|UTC+00:00|
|Africa/Cairo|UTC+02:00|
|Africa/Casablanca|UTC+01:00|
|Africa/Johannesburg|UTC+02:00|
|Africa/Lagos|UTC+01:00|
|Africa/Nairobi|UTC+03:00|
|Africa/Tunis|UTC+01:00|
|America/Anchorage|UTC-09:00|
|America/Argentina/Buenos_Aires|UTC-03:00|
|America/Argentina/Salta|UTC-03:00|
|America/Argentina/San_Luis|UTC-03:00|
|America/Asuncion|UTC-03:00|
|America/Atikokan|UTC-05:00|
|America/Belem|UTC-03:00|
|America/Blanc-Sablon|UTC-04:00|
|America/Bogota|UTC-05:00|
|America/Campo_Grande|UTC-04:00|
|America/Caracas|UTC-04:00|
|America/Chicago|UTC-06:00|
|America/Costa_Rica|UTC-06:00|
|America/Dawson|UTC-08:00|
|America/Dawson_Creek|UTC-07:00|
|America/Denver|UTC-07:00|
|America/Edmonton|UTC-07:00|
|America/El_Salvador|UTC-06:00|
|America/Guatemala|UTC-06:00|
|America/Guayaquil|UTC-05:00|
|America/Halifax|UTC-04:00|
|America/Hermosillo|UTC-07:00|
|America/Iqaluit|UTC-05:00|
|America/Jamaica|UTC-05:00|
|America/La_Paz|UTC-04:00|
|America/Lima|UTC-05:00|
|America/Los_Angeles|UTC-08:00|
|America/Managua|UTC-06:00|
|America/Mazatlan|UTC-07:00|
|America/Mexico_City|UTC-06:00|
|America/Montevideo|UTC-03:00|
|America/Nassau|UTC-05:00|
|America/New_York|UTC-05:00|
|America/Noronha|UTC-02:00|
|America/Panama|UTC-05:00|
|America/Phoenix|UTC-07:00|
|America/Port_of_Spain|UTC-04:00|
|America/Puerto_Rico|UTC-04:00|
|America/Rainy_River|UTC-06:00|
|America/Regina|UTC-06:00|
|America/Santiago|UTC-04:00|
|America/Santo_Domingo|UTC-04:00|
|America/Sao_Paulo|UTC-03:00|
|America/St_Johns|UTC-04:00|
|America/Tegucigalpa|UTC-06:00|
|America/Tijuana|UTC-08:00|
|America/Toronto|UTC-05:00|
|America/Vancouver|UTC-08:00|
|Asia/Amman|UTC+02:00|
|Asia/Baghdad|UTC+03:00|
|Asia/Bahrain|UTC+03:00|
|Asia/Bangkok|UTC+07:00|
|Asia/Beirut|UTC+02:00|
|Asia/Colombo|UTC+05:30|
|Asia/Dhaka|UTC+06:00|
|Asia/Dubai|UTC+04:00|
|Asia/Gaza|UTC+02:00|
|Asia/Ho_Chi_Minh|UTC+07:00|
|Asia/Hong_Kong|UTC+08:00|
|Asia/Irkutsk|UTC+08:00|
|Asia/Jakarta|UTC+07:00|
|Asia/Jayapura|UTC+09:00|
|Asia/Jerusalem|UTC+02:00|
|Asia/Kamchatka|UTC+12:00|
|Asia/Karachi|UTC+05:00|
|Asia/Kolkata|UTC+05:30|
|Asia/Krasnoyarsk|UTC+07:00|
|Asia/Kuala_Lumpur|UTC+08:00|
|Asia/Kuwait|UTC+03:00|
|Asia/Magadan|UTC+11:00|
|Asia/Makassar|UTC+08:00|
|Asia/Manila|UTC+08:00|
|Asia/Muscat|UTC+04:00|
|Asia/Nicosia|UTC+02:00|
|Asia/Omsk|UTC+06:00|
|Asia/Phnom_Penh|UTC+07:00|
|Asia/Qatar|UTC+03:00|
|Asia/Riyadh|UTC+03:00|
|Asia/Seoul|UTC+09:00|
|Asia/Shanghai|UTC+08:00|
|Asia/Singapore|UTC+08:00|
|Asia/Taipei|UTC+08:00|
|Asia/Tokyo|UTC+09:00|
|Asia/Vladivostok|UTC+10:00|
|Asia/Yakutsk|UTC+09:00|
|Asia/Yekaterinburg|UTC+05:00|
|Atlantic/Azores|UTC-01:00|
|Atlantic/Canary|UTC+00:00|
|Atlantic/Reykjavik|UTC+00:00|
|Australia/Broken_Hill|UTC+09:00|
|Australia/Perth|UTC+08:00|
|Australia/Sydney|UTC+10:00|
|Europe/Amsterdam|UTC+01:00|
|Europe/Athens|UTC+02:00|
|Europe/Belgrade|UTC+01:00|
|Europe/Berlin|UTC+01:00|
|Europe/Bratislava|UTC+01:00|
|Europe/Brussels|UTC+01:00|
|Europe/Bucharest|UTC+02:00|
|Europe/Budapest|UTC+01:00|
|Europe/Copenhagen|UTC+01:00|
|Europe/Dublin|UTC+01:00|
|Europe/Helsinki|UTC+02:00|
|Europe/Istanbul|UTC+03:00|
|Europe/Kaliningrad|UTC+02:00|
|Europe/Kiev|UTC+02:00|
|Europe/Lisbon|UTC+00:00|
|Europe/Ljubljana|UTC+01:00|
|Europe/London|UTC+00:00|
|Europe/Luxembourg|UTC+01:00|
|Europe/Madrid|UTC+01:00|
|Europe/Malta|UTC+01:00|
|Europe/Moscow|UTC+03:00|
|Europe/Oslo|UTC+01:00|
|Europe/Paris|UTC+01:00|
|Europe/Prague|UTC+01:00|
|Europe/Riga|UTC+02:00|
|Europe/Rome|UTC+01:00|
|Europe/Samara|UTC+04:00|
|Europe/Sarajevo|UTC+01:00|
|Europe/Skopje|UTC+01:00|
|Europe/Sofia|UTC+02:00|
|Europe/Stockholm|UTC+01:00|
|Europe/Tallinn|UTC+02:00|
|Europe/Vienna|UTC+01:00|
|Europe/Vilnius|UTC+02:00|
|Europe/Warsaw|UTC+01:00|
|Europe/Zagreb|UTC+01:00|
|Europe/Zurich|UTC+01:00|
|Indian/Maldives|UTC+05:00|
|Indian/Mauritius|UTC+04:00|
|Pacific/Apia|UTC+13:00|
|Pacific/Auckland|UTC+12:00|
|Pacific/Easter|UTC-06:00|
|Pacific/Galapagos|UTC-06:00|
|Pacific/Honolulu|UTC-10:00|
|Pacific/Majuro|UTC+12:00|
|Pacific/Midway|UTC-11:00|
|Pacific/Niue|UTC-11:00|
|Pacific/Pago_Pago|UTC-11:00|
```
