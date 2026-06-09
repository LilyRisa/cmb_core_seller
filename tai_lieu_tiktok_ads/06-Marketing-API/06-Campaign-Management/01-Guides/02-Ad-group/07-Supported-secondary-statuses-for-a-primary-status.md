---
title: "Supported secondary statuses for a primary status"
doc_id: 1757239620352002
path: "Marketing API / Campaign Management / Guides / Ad group / Supported secondary statuses for a primary status"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported secondary statuses for a primary status

See the following tables to learn about the supported secondary statuses for a certain primary status at the campaign, ad group, and ad levels.
 
## For Promote campaigns
### Supported campaign secondary statuses
Refer to the following table to find out the secondary statuses that are supported for a certain primary status in Promote campaigns (campaigns created through the TikTok mobile App).

To learn more about Promote campaigns, see [Promote campaigns](https://business-api.tiktok.com/portal/docs?id=1785880454546433).
``` xtable
| <code>primary_status</code> value{25%} | Corresponding status on TikTok Ads Manager{18%} | Supported values of <code>secondary_status</code> <br>(campaign level) {32%}| Corresponding status on TikTok Ads Manager{25%} |
|---|---|---|
| `STATUS_ALL` | / | All campaign secondary statuses | / |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_ADVERTISER_AUDIT_DENY` | Account not approved |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_ADVERTISER_AUDIT` | Account in review |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_CONTRACT_PENDING` | Contract has not taken effect |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_ACCOUNT_PUNISH` | Account penalized |
| `STATUS_DELIVERY_OK` | Active | `CAMPAIGN_STATUS_ENABLE` | / |
```
### Supported ad group secondary statuses
Refer to the following table to learn about the secondary statuses that are supported for a certain primary status in Promote ad groups.
``` xtable
| <code>primary_status</code> value {25%}| Corresponding status on TikTok Ads Manager{18%} | Supported values of <code>secondary_status</code><br> (ad group level) {32%}| Corresponding status on TikTok Ads Manager{25%} |
|---|---|---|---|
| `STATUS_ALL` | / | All ad group secondary statuses | / |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_ADVERTISER_AUDIT_DENY` | Account not approved |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_ADVERTISER_AUDIT` | Account in review |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_CONTRACT_PENDING` | Contract has not taken effect |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_ACCOUNT_PUNISH` | Account penalized |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_AUDIT` | In review |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_LIVE_NOT_START` | LIVE not started |
| `STATUS_DELIVERY_OK` | Active | `ADGROUP_STATUS_PARTIAL_AUDIT_DELIVERY_OK` | / |
| `STATUS_DELIVERY_OK` | Active | `ADGROUP_STATUS_DELIVERY_OK` | / |
| `STATUS_TIME_DONE` | Completed | `ADGROUP_STATUS_TIME_DONE`<br><br><p><span style="color:darkred"><b>Note</b></span>: This status indicates the delivery of the ad group ended at the scheduled time.  | / |
| `STATUS_RF_CLOSED` | Closed | `ADGROUP_STATUS_PROMOTE_AD_NOT_APPROVED` | Creative not approved |
| `STATUS_RF_CLOSED` | Closed | `ADGROUP_STATUS_PROMOTE_WITHDRAW_ORDER` | Order is withdrawn |
```
### Supported ad secondary statuses
Refer to the following table to learn about the secondary statuses that are supported for a certain primary status in Promote ads.
``` xtable
| <code>primary_status</code> value{25%} | Corresponding status on TikTok Ads Manager {18%}| Supported values of <code>secondary_status</code> <br>(ad level) {32%} | Corresponding status on TikTok Ads Manager{25%} |
|---|---|---|
| `STATUS_ALL` | / | All ad secondary statuses | / |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ADVERTISER_AUDIT_DENY` | Account not approved |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ADVERTISER_AUDIT` | Account in review |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_CONTRACT_PENDING` | Contract has not taken effect |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_ACCOUNT_PUNISH` | Account penalized |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_AUDIT` | In review |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_NOT_START` | Outside of schedule |
| `STATUS_DELIVERY_OK` | Active | `AD_STATUS_PARTIAL_AUDIT_DELIVERY_OK` | / |
| `STATUS_DELIVERY_OK` | Active | `AD_STATUS_DELIVERY_OK` | / |
| `STATUS_TIME_DONE` | Completed | `AD_STATUS_DONE` | Completed |
| `STATUS_RF_CLOSED` | Closed | `AD_STATUS_PROMOTE_AD_OFFLINE_AUDIT` | Creative not approved |
| `STATUS_RF_CLOSED` | Closed | `AD_STATUS_PROMOTE_ADGROUP_CLOSED` | Ad group is closed |
```

## For GMV Max Campaigns
### Supported campaign secondary statuses
Refer to the table below to find out the secondary statuses that are supported for a certain primary status in [GMV Max Campaigns](https://business-api.tiktok.com/portal/docs?id=1822009058467842). Note that all campaign secondary statuses cannot be used as filters in [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986).

``` xtable
| <code>primary_status</code> value{25%} | Corresponding status on TikTok Ads Manager{18%} | Supported values of <code>secondary_status</code> <br>(campaign level) {32%}| Corresponding status on TikTok Ads Manager{25%} |
|---|---|---|
| `STATUS_ALL` | / | All campaign secondary statuses | / |
| `STATUS_NOT_DELETE` | / | All campaign secondary statuses excluding `CAMPAIGN_STATUS_DELETE` | / |
| `STATUS_DELETE` | Deleted | `CAMPAIGN_STATUS_DELETE` | Campaign deleted |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_TTS_TT_ASSET_UNAVAILABLE` | Asset not available |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_TTS_TT_IDENTITY_UNAVAILABLE` | TikTok account not available |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_TIKTOK_SHOP_UNAVAILABLE` | TikTok shop not available |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_REVIEW_DISAPPROVED` | Disapproved |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_AD_UNAVAILABLE` | No available ad |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_PRODUCT_UNAVAILABLE` | Product not available |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_ADVERTISER_AUDIT_DENY` | Account not approved |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_ADVERTISER_AUDIT` | Account in review |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_CONTRACT_PENDING` | Contract has not taken effect |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_ACCOUNT_PUNISH` | Account penalized |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_BUDGET_EXCEED` | Campaign out of budget |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_IDENTITY_USED_BY_GMV_MAX_AD` | Identity unavailable as it's used by a GMV max ad |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_TTS_ACCOUNT_BALANCE_INSUFFICIENT`|Insufficient balance  <br> (of your advertiser account)|
| `STATUS_DISABLE` | Inactive | `CAMPAIGN_STATUS_DISABLE` | Campaign inactive |
| `STATUS_DISABLE` | Inactive | `CAMPAIGN_STATUS_LIVE_GMV_MAX_AUTHORIZATION_CANCEL` | This campaign is paused because the selected official TikTok account has granted GMV Max authorization to another ad account. |
| `STATUS_DISABLE` | Inactive | `CAMPAIGN_STATUS_PRODUCT_GMV_MAX_AUTHORIZATION_CANCEL` | This campaign is paused because the TikTok Shop has granted GMV Max authorization to another ad account.  |
| `STATUS_DISABLE` | Inactive | `CAMPAIGN_STATUS_IDENTITY_USED_BY_LIVE_GMV_MAX` | This campaign is paused due to an active LIVE GMV max campaign which uses this TikTok account.|
| `STATUS_DISABLE` | Inactive | `CAMPAIGN_STATUS_PRODUCT_USED_BY_PRODUCT_GMV_MAX` |This campaign is paused due to an active Product GMV max campaign created for this shop. |
| `STATUS_DISABLE` | Inactive | `CAMPAIGN_STATUS_AWAITING_RELEASE` | Awaiting release |
| `STATUS_DELIVERY_OK` | Active | `CAMPAIGN_STATUS_ENABLE` | / |
``` 


## For other campaigns
### Supported campaign secondary statuses
Refer to the table below to find out the secondary statuses that are supported for a certain primary status in other campaigns (campaigns created through the API or in TikTok Ads Manager). Note that all campaign secondary statuses excluding `CAMPAIGN_STATUS_IDENTITY_USED_BY_GMV_MAX_AD` can be used as filters in [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986).
``` xtable
| <code>primary_status</code> value{25%} | Corresponding status on TikTok Ads Manager{18%} | Supported values of <code>secondary_status</code> <br>(campaign level) {32%}| Corresponding status on TikTok Ads Manager{25%} |
|---|---|---|
| `STATUS_ALL` | / | All campaign secondary statuses | / |
| `STATUS_NOT_DELETE` | / | All campaign secondary statuses excluding `CAMPAIGN_STATUS_DELETE` | / |
| `STATUS_DELETE` | Deleted | `CAMPAIGN_STATUS_DELETE` | Campaign deleted |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_ADVERTISER_AUDIT_DENY` | Account not approved |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_ADVERTISER_AUDIT` | Account in review |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_CONTRACT_PENDING` | Contract has not taken effect |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_ACCOUNT_PUNISH` | Account penalized |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_BUDGET_EXCEED` | Campaign out of budget |
| `STATUS_NOT_DELIVERY` | Not delivering | `CAMPAIGN_STATUS_IDENTITY_USED_BY_GMV_MAX_AD` | Identity unavailable as it's used by a GMV max ad |
| `STATUS_DISABLE` | Inactive | `CAMPAIGN_STATUS_DISABLE` | Campaign inactive |
| `STATUS_DISABLE` | Inactive | `CAMPAIGN_STATUS_AWAITING_RELEASE` | Awaiting release |
| `STATUS_DELIVERY_OK` | Active | `CAMPAIGN_STATUS_ENABLE` | / |
```
### Supported ad group secondary statuses
Refer to the table below to find out the secondary statuses that are supported for a certain primary status in other ad groups. Note that all the ad group secondary statuses excluding `ADGROUP_STATUS_REVIEW_PARTIALLY_APPROVED`, `ADGROUP_STATUS_TRANSCODING_FAIL`, `ADGROUP_STATUS_ASSET_AUTHORIZATION_LOST`, `ADGROUP_STATUS_ADGROUP_QUOTA_LIMIT`, and `ADGROUP_STATUS_ADGROUP_PRE_ONLINE` can be used as filters in [/adgroup/get/](https://ads.tiktok.com/marketing_api/docs?id=1739314558673922).
 
``` xtable
| Ad group type {13%}| <code>primary_status</code> value {17%}| Corresponding status on TikTok Ads Manager{16%} | Supported values of <code>secondary_status</code> <br>(ad group level) {34%}| Corresponding status on TikTok Ads Manager{20%} |
|---|---|---|
| / | `STATUS_ALL` | / | All ad group secondary statuses | / |
| / | `STATUS_NOT_DELETE` | / | All ad group secondary statuses excluding `CAMPAIGN_STATUS_DELETE` and `ADGROUP_STATUS_DELETE` | / |
| Auction | `STATUS_DELETE` | Deleted | `ADGROUP_STATUS_CAMPAIGN_DELETE` | Campaign deleted |
| Auction | `STATUS_DELETE` | Deleted | `ADGROUP_STATUS_DELETE` | Ad group deleted |
| Auction | `STATUS_DISABLE` | Inactive | `ADGROUP_STATUS_CAMPAIGN_DISABLE` | Campaign inactive |
| Auction | `STATUS_DISABLE` | Inactive | `ADGROUP_STATUS_DISABLE` | Ad group inactive |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_ADVERTISER_AUDIT_DENY` | Account not approved |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_ADVERTISER_AUDIT` | Account in review |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_CONTRACT_PENDING` | Contract has not taken effect |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_ACCOUNT_PUNISH` | Account penalized |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_BALANCE_EXCEED` | Payment unsuccessful or insufficient balance |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_CREATE` | No creatives |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_TRANSCODING_FAIL` | Unable to convert video, upload again. |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_AUDIT` | In review |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_REAUDIT` | Edited for review |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_AUDIT_DENY` | Not approved |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_PARTIAL_AUDIT_NO_DELIVERY` | In review |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_LIVE_NOT_START` | LIVE not started |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_INDUSTRY_QUALIFICATION_MISSING` | Qualification needed |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_INDUSTRY_QUALIFICATION_EXPIRED` | Expired |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_INDUSTRY_QUALIFICATION_DENY` | Disapproved |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_CAMPAIGN_EXCEED` | Campaign out of budget |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_BUDGET_EXCEED` | Ad group out of budget |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_FROZEN` | / |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_ADGROUP_PRE_ONLINE` | / |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_NOT_START` | Outside of schedule |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_ASSET_AUTHORIZATION_LOST` | Asset unavailable or authorization revoked |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_PIXEL_UNBIND` | Pixel Unauthorized |
| Auction| `STATUS_NOT_DELIVERY` |Not delivering | `ADGROUP_STATUS_ADGROUP_QUOTA_LIMIT` |Out of quota. Pause an active ad group to continue.|
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_IDENTITY_USED_BY_GMV_MAX_AD` | Identity unavailable as it's used by a GMV max ad |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_SEARCH_KEYWORDS_IN_REVIEW` | Keywords in review |
| Auction | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_SEARCH_KEYWORDS_NOT_AVAILABLE` | No keywords available |
| Auction | `STATUS_DELIVERY_OK` | Active | `ADGROUP_STATUS_SEARCH_KEYWORDS_PARTIAL_APPROVED` | Keywords partially approved |
| Auction | `STATUS_DELIVERY_OK` | Active | `ADGROUP_STATUS_PARTIAL_AUDIT_DELIVERY_OK` | / |
| Auction | `STATUS_DELIVERY_OK` | Active | `ADGROUP_STATUS_SHADOW_ADGROUP_REAUDIT` | Edited for review |
| Auction <br>or R & F  | `STATUS_DELIVERY_OK` | Active | `ADGROUP_STATUS_DELIVERY_OK` | / |
| Auction <br>or R & F  | `STATUS_DELIVERY_OK` | Active | `ADGROUP_STATUS_REVIEW_PARTIALLY_APPROVED` | Some ads were not approved |
| Auction <br>or R & F | `STATUS_TIME_DONE` | Completed | `ADGROUP_STATUS_TIME_DONE`<br><br><p><span style="color:darkred"><b>Note</b></span>: This status indicates the delivery of the ad group ended at the scheduled time. | / |
| R & F | `STATUS_RF_CLOSED` | Closed | `ADGROUP_STATUS_RF_DEDUCTION_FAILED` | Insufficient balance |
| R & F | `STATUS_RF_CLOSED` | Closed | `ADGROUP_STATUS_RF_NO_VALID_CREATIVE` | No valid ads |
| R & F | `STATUS_RF_CLOSED` | Closed | `ADGROUP_STATUS_RF_CLOSED_OTHERS` | / |
| R & F | `STATUS_RF_CLOSED` | Closed | `ADGROUP_STATUS_RF_AD_AUDIT_DENY` | Ads disapproved |
| R & F | `STATUS_RF_CLOSED` | Closed | `ADVERTISER_ACCOUNT_INVALID` | Invalid account status |
| R & F | `STATUS_RF_CLOSED` | Closed | `ADGROUP_STATUS_RF_ADGROUP_INVALID` | / |
| R & F | `STATUS_RF_CLOSED` | Closed | `ADGROUP_STATUS_RF_WITHDRAW_ORDER` | Reservation cancelled |
| R & F | `STATUS_TIME_DONE` | Completed | `ADGROUP_STATUS_RF_TERMINATE` | Ad group terminated |
| R & F | `STATUS_TIME_DONE` | Completed | `ADGROUP_STATUS_RF_TIME_DONE`<br><br><p><span style="color:darkred"><b>Note</b></span>: This status indicates the delivery of the ad group ended before the scheduled time due to insufficient budget or ad account balance. | Completed |
| R & F | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_RF_NO_DELIVERY_CREATIVE` | No valid creatives |
| R & F | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_RF_SHORT_BALANCE` | Account balance less than deduction amount |
| R & F | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_RF_BOOKING` | Reserved |
| R & F | `STATUS_NOT_DELIVERY` | Not delivering | `ADGROUP_STATUS_RF_SCHEDULE` | Scheduled |
```
 
### Supported ad secondary statuses
Refer to the table below to find out the secondary statuses that are supported for a certain primary status in other ads. Note that all the ad secondary statuses excluding `AD_STATUS_REVIEW_PARTIALLY_APPROVED`, `AD_STATUS_TRANSCODING_FAIL`, `AD_STATUS_ADGROUP_ASSET_AUTHORIZATION_LOST`,`AD_STATUS_ASSET_AUTHORIZATION_LOST`,  `AD_STATUS_AD_QUOTA_LIMIT`, and `AD_STATUS_AD_PRE_ONLINE` can be used as filters in [/ad/get/](https://ads.tiktok.com/marketing_api/docs?id=1735735588640770).
 
``` xtable
| <code>primary_status</code> value{20%} | Corresponding status on TikTok Ads Manager {20%}| Supported values of <code>secondary_status</code><br> (ad level) {35%} | Corresponding status on TikTok Ads Manager{25%} |
|---|---|---|
| `STATUS_ALL` | / | All ad secondary statuses | / |
| `STATUS_NOT_DELETE` | / | All ad secondary statuses excluding `CAMPAIGN_STATUS_DELETE`, `ADGROUP_STATUS_DELETE`, and `AD_STATUS_DELETE` | / |
| `STATUS_DELETE` | Deleted | `AD_STATUS_CAMPAIGN_DELETE` | Campaign deleted |
| `STATUS_DELETE` | Deleted | `AD_STATUS_ADGROUP_DELETE` | Ad group deleted |
| `STATUS_DELETE` | Deleted | `AD_STATUS_DELETE` | Ad deleted |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ADVERTISER_AUDIT_DENY` | Account not approved |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ADVERTISER_AUDIT` | Account in review |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_CONTRACT_PENDING` | Contract has not taken effect |
| `STATUS_NOT_DELIVERY` | Not delivering | `ADVERTISER_ACCOUNT_PUNISH` | Account penalized |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_LIVE_OFFLINE` | / |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_BALANCE_EXCEED` | Payment unsuccessful or insufficient balance |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ADGROUP_PARTIAL_AUDIT_NO_DELIVERY` | In review |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_PARTIAL_AUDIT_NO_DELIVERY` | In review |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ADGROUP_AUDIT_DENY` | Not approved |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ADGROUP_INDUSTRY_QUALIFICATION_MISSING` | Qualification needed |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ADGROUP_INDUSTRY_QUALIFICATION_EXPIRED` | Disapproved |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ADGROUP_INDUSTRY_QUALIFICATION_DENY` | Expired |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_MUSIC_AUTHORIZATION_MISSING` | Music copyright missing |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_TRANSCODING_FAIL` | Replace videos with errors in the ad group |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_AUDIT` | In review |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_REAUDIT` | Edited for review |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_AUDIT_DENY` | Not approved |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_PROCESS_AUDIO` | Processing audio, re-upload if it gets stuck. |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_CAMPAIGN_EXCEED` | Campaign out of budget |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_BUDGET_EXCEED` | Ad group out of budget |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_AD_PRE_ONLINE` | /   |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_NOT_START` | Outside of schedule |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ADGROUP_ASSET_AUTHORIZATION_LOST` | Asset unavailable or authorization revoked |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ASSET_AUTHORIZATION_LOST` | Asset unavailable or authorization revoked |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STAUS_PIXEL_UNBIND` | Pixel Unauthorized |
| `STATUS_NOT_DELIVERY` |Not delivering | `AD_STATUS_AD_QUOTA_LIMIT` |Ad group paused because your account is out of quota. Pause an active ad group to continue.|
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_PRODUCT_UNAVAILABLE` | Product is unavailable. Edit the ad to learn why.   |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_ANCHOR_UNAVAILABLE` | Product link is unavailable. Edit the ad to learn why.   |
| `STATUS_NOT_DELIVERY` | Not delivering | `AD_STATUS_NO_AUTHORIZATION_OF_SHOWCASE` | Identity is unavailable. Edit the ad to check details.   |
| `STATUS_DELIVERY_OK` | Active | `AD_STATUS_PARTIAL_AUDIT_DELIVERY_OK` | / |
| `STATUS_DELIVERY_OK` | Active | `AD_STATUS_REVIEW_PARTIALLY_APPROVED` | / |
| `STATUS_DELIVERY_OK` | Active | `AD_STATUS_DELIVERY_OK` | / |
| `STATUS_DISABLE` | Inactive | `AD_STATUS_CAMPAIGN_DISABLE` | Campaign inactive |
| `STATUS_DISABLE` | Inactive | `AD_STATUS_ADGROUP_DISABLE` | Ad group inactive |
| `STATUS_DISABLE` | Inactive | `AD_STATUS_DISABLE` | / |
| `STATUS_FROZEN` | / | `ADGROUP_STATUS_FROZEN` | / |
| `STATUS_TIME_DONE` | Completed | `AD_STATUS_DONE` | Completed |
| `STATUS_RF_CLOSED` | Closed | `AD_STATUS_RF_ADGROUP_CLOSED` | Ad group is closed |
```

