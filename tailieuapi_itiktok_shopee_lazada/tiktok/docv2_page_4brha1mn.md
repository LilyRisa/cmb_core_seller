# New Capability for Affiliate Data Compass Offline Data Export

> Source: https://partner.tiktokshop.com/docv2/page/4brha1mn
> Section: Changelog
> Scraped: 2026-05-21T00:33:06.038Z

---

# Overview

TikTok Shop is launching a new set of Affiliate Seller Compass Offline Data Export APIs under the `affiliate_seller` domain. These APIs enable developers (ISVs and in-house seller teams) to programmatically create, query, and download large-scale, asynchronous data reports from the Affiliate Seller Compass — including video performance, creator analytics, and other key business metrics.  
Previously, partners and sellers lacked an efficient, automated way to export large volumes of affiliate performance data. Manual downloads were time-consuming, error-prone, and could not be integrated into automated data analysis pipelines. With this release, three new API endpoints are introduced to support a complete offline export workflow: creating an export task, querying task status, and downloading the completed file.  
**Key benefits:**

-   **For ISVs/Developers:** Automate data collection, reduce manual effort, and build more sophisticated analytics tools on top of TikTok Shop affiliate data.
-   **For Sellers:** Access more powerful third-party applications that provide deeper insights into affiliate marketing performance.
-   **For TikTok Shop Ecosystem:** Strengthen the Open API platform, increase partner stickiness, and foster a healthier developer ecosystem.

# Impact

|  |  |
| --- | --- |
| 
Impacted version(s)

 | 

\* 202603 (and later)

 |
| 

Impacted market(s)

 | 

\* United States (US) - Local and cross-border  
\* Southeast Asia (SEA)- Local and cross-border

 |

## New APIs

| **API Name** | **Type** | **Version** |
| --- | --- | --- |
| 
[Create Compass Offline Export Task](https://partner.tiktokshop.com/docv2/page/create-compass-offline-export-task-202603)

 | 

API

 | 

202603

 |
| 

[Get Compass Task List](https://partner.tiktokshop.com/docv2/page/get-compass-task-list-202603)

 | 

API

 | 

202603

 |
| 

[Download Compass Task File](https://partner.tiktokshop.com/docv2/page/download-compass-task-file-202603)

 | 

API

 | 

202603

 |

### Typical Integration Flow

1.  **Create Task** — Call the Create endpoint to request a data export (e.g., last month's video performance data).
    
2.  **Poll Status** — Periodically call the Get Task List endpoint until the task status changes to `SUCCEEDED` or `FAILED`.
    
3.  **Download File** — Once succeeded, call the Download endpoint with the `task_id` to retrieve the XLSX file.
    
4.  **Process Data** — Parse the downloaded file and ingest data into your own systems for analysis.
