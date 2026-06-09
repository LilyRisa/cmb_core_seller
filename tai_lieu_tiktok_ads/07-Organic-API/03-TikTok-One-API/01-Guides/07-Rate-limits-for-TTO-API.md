---
title: "Rate limits for TTO API"
doc_id: 1825117901366274
path: "Organic API / TikTok One API / Guides / Rate limits for TTO API"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Rate limits for TTO API

## Overview
To support the continued growth of TikTok's creator ecosystem and ensure optimal performance, we are introducing important changes to TikTok One API (TTO API) rate limits. These updates are designed to provide fair resource allocation and a sustainable, high-quality experience for all creators, brands, and partners. This approach underpins our commitment to long-term innovation and developer success.

## TTO API unique rate limit
Effective Date: September 26, 2025, 00:00 (GMT+0)

**Unique rate limits apply to the following TTO API endpoints:**

<div style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>Endpoint</th>
                <th colspan="3">Basic</th>
                <th colspan="3">Advanced</th>
                <th colspan="3">Premium</th>
            </tr>
        </thead>
        <tbody>
		  <tr>
                <td>Limit type</td>
                <td>QPS</td>
                <td>QPM</td>
                <td>QPD</td>
                <td>QPS</td>
                <td>QPM</td>
                <td>QPD</td>
                <td>QPS</td>
                <td>QPM</td>
                <td>QPD</td>
            </tr>
		    <tr>
                <td><a href="https://business-api.tiktok.com/portal/docs?id=1834913529338882" target="_blank">/tto/creator/authorized/</a></td>
                <td>10</td>
                <td>20</td>
                <td>50</td>
                <td>20</td>
                <td>1,200</td>
                <td>10,000</td>
                <td>30</td>
                <td>1,800</td>
                <td>300,000</td>
            </tr>
		    <tr>
                <td><a href="https://business-api.tiktok.com/portal/docs?id=1834913554657282" target="_blank">/tto/creator/authorized/video/list/</a></td>
                <td>10</td>
                <td>20</td>
                <td>50</td>
                <td>20</td>
                <td>1,200</td>
                <td>10,000</td>
                <td>30</td>
                <td>1,800</td>
                <td>300,000</td>
            </tr>
            <tr>
                <td><a href="https://business-api.tiktok.com/portal/docs?id=1828185358712897" target="_blank">/tto/tcm/creator/public/</a></td>
                <td>10</td>
                <td>20</td>
                <td>50</td>
                <td>20</td>
                <td>1,200</td>
                <td>10,000</td>
                <td>30</td>
                <td>1,800</td>
                <td>150,000</td>
            </tr>
            <tr>
                <td><a href="https://business-api.tiktok.com/portal/docs?id=1828185411810305" target="_blank">/tto/tcm/creator/public/video/list/</a></td>
                <td>10</td>
                <td>20</td>
                <td>50</td>
                <td>20</td>
                <td>1,200</td>
                <td>10,000</td>
                <td>30</td>
                <td>1,800</td>
                <td>500,000</td>
            </tr>
            <tr>
                <td><a href="https://business-api.tiktok.com/portal/docs?id=1816378813274113" target="_blank">/tto/tcm/rank/</a></td>
                <td>10</td>
                <td>20</td>
                <td>50</td>
                <td>20</td>
                <td>1,200</td>
                <td>10,000</td>
                <td>30</td>
                <td>1,800</td>
                <td>150,000</td>
            </tr>
            <tr>
                <td><a href="https://business-api.tiktok.com/portal/docs?id=1825017307843585" target="_blank">/tto/tcm/creator/discover/</a></td>
                <td>10</td>
                <td>20</td>
                <td>50</td>
                <td>20</td>
                <td>1,200</td>
                <td>10,000</td>
                <td>30</td>
                <td>1,800</td>
                <td>100,000</td>
            </tr>
        </tbody>
    </table>
</div>

> <span style="color:DodgerBlue">**Note**</span><br>
> Global rate limits apply to other TTO API endpoints not listed above. To learn about the global rate limits for a developer app, refer to [Rate limits - Global rate limits](https://business-api.tiktok.com/portal/docs?id=1740029171730433#item-link-Global%20rate%20limits).

**Rate limit tiers:**
- **Basic Level**: All developers will default to Basic rate limits.
- **Advanced Level**: Developers may qualify for Advanced rate limits if, within a calendar quarter, they manage at least 300 branded content posts linked to any TikTok One Campaign created through the [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458) endpoint.
- **Premium Level**: Developers may qualify for Premium rate limits if, within a calendar quarter, they fulfill the required number of branded content posts linked to any TikTok One Campaign created through the [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458) endpoint. For non-SaaS developers, the threshold is 1,000 branded content posts, whereas for SaaS developers, the precise threshold will be specified by the TikTok Creative Operations team.
> <span style="color:DodgerBlue">**Note**</span><br>
>  A rate limit increase is subject to evaluation and approval by the TikTok Creative Operations team. Developers who do not adhere to the requisite standards or misuse the API may be demoted to the Advanced or Basic level. Upon approval of a rate limit increase, the TikTok Creative Operations team will furnish additional details.

## Apply for TTO API Rate Limit Increase
- **Managed Developers**: If you are a Creative Partner or managed developer, please contact your TikTok representative to help you start a conversation with the TikTok Creative Operations team.
- **Self-Serve Developers**: If you are a self-serve developer and meet the Advanced or Premium level requirements, you can submit a [TTO API rate limit increase application](https://bytedance.sg.larkoffice.com/share/base/form/shrlgphNGDoW1eLAEE8EP0pNwWh). The TikTok Creative Operations team will review your application and contact you with the next steps if you meet the criteria. If your application is not approved, the Basic level of TTO API rate limits will apply to your developer app.




