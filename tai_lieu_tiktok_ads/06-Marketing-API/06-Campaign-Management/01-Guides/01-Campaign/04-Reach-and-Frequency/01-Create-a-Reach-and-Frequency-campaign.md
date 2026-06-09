---
title: "Create a Reach & Frequency campaign"
doc_id: 1744571205201922
path: "Marketing API / Campaign Management / Guides / Campaign / Reach & Frequency / Create a Reach & Frequency campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Reach & Frequency campaign

This article introduces how to create a Reach & Frequency (R&F) campaign. You need to perform the following steps to set up a R&F campaign:

1. Create a Reach & Frequency campaign.
2. Obtain estimated available ad inventory.
3. [Optional] Create a budget & impression or budget & reach chart.
4. Create an ad group.
5. Create ads.

> <span style="color: DodgerBlue">**Note**</span><br>The Reach and Frequency feature is in GA. Developers can build integrations with this feature. However, the integration would only work if the ad accounts are allowlisted for this feature. To have your ad accounts allowlisted, please reach out to your TikTok representative.

# Prerequisite
You have reached out to your TikTok representative to apply for the allowlist, and signed a Commercial Contract for Branding.

# 1. Create a Reach & Frequency campaign
Use [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602) to create a Reach & Frequency (R&F) campaign. 
  - Advertising objectives:  Currently, only `RF_REACH` advertising objective is available for R&F type ads. Therefore, you will need to set `objective_type` to `RF_REACH`.
  
# 2. Get inventory estimate
 
Use [/rf/inventory/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1738235487941633) to get the inventory estimate.  Ad inventory is estimated based on advertising objectives, placements, targeting, schedule, show frequency and reservation settings. 

1. Reservation methods
- You can reserve using fixed budget, fixed impressions, or fixed reach. 
	 
  -  **Reserve using fixed budget**<br>
Set `rf_purchased_type` to `FIXED_BUDGET`, and then enter an amount in `budget`. The system will use the budget amount to predict impressions, reach, frequency and CPM. 
  - **Reserve using fixed impressions**<br>
Set `rf_purchased_type` to `FIXED_SHOW`, and then enter the number of impressions in `purchased_impression`. The system will use the impressions to predict reach, frequency, CPM, and budget.
  - **Reserve using fixed reach**<br>
Set `rf_purchased_type` to `FIXED_REACH`, and then enter the number of people you want to reach in `purchased_reach`. The system will use the reach to predict impression, frequency, CPM, and budget. 
			
Depending on the fixed value you have specified, we will return the corresponding predicted values in the `default_result` object in the response. For example, if you set `rf_purchased_type` to `FIXED_SHOW` and specify a fixed value in `purchased_impression`, then based on the impressions you set, we will calculate and return the estimated values for `budget`, `cpm`, `reach` and `average_frequency` in `default_result`. 

> <span style="color: DodgerBlue">**Note**</span><br>The amount that you enter in `budget`, `purchased_impression` or `purchased_reach` must be within the range of minimum and maximum values returned in the `base_info` object in `results`. Otherwise, we will return the maximum estimate values instead of the values reserved using fixed amounts in `default_result`.

 **Example:**
Below is an example of choosing `FIXED_SHOW` as the reservation method. 

**Request**
```xcodeblock
(code)
{
    "advertiser_id": "123",
    "audience_info": {
        "location_ids": [
            "6252001"
        ],
        "network_types": ["5G"],
        "carrier_ids":["3100000", "3100001", "3100002", "3100003"],
        "operating_systems": ["IOS"],
        "brand_safety_type":"LIMITED_INVENTORY",
        "contextual_tag_ids":[18,19]
    },
    "schedule_start_time": "2022-09-28 05:00:00",
    "schedule_end_time": "2022-10-02 04:59:59",
    "frequency": 3,
    "frequency_schedule": 5,
    "objective_type": "RF_REACH",
    "rf_purchased_type": "FIXED_SHOW",
    "purchased_impression":800
}
(/code)
```

**Response**
```xcodeblock
(code)
{
    "code": 0,
    "message": "OK",
    "request_id": "202209150956440102452480581B06C634",
    "data": {
        "default_result": {
            "base_info": {
                "impression": 800,
                "cpm": 5.78,
                "budget": 4624.12,
                "reach": 520,
                "average_frequency": 1.54
            },
            "frequency_per_person": [
                {
                    "percentage": 0.56,
                    "frequency": 1
                },
                {
                    "percentage": 0.26,
                    "frequency": 2
                },
                {
                    "percentage": 0.18,
                    "frequency": 3
                }
            ],
            "daily_cost": [
                {
                    "cost": 2357.82,
                    "date": "2022-09-28"
                },
                {
                    "cost": 2296.15,
                    "date": "2022-09-29"
                },
                {
                    "cost": 1728.11,
                    "date": "2022-09-30"
                },
                {
                    "cost": 1956.63,
                    "date": "2022-10-01"
                }
            ]
        },
        "results": [
            {
                "base_info": {
                    "impression": 131,
                    "cpm": 4.19,
                    "budget": 549.11,
                    "reach": 104,
                    "average_frequency": 1.26
                },
                "frequency_per_person": [
                    {
                        "percentage": 0.77,
                        "frequency": 1
                    },
                    {
                        "percentage": 0.21,
                        "frequency": 2
                    },
                    {
                        "percentage": 0.02,
                        "frequency": 3
                    }
                ],
                "daily_cost": [
                    {
                        "cost": 152.83,
                        "date": "2022-09-28"
                    },
                    {
                        "cost": 148.31,
                        "date": "2022-09-29"
                    },
                    {
                        "cost": 140.64,
                        "date": "2022-09-30"
                    },
                    {
                        "cost": 107.33,
                        "date": "2022-10-01"
                    }
                ]
            },
            {
                "base_info": {
                    "impression": 140,
                    "cpm": 4.21,
                    "budget": 589.73,
                    "reach": 111,
                    "average_frequency": 1.26
                },
                "frequency_per_person": [
                    {
                        "percentage": 0.76,
                        "frequency": 1
                    },
                    {
                        "percentage": 0.22,
                        "frequency": 2
                    },
                    {
                        "percentage": 0.02,
                        "frequency": 3
                    }
                ],
                "daily_cost": [
                    {
                        "cost": 172.76,
                        "date": "2022-09-28"
                    },
                    {
                        "cost": 154.3,
                        "date": "2022-09-29"
                    },
                    {
                        "cost": 141.89,
                        "date": "2022-09-30"
                    },
                    {
                        "cost": 120.77,
                        "date": "2022-10-01"
                    }
                ]
            },
            {
                "base_info": {
                    "impression": 150,
                    "cpm": 4.27,
                    "budget": 639.83,
                    "reach": 118,
                    "average_frequency": 1.27
                },
                "frequency_per_person": [
                    {
                        "percentage": 0.75,
                        "frequency": 1
                    },
                    {
                        "percentage": 0.23,
                        "frequency": 2
                    },
                    {
                        "percentage": 0.02,
                        "frequency": 3
                    }
                ],
                "daily_cost": [
                    {
                        "cost": 174.2,
                        "date": "2022-09-28"
                    },
                    {
                        "cost": 174.44,
                        "date": "2022-09-29"
                    },
                    {
                        "cost": 152.61,
                        "date": "2022-09-30"
                    },
                    {
                        "cost": 138.58,
                        "date": "2022-10-01"
                    }
                ]
            }
        ]
    }
}
(/code)
```
- Or if you don't specify any fixed values for `budget`, `purchased_impression` or `purchased_reach`, then from the `results` object in response, you can select `budget`, `impression` , `reach` and `cpm` that most closely approximate to your expectations. 

2. Schedule start time and end time 
<br> Currently, the minimum unit for inventory estimation is days. Therefore, in your request, `schedule_start_time` must be at YYYY-MM-DD HH:00:00, and `schedule_end_time` at YYYY-MM-DD HH:59:59 for the time zone where the ad will be delivered.

**Example:**

For an advertiser in the UTC-5 time zone who wants to get estimation from Nov 3, 2020 to Nov 10, 2020, they need to configure the following parameters:

```xcodeblock
(code)
{
  "schedule_start_time": "2020-11-03 05:00:00", # UTC+0 time
  "schedule_end_time": "2020-11-11 04:59:59"  # UTC+0 time
}
(/code)
```
>  <span style="color:DodgerBlue">**Note**</span><br>Since ad inventory is constantly changing, the inventory estimates are time-sensitive. If too much time elapses between your inventory estimate request and your inventory reservation request, your inventory reservation may be unsuccessful. This is usually because of sufficient ad inventory or fluctuations in the impressions-to-budget ratio.<br> <br>The impressions (`impression`) and users reached (`reach`) included in the inventory estimate response are measured in units of 1,000. For example, if the inventory response is `impression=200` and `reach=140`, the estimated impressions would be `200 x 1,000` impressions, and the estimated users reached would be `140 x 1,000` users.

# 3. [Optional] Create a budget & impression or budget & reach chart

Based on the budget, impression and reach values returned in the response, you can create a chart that shows the estimated budget for a targeting impression or reach.<br> 

This is an example code about extracting budget and impression numbers from the `results` object in the response, and drawing a chart.

``` xcodeblock
(code)
import json
import numpy as np
import matplotlib.pyplot as plt

f = open('result.json')
data = json.load(f)
results = data['data']['results']
budget = []
impression = []
for result in results:
    budget.append(result['base_info']['budget'])
    impression.append(result['base_info']['impression'])

line = plt.plot(budget, impression)
plt.show()

f.close()
(/code)
```

# 4. Create an ad group
Use the [/adgroup/rf/create/](https://ads.tiktok.com/marketing_api/docs?id=1738235338194945) endpoint to create an R&F ad group.
  - Target audience: Audience can be categorized based on their age, gender, language, region, and operating system. 
  - Placement: The system sets R&F placements to TikTok placement by default. You don't need to specify the field in your request.   
  - Schedule (`schedule_start_time` and `schedule_end_time`):
    - The budget for R&F ads must be locked 5 minutes before delivery. If the difference between ad group creation time and 00:00 the next day ( for the time zone of the location where the ad will be delivered ) is less than 5 minutes, you might need to set the start time to `[the day after tomorrow until 90 days from today]`. Otherwise, you can set the start time to `[tomorrow until 90 days from today]`.
    - Currently, the duration of R&F ads is days. Therefore, in your request, `schedule_start_time` must be at YYYY-MM-DD HH:00:00, and `schedule_end_time` at YYYY-MM-DD HH:59:59 for the time zone where the ad will be delivered.
    - The time interval between `schedule_start_time` and `schedule_end_time` should not exceed 90 days. 
	
**Example:**
For an advertiser in the UTC-5 time zone who wants to create an R&F ad group from Nov 3, 2020 to Nov 10, 2020, they need to configure the following parameters:
```xcodeblock
(code)
{
  "schedule_start_time": "2020-11-03 05:00:00", # UTC+0 time
  "schedule_end_time": "2020-11-11 04:59:59"  # UTC+0 time
}
(/code)
```

- Reservation
    - Set `rf_purchased_type` to `FIXED_SHOW`, `FIXED_REACH` or `FIXED_BUDGET` as your reservation and purchase method.
    - Based on the data returned via [/rf/inventory/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1738235487941633), specify an amount in `budget`, `purchased_impression` and `purchased_reach`.  

       * If you have specified `rf_purchased_type` and entered a fixed value for `budget`, `purchased_impression` or `purchased_reach` in [/rf/inventory/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1738235487941633),  then you can find the corresponding estimates in the `base_info` object (in `default_result`) in the response. The next step is to pass these values into the `budget`, `purchased_impression` and `purchased_reach` fields in [/adgroup/rf/create/](https://ads.tiktok.com/marketing_api/docs?id=1738235338194945). *

       * **OR** if no fixed values are set for `budget`, `purchased_impression` or `purchased_reach` in [/rf/inventory/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1738235487941633), then you can select your desired budget, impression and reach that are returned in the `base_info` object (in `results`). The next step is to pass these values into the `budget`, `purchased_impression` and `purchased_reach` fields in [/adgroup/rf/create/](https://ads.tiktok.com/marketing_api/docs?id=1738235338194945). 
  - Optimization goals: The optimization goal (`optimization_goal`) at the ad group level must be aligned with the advertising objectives (`objective_type`) at the campaign level.
  - Frequency: Use `frequency` and `frequency_schedule` to determine how often you want people to see your ads. 
  
**Example:** 

**Request parameters**
```xcodeblock
(code)
{
    "comment_disabled": false,
    "share_disabled": false,
    "optimization_goal": "REACH",
    "rf_purchased_type": "FIXED_SHOW",
    "purchased_reach": 520,
    "purchased_impression": 800,
    "video_download_disabled": false,
    "age_groups": ["AGE_18_24"],
    "operating_systems": ["IOS"],
    "network_types": ["WIFI"],
    "brand_safety_type": "NO_BRAND_SAFETY",
    "external_action": "STANDARD_FEED",
    "advertiser_id":{{advertise_id}},
    "schedule_start_time":"2023-01-10 05:00:00",
    "schedule_end_time":"2023-02-12 04:59:59",
    "frequency":3,
    "frequency_schedule":7,
    "objective_type":"RF_REACH",
    "location_ids":["6252001"],
    "request_id":{{request_Id}},
    "campaign_id":{{campaign_id}},
    "rf_buy_type":"FIXED_SHOW",
    "optimize_goal":"REACH",
    "budget":4624.12,
    "adgroup_name":"xxx",
    "promotion_type":"APP_IOS",
    "display_mode":"STANDARD",
    "excluded_audience_ids": [
        {{excluded_audience_ids}}
    ]
}
(/code)
```
# 5. Create an ad
Use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) to create an R&F ad. 
> <span style="color: DodgerBlue">**Note**</span><br>R&F ads only support video ad creatives. 
 
