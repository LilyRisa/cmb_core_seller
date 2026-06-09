---
title: "Authorization"
doc_id: 1781891256601602
path: "Get Started / Step-by-step workflow / Authorization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Authorization

After your developer application is approved, you need to get authorization from your advertiser, TikTok accounts, TikTok Creator Marketplace (TTCM) accounts, or TikTok One (TTO) Creator Marketplace accounts to manage the accounts. 

**The specific steps required for authorization will vary depending on the type of API you are using.**  Please refer to the following topics for detailed instructions on how to complete authorization：

- Marketing API authorization: Refer to <b>[Marketing API authorization](https://business-api.tiktok.com/portal/docs?id=1738373141733378)</b> to learn how to get authorization from advertisers to manage their accounts.
- Accounts API / Mentions API authorization: Refer to <b>[Accounts API authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009)</b> or <b>[Mentions API authorization](https://business-api.tiktok.com/portal/docs?id=1825103730490434)</b> to learn how to get authorization from TikTok account users to manage their accounts.
- TTO API authorization:
  - Refer to <b>[Obtain authorization from the TTO Creator Marketplace account](https://business-api.tiktok.com/portal/docs?id=1825117875361793#item-link-Obtain%20authorization%20from%20the%20TTO%20Creator%20Marketplace%20account)</b> to learn how to get authorization from TTO Creator Marketplace account users. This access token does not expire.
- Creator Marketplace API authorization:
   - Refer to <b>[Obtain authorization from the TTCM account](https://business-api.tiktok.com/portal/docs?id=1781189065082881-item-link-Obtain%20authorization%20from%20the%20TTCM%20account)</b> to learn how to get authorization from TTCM account users to manage their accounts. 
   - Refer to <b>[Get authorization from Creators](https://business-api.tiktok.com/portal/docs?id=1768506960432130)</b> to learn how to get authorization from Creators.
- Discovery API authorization: Refer to <b>[Discovery API authorization](https://business-api.tiktok.com/portal/docs?id=1825127469285442)</b> to learn how to get authorization from advertisers or TikTok account users to manage their accounts.
- Spark Ads Recommendation API authorization: Get authorization from TikTok account users or TTCM account users to manage their accounts.




# Comparing authorization for different APIs
The table below outlines the differences in the authorization processes for Marketing API, Accounts API, Mentions API, Creator Marketplace API, TTO API, Discovery API, and Spark Ads Recommendation API.

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
</tbody></table>
</div>

