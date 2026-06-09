---
title: "Change Log"
doc_id: 1738811145996290
path: "API Reference / Change Log"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Change Log

The change logs show you the changes made to your ad account, campaigns, ad groups and ads. This is a great way for agencies and businesses to monitor changes made by team members. You can use the Change Log API to retrieve log data to track campaign changes and troubleshoot issues. You will be able to view the time when a change is made, the object that has been changed, the change details, and the operator who made specific changes to a campaign, ad group, or ad.

To get the change log file, you need to first create an asynchronous download task via [/changelog/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1738811219460097), then use [/changelog/task/check/](https://ads.tiktok.com/marketing_api/docs?id=1739916077352961) to check whether the task completes  (`status` = `SUCCESS`), and finally when the task completes, call [/changelog/task/download/](https://ads.tiktok.com/marketing_api/docs?id=1739924165710849) to download the change log file.

This group consists of the following endpoints:
- [Create a change log download task](https://ads.tiktok.com/marketing_api/docs?id=1738811219460097)
- [Check the status of a download task](https://ads.tiktok.com/marketing_api/docs?id=1739916077352961)
- [Get the downloaded file](https://ads.tiktok.com/marketing_api/docs?id=1739924165710849)


