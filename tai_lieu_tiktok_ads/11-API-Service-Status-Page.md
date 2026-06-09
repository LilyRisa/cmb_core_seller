---
title: "API Service Status Page"
doc_id: 1847461383861378
path: "API Service Status Page"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# API Service Status Page

The [API Service Status Page](https://business-api.tiktok.com/portal/api-service-status) provides developers with real-time visibility into the health and performance of TikTok for Business API services. This tool allows you to monitor the status of our APIs, stay informed about ongoing incidents, and review historical incident details.

## Key features
### **Real-time status monitoring**
Get a comprehensive overview of the operational status of all our API products. Each API is categorized by its current service level:

- **No Known Issue**: Service is fully healthy with no known disruptions.

- **Low Disruption**: Minor issues like elevated latency or timeouts may occur, but the majority of requests succeed.

- **Major Disruption**: Critical service impact where most or all API calls are failing or latency exceeds SLOs (Service Level Objectives) globally.

### **Incident history and details**
Access a detailed log of all incidents that have occurred in the past 90 days. For each incident, you can view:

- Incident start and resolution times

- Affected API products

- A detailed description of the issue and its impact

## Stay updated on API status
To ensure you are always aware of API status changes, we offer multiple notification options:

- **Webhook subscriptions**: Receive real-time incident notifications by [subscribing to the `API_SERVICE_STATUS` webhook](https://business-api.tiktok.com/portal/docs?id=1810521739537409#item-link-Subscribe%20to%20API%20service%20status). You can filter notifications by specific API products to only get the updates that matter to you.

- **RSS feed**: Subscribe to our RSS feed by [accessing the RSS feed link](https://business-api.tiktok.com/marketing_api/api/status_page/incident/rss/) and incorporating it into your RSS reader. This integration allows the reader to automatically check for updates based on your defined polling frequency. We recommend setting your reader to auto-fetch every 30 minutes for the most timely updates. New incidents or updates will appear directly in your chosen RSS reader.

- **In-portal banners**: During an ongoing incident, a banner will be displayed on the API documentation pages with a summary and a link to the detailed status page.
