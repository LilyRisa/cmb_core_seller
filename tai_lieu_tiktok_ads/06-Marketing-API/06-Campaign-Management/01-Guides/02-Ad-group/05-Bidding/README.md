---
title: "Bidding"
doc_id: 1745292444424193
path: "Marketing API / Campaign Management / Guides / Ad group / Bidding"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Bidding

Bidding is important to the overall ad creation process. You can use the following bidding features to run your ads. 

# Bid strategy
The bid strategy (`bid_type`) determines how the system manages your cost per result, spends your budget, and delivers ads. See [here](https://ads.tiktok.com/help/article?aid=6731899932019523590) for detailed introductions. 

We provide the following two bidding strategies: 
- `BID_TYPE_CUSTOM`: Manual bidding. This bidding strategy is called **Cost Cap** in TikTok Ads Manager.
- `BID_TYPE_NO_BID`: Disable bid control, spending your budget fully and getting the maximum possible results. This bidding strategy is called **Maximum Delivery** in TikTok Ads Manager.

# Billing event 
Billing event (`billing_event`) determines when you pay for your ad. See [here](https://ads.tiktok.com/help/article?aid=9686) for detailed introductions. 

We provide the following billing events: 
- `CPC`: Your bid is the cost you are willing to pay per click.
- `CPM`: Your bid is the price you are willing to pay for one thousand impressions. 
- `CPV`: Your bid is the price you are willing to pay for per view.
- `OCPC`: Your bid is the optimized cost you are willing to pay per click.
- `GD`: Guaranteed delivery. 
- `OCPM`: Your bid is the optimized price you are willing to pay for one thousand impressions. 

# Bidding limits
Bidding limits are determined by the currency of your ad account, the selected billing event, and the budget at the ad group level and campaign level. To specify a bid, you need to set `bid_type` as `BID_TYPE_CUSTOM` at the ad group level and then pass in `bid_price` or `conversion_bid_price`. The verification of the bid price you specify is threefold:
1. Currency precision. Different currencies may vary in the maximum decimal places you can specify. For instance, for US Dollar (USD) you can keep two decimal places at most, but for Japanese Yen (JPY) you can only specify integers.
2. Bid price limit. The upper limit for the bid is calculated as <strong>the base pricing limit for the billing event (`billing_event`) </strong>* <strong>Bid Verification Ratio for the currency</strong>. For example, if you specify the billing event as CPC (base pricing limit=30), and the currency is JPY (Bid Verification Ratio=100), then the maximum bid price you can set will be 3000 JPY.
><span style="color:DodgerBlue">**Note**</span><br>Bid Verification Ratio is the same as Budget Verification Ratio. See the Budget Verification Ratio for different currencies in [Currency](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433). <br><strong>OR</strong> you can see the maximum bid price directly [here](https://ads.tiktok.com/marketing_api/docs?id=1750444779586561).
3. Budget limit. The bid price also needs to be lower than the budget you set for the ad group and the campaign. 


# Suggested bid 
Using machine-learning technology based on your ad settings and historical delivery data, suggested bid provides the most scientific bid suggestion for your ads. See [here](https://ads.tiktok.com/help/article?aid=9683) for detailed introductions. 

You can get suggested bid using [/tool/bid/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1737107845597186). Note that the suggested bid can only be used with **Cost Cap** bidding strategy (`bid_type` = `BID_TYPE_CUSTOM`).

