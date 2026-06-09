---
title: "Use split tests"
doc_id: 1742668948278274
path: "Marketing API / Campaign Management / Guides / Campaign / Super Split Test / Use split tests"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Use split tests

This article mainly introduces different use cases of split tests. 

# Create a split test

1. Create campaigns using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602).  

    Before you start, please be aware of the limitations in [Super Split Test - Campaign](https://ads.tiktok.com/marketing_api/docs?id=1742668394531842). 
    Currently, each iOS14 dedicated campaign only supports up to two split test groups. You can use[ /campaign/quota/get/](https://ads.tiktok.com/marketing_api/docs?id=1739324098894850) to check the remaining quota in your iOS14 campaign.  
	
2. Create ad groups using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). 

    Before you start, please be aware of the limitations in [Super Split Test - Ad group](https://ads.tiktok.com/marketing_api/docs?id=1742668394531842).  
	
3.  Create a split test using [/split_test/create/](https://ads.tiktok.com/marketing_api/docs?id=1742666471475201).  In the request, you can specify the key metric that you want to monitor in your test. 

	See [Super Split Test - Supported optimization goals](https://ads.tiktok.com/marketing_api/docs?id=1742668394531842&rid=2xb5zgugmdy) to find out the key metrics supported under different optimization goals.

4. View your split test results using [/split_test/result/get/](https://ads.tiktok.com/marketing_api/docs?id=1742666557045761) at least 24 hours after your test has ended.  
   
    You can get the corresponding P value of the metric that you want to monitor in the response. There will be a winning group or campaign when the P value <=0.1.  You can get the detailed metric values in reports. See [here](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) to view the supported metrics in different report types.  Based on the specific metrics and their corresponding P values, then you can determine the winning ad group or campaign by yourself. 
5. For <b>ad group</b> level split test, after you've decided the winning ad group, use [/split_test/promote/ ](https://ads.tiktok.com/marketing_api/docs?id=1742666543736834) to run the winning ad group and pause the other group in your split test. The budget of the winning ad group will be doubled. Currently, this endpoint does not support campaign level split test.


# Update the test start and end time 

If you want to update the start time and end time of a split test , use[ /split_test/update/](https://ads.tiktok.com/marketing_api/docs?id=1742666501911553). 

# Get split test results

You can use  [/split_test/result/get/](https://ads.tiktok.com/marketing_api/docs?id=1742666557045761)  to view test results after your test has ended for at least 24 hours. We will return the corresponding P value of the metric that you chose when creating the test. There will be a winning group or campaign when the P value <=0.1.

# End a split test

If you want to end a split test after the test has started, use [/split_test/end/](https://ads.tiktok.com/marketing_api/docs?id=1742666522399746). Then, both ad groups in an <b>ad group</b> level split test will be inactive. However, in a <b>campaign level</b> split test, campaigns and adgroups will still follow their own schedule, which is independent from the split test schedule. For example, if a campaign level split test has reached the end time, the split test will terminate, but the campaigns will continue running if their schedules have not yet ended.

# Check whether an ad group or a campaign is in a split test

If you want to check whether an ad group is bound to a split test, use [/adgroup/get/](https://ads.tiktok.com/marketing_api/docs?id=1739314558673922). We will return `split_test_status` and `split_test_group_id` in response. You can also use `split_test_enabled` field in [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986) and [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922) to filter out campaigns or ad groups that are split test enabled or not.





