---
title: "Authentication"
doc_id: 1781892384100354
path: "Get Started / Step-by-step workflow / Authentication"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Authentication

After you get `auth_code` from advertisers, TikTok account users, TikTok Creator Marketplace (TTCM) accounts, or TikTok One (TTO) Creator Marketplace accounts, you can use it to generate an **access token** for subsequent API requests.

**The specific steps required for authentication will vary depending on the type of API you are using.**  Please refer to the following topics for detailed instructions on how to complete authentication：

- Marketing API authentication: Refer to **[Marketing API authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162)** to learn how to generate a long-term access token. This access token does not expire.
- Accounts API / Mentions API authentication: Refer to **[Accounts API authentication](https://business-api.tiktok.com/portal/docs?id=1738084387220481)** or **[Mentions API authentication](https://business-api.tiktok.com/portal/docs?id=1825105003772930)** to learn how to generate an access token. The access token is valid for one day. Once an access token has expired, you need to renew the access token via [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Renew%20a%20TikTok%20account%20access%20token).
- TTO API authentication:
  - Refer to **[Obtain authentication from the TTO Creator Marketplace account](https://business-api.tiktok.com/portal/docs?id=1825117875361793#item-link-Obtain%20authentication%20from%20the%20TTO%20Creator%20Marketplace%20account)** to learn how to generate an access token for TTO Creator Marketplace accounts. This access token does not expire.
- Creator Marketplace API authentication: 
   - Refer to **[TTCM account authentication](https://business-api.tiktok.com/portal/docs?id=1781189065082881)**  to learn how to generate an access token for TTCM accounts. This access token does not expire.
   - Refer to **[Get authorization from Creators - Step 3](https://business-api.tiktok.com/portal/docs?id=1768506960432130)** to learn how to generate a Creator access token. It is valid for one day. Once an access token has expired, you need to renew the access token via [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?rid=7xusz43oh3h&id=1768493902119937#item-link-Renew%20an%20access%20token). 
- Discovery API authentication: Refer to **[Discovery API authentication](https://business-api.tiktok.com/portal/docs?id=1825127469285442)** to learn how to generate an advertiser access token or TikTok account access token. This advertiser access token does not expire while the TikTok account access token is valid for one day. Once the TikTok account access token has expired, you need to renew the access token via [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Revoke%20a%20TikTok%20account%20access%20token)..
- Spark Ads Recommendation API authentication: Generate a TTCM account access token or TikTok account access token. This TTCM account access token does not expire while the TikTok account access token is valid for one day. Once the TikTok account access token has expired, you need to renew the access token via [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Revoke%20a%20TikTok%20account%20access%20token).


# Comparing authentication for different APIs
The table below outlines the differences in the authentication processes for Marketing API, Accounts API, Mentions API, Creator Marketplace API, TTO API, Discovery API, and Spark Ads Recommendation API.

<div style="overflow-x:auto;">
<table style="width:1800px">
  <thead>
  <tr>
    <th style="width:10%"></th>
    <th style="width:15%">Marketing API</th>
    <th style="width:15%">Accounts API/Mentions API</th>
    <th style="width:15%">TikTok Creator Marketplace API</th>
    <th style="width:15%">TTO API</th>
    <th style="width:15%">Discovery API</th>
    <th style="width:15%">Spark Ads Recommendation API</th>
  </tr></thead>
<tbody>
  <tr>
    <td>Authentication endpoint</td>
    <td><a href="https://ads.tiktok.com/marketing_api/docs?id=1738373164380162" target="_blank">/oauth2/access_token/</a> (Advertiser access token)</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20an%20access%20token" target="_blank">/tt_user/oauth2/token/</a> (TikTok account access token)</td>
    <td><ul><li><a href="https://business-api.tiktok.com/portal/docs?id=1738373164380162" target="_blank">/oauth2/access_token/</a> (TTCM account access token)</li><li><a href="https://business-api.tiktok.com/portal/docs?rid=7xusz43oh3h&id=1768493902119937#item-link-Get%20an%20access%20token" target="_blank">/tt_user/oauth2/token/</a> (Creator access token)</li></ul></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1738373164380162" target="_blank">/oauth2/access_token/</a> (TTO access token)</td>
    <td><ul><li><a href="https://business-api.tiktok.com/portal/docs?id=1738373164380162" target="_blank">/oauth2/access_token/</a> (Advertiser access token)</li><li><a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token" target="_blank">/tt_user/oauth2/token/</a> (TikTok account access token)</li></ul></td>
    <td><ul><li><a href="https://business-api.tiktok.com/portal/docs?id=1738373164380162" target="_blank">/oauth2/access_token/</a> (TTCM access token)</li><li><a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token" target="_blank">/tt_user/oauth2/token/</a> (TikTok account access token)</li></ul></td>
  </tr>
  <tr>
    <td>Access token expiry</td>
    <td>The advertiser access token does not expire. If you want to invalidate the token, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739965949088770" target="_blank">/oauth2/revoke_token/</a>.</td>
    <td>The TikTok account access token is valid for one day. Once an access token has expired, you need to renew the access token via <a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Renew%20an%20access%20token" target="_blank">/tt_user/oauth2/refresh_token/</a>.</td>
    <td><ul><li>TTCM account access token: It does not expire. If you want to invalidate the token, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739965949088770" target="_blank">/oauth2/revoke_token/</a>.</li><li>Creator access token: It is valid for one day. Once an access token has expired, you need to renew the access token via <a href="https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Renew%20an%20access%20token" target="_blank">/tt_user/oauth2/refresh_token/</a>.</li></ul></td>
    <td>TTO account access token: It does not expire. If you want to invalidate the token, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739965949088770" target="_blank">/oauth2/revoke_token/</a>.</td>
    <td><ul><li>Advertiser access token (when the endpoint is related to hashtags): It does not expire. If you want to invalidate the token, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739965949088770" target="_blank">/oauth2/revoke_token/</a>.</li><li>TikTok account access token (when the endpoint is related to tracks): It is valid for one day. Once the access token has expired, you need to renew the access token via <a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Revoke%20a%20TikTok%20account%20access%20token" target="_blank">/tt_user/oauth2/refresh_token/</a>.</li></ul></td>
	<td><ul><li>TTCM access token (for <code>/spark_ad/recommend/</code>): It does not expire. If you want to invalidate the token, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739965949088770" target="_blank">/oauth2/revoke_token/</a>.</li><li>TikTok account access token (for <code>/business/video/recommend/</code>): It is valid for one day. Once the access token has expired, you need to renew the access token via <a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Revoke%20a%20TikTok%20account%20access%20token" target="_blank">/tt_user/oauth2/refresh_token/</a>.</li></ul></td>
  </tr>
</tbody></table>
</div>


