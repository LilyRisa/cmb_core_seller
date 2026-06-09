---
title: "FAQs"
doc_id: 1766042058952706
path: "Organic API / Accounts API / Guides / Get started / Authorization / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

This article collects some common questions about authorization before you use the Accounts API.

# Questions
<b>On the App Detail page, there are two different authorization URLs - Advertiser authorization URL and TikTok account holder authorization URL, and what are their differences? </b>


The Advertiser authorization URL is used for obtaining authorization from advertisers, TikTok Creator Marketplace (TTCM) accounts, or TikTok One (TTO) Creator Marketplace accounts, while the TikTok account holder authorization URL is used for obtaining authorization from Creators or TikTok account users. See the table below for details.

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
    <td>Description</td>
    <td>The authorization for Marketing API is performed via TikTok For Business advertiser.</td>
    <td>The authorization for Accounts API/Mentions API is performed via TikTok organic account.</td>
    <td>The authorization for TikTok Creator Marketplace (TTCM) API is performed via TTCM account and TikTok organic account.<br><br><p><span style="color:darkred"><b>Note</b></span>: TikTok Creator Marketplace API is in closed beta. You need to apply for access first.</td>
    <td>The authorization for TTO API is performed via TTO Creator Marketplace account.</td>
    <td>The authorization for Discovery API is performed via TikTok For Business advertiser or TikTok organic account.</td>
    <td>The authorization for Spark Ads Recommendation API is performed via TTCM account or TikTok organic account.</td>
  </tr>
  <tr>
    <td>Authorization URL</td>
    <td>Advertiser authorization URL on <a href="https://business-api.tiktok.com/portal/apps" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1738373141733378" target="_blank">Authorization - Step 1</a>.</td>
    <td>TikTok account holder authorization URL on <a href="https://ads.tiktok.com/i18n/login?redirect=https%3A%2F%2Fads.tiktok.com%2Fmarketing_api%2Fapps%2F" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1738083939371009" target="_blank">Accounts - Authorization</a>.</td>
    <td><ul><li>TTCM account authorization: Advertiser authorization URL on <a href="https://business-api.tiktok.com/portal/apps" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1781189065082881" target="_blank">Obtain authorization and authentication from your TTCM account</a>.</li><li>Creator authorization: TikTok account holder authorization URL on <a href="https://business-api.tiktok.com/portal/apps" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1768506960432130" target="_blank">Get authorization from Creators - Step 1</a>.</li></ul></td>
    <td>Advertiser authorization URL on <a href="https://ads.tiktok.com/i18n/login?redirect=https%3A%2F%2Fads.tiktok.com%2Fmarketing_api%2Fapps%2F" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1825117875361793" target="_blank">Obtain authorization and authentication from your TTO Creator Marketplace account</a>.</td>
    <td><ul><li>Advertiser authorization (when the endpoint is related to hashtags): Advertiser authorization URL on <a href="https://ads.tiktok.com/i18n/login?redirect=https%3A%2F%2Fads.tiktok.com%2Fmarketing_api%2Fapps%2F" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1738373141733378" target="_blank">Authorization - Step 1</a> for detailed steps.</li><li>TikTok account user authorization (when the endpoint is related to tracks): TikTok account holder authorization URL on My Apps. See <a href="https://business-api.tiktok.com/portal/docs?id=1738083939371009" target="_blank">Accounts API Authorization</a>.</li></ul></td>
    <td><ul><li>TTCM account authorization (for <a href="https://business-api.tiktok.com/portal/docs?id=1825128700959746" target="_blank">/spark_ad/recommend/</a>): Advertiser authorization URL on <a href="https://ads.tiktok.com/i18n/login?redirect=https%3A%2F%2Fads.tiktok.com%2Fmarketing_api%2Fapps%2F" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1781189065082881" target="_blank">Obtain authorization and authentication from your TTCM account</a>.</li><li>TikTok account user authorization (for <a href="https://business-api.tiktok.com/portal/docs?id=1825128684907522" target="_blank">/business/video/recommend/</a>): TikTok account holder authorization URL on My Apps. See <a href="https://business-api.tiktok.com/portal/docs?id=1738083939371009" target="_blank">Accounts API Authorization</a>.</li></ul></td>
  </tr>
  <tr>
    <td>Authorization code expiry</td>
    <td>Valid for one hour and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</td>
	<td>Valid for 10 minutes and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</td>
    <td><ul><li>TTCM account authorization code: Valid for one hour and can be used only once.</li><li>Creator authorization code: Valid for 10 minutes and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</li></ul></td>
    <td>Valid for one hour and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</td>
    <td><ul><li>Advertiser authorization code: Valid for one hour and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</li><li>TikTok account user authorization code: Valid for 10 minutes and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</li></ul></td>
    <td><ul><li>TTCM account authorization code: Valid for one hour and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</li><li>TikTok account user authorization code: Valid for 10 minutes and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</li></ul></td>
  </tr>
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



