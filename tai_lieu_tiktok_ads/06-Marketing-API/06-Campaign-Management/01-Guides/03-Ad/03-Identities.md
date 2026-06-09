---
title: "Identities"
doc_id: 1738958351620097
path: "Marketing API / Campaign Management / Guides / Ad / Identities"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Identities

With the Identity feature, you can create Spark Ads by pushing posts from your TikTok For Business account to the linked TikTok Business Account, or by pulling posts from the linked Business Account, from authorized posts or from authorized TikTok accounts.

There are five types of authorized identities:
* Custom User (`CUSTOMIZED_USER`): This type of identity represents virtual TikTok accounts that are only used for displaying in-feed ads. This type of identities can be created via the [/identity/create/](https://ads.tiktok.com/marketing_api/docs?rid=uraumvplog&id=1710483755010050) endpoint. If you are not using Spark Ads, we still highly recommend you to pass in `identity_id` and `identity_type` (`CUSTOMIZED_USER`) for better management of ads information.
* Authorized User (`AUTH_CODE`): This type of identity is created when you use the authorization code to access a TikTok account or a TikTok post. 
* TikTok User (`TT_USER`): This type of identity is created when you bind your TikTok For Business account with your TikTok Business Account, or when you bind your TikTok For Business account with your regular TikTok account and then upgrade the account to Business Account.
* TikTok Account User in Business Center (`BC_AUTH_TT`): This type of identity is created when you add a TikTok account to your Business Center and the TikTok account owner approves your request. After that, the members in your Business Center can access the profile info and live videos in the TikTok account.
* TikTok Account User for TikTok Shop (`TTS_TT`): This type of identity is created when you set an official TikTok account for the TikTok Shop.

Three types of identity can be used to create spark ads: Authorized User (`AUTH_CODE`), TikTok User (`TT_USER`), and TikTok Account User in Business Center (`BC_AUTH_TT`). See [Spark Ads-How to create Spark Ads](https://ads.tiktok.com/marketing_api/docs?id=1739470744631298) for detailed steps of Spark Ads creation.



## Related endpoints
* [Create an identity](https://ads.tiktok.com/marketing_api/docs?id=1740654203526146)
* [Delete an identity](https://ads.tiktok.com/marketing_api/docs?id=1759155939236865)
* [Get identity list](https://ads.tiktok.com/marketing_api/docs?id=1740218420781057)
* [Get identity info](https://ads.tiktok.com/marketing_api/docs?id=1740218453385217)
* [Get videos for identity](https://ads.tiktok.com/marketing_api/docs?id=1740218475032577)
* [Get music authorization info](https://ads.tiktok.com/marketing_api/docs?id=1740218495869954)
* [Get TikTok video info](https://ads.tiktok.com/marketing_api/docs?id=1740218522178562)
