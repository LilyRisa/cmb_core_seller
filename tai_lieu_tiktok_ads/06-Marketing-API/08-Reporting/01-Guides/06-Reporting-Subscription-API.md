---
title: "Reporting Subscription API"
doc_id: 1861445809243137
path: "Marketing API / Reporting / Guides / Reporting Subscription API"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Reporting Subscription API

## What is Reporting Subscription API?
The Reporting Subscription API allows developers to subscribe to auto-generated data reports, reducing the number of requests for large amounts of data. These reports can be sent every five minutes, 15 minutes, 30 minutes, or 60 minutes. For instance, if you configure the frequency to every five minutes, when any of the reporting metrics `spend`, `impressions`, `clicks`, and `video_play_actions` for any of the ad accounts that you are subscribed to has changed within the last five minutes, you will receive at most one webhook notification.

## Why Reporting Subscription API?
- Developers can automatically receive reports through either webhooks or emails.
	- Previously, if you wanted to generate asynchronous reports for the reporting metrics `spend`, `impressions`, `clicks`, and `video_play_actions`, you had to repeatedly call three Reporting API endpoints ([/report/task/create/](https://business-api.tiktok.com/portal/docs?id=1740302766489602), [/report/task/check/](https://business-api.tiktok.com/portal/docs?id=1740302781443073), and [/report/task/download/](https://business-api.tiktok.com/portal/docs?id=1740302808815618)) to download the reports. The Reporting Subscription API, on the other hand, enables you to automatically receive reports without invoking these three endpoints repeatedly.
- Developers can access reports at their preferred frequency.
	- The Reporting Subscription API allows developers to customize the frequency at which they wish to receive their reports.
## Steps
To learn about how to use Reporting Subscription API, see [Subscribe to reporting metric data changes](https://business-api.tiktok.com/portal/docs?id=1810521739537409#item-link-Subscribe%20to%20reporting%20metric%20data%20changes).


