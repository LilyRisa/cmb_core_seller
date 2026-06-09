---
title: "Supported dimensions for Upgraded Smart+ Creative Overview Reports"
doc_id: 1843337892165889
path: "API Reference / Upgraded Smart+ / Reporting / Run an Upgraded Smart+ Creative Overview Report / Supported dimensions for Upgraded Smart+ Creative Overview Reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported dimensions for Upgraded Smart+ Creative Overview Reports

Dimension is an attribute to group your data by.

The following table outlines the available dimensions and supported dimension groupings in Upgraded Smart+ Creative Overview Reports.

```xtable
| Dimension name {23%} | Dimension type {14%} | Description {24%} | Supported dimension groupings {41%} |
| --- | --- | --- | --- |
| `advertiser_id` | Advertising dimension | Group by Advertiser ID. | <ul><li>`advertiser_id` x `main_material_id`</li><li>`advertiser_id` x `ad_text_entity_id`</li><li>`advertiser_id` x `call_to_action_entity_id`</li><li>`advertiser_id` x `interactive_add_on_entity_id`</li></ul> |
| `campaign_id` | Advertising dimension | Group by Campaign ID. | <ul><li>`campaign_id` x `main_material_id`</li><li>`campaign_id` x `ad_text_entity_id`</li><li>`campaign_id` x `call_to_action_entity_id`</li><li>`campaign_id` x `interactive_add_on_entity_id`</li></ul> |
| `adgroup_id` | Advertising dimension | Group by Ad group ID. | <ul><li>`adgroup_id` x `main_material_id`</li><li>`adgroup_id` x `ad_text_entity_id`</li><li>`adgroup_id` x `call_to_action_entity_id`</li><li>`adgroup_id` x `interactive_add_on_entity_id`</li></ul> |
| `smart_plus_ad_id` | Advertising dimension | Group by Ad ID. | <ul><li>`smart_plus_ad_id` x `main_material_id`</li><li>`smart_plus_ad_id` x `ad_text_entity_id`</li><li>`smart_plus_ad_id` x `call_to_action_entity_id`</li><li>`smart_plus_ad_id` x `interactive_add_on_entity_id`</li></ul> |
| `main_material_id` | Creative dimension | Group by main creative ID. | <ul><li>`advertiser_id` x `main_material_id`</li><li>`campaign_id` x `main_material_id`</li><li>`adgroup_id` x `main_material_id`</li><li>`smart_plus_ad_id` x `main_material_id`</li></ul> |
| `ad_text_entity_id` | Setting dimension | Group by ad text entity ID.<br><br>The ID is a system-generated unique identifier for an ad text. | <ul><li>`advertiser_id` x `ad_text_entity_id`</li><li>`campaign_id` x `ad_text_entity_id`</li><li>`adgroup_id` x `ad_text_entity_id`</li><li>`smart_plus_ad_id` x `ad_text_entity_id`</li></ul> |
| `call_to_action_entity_id` | Setting dimension | Group by call-to-action entity ID.<br><br>The ID is a system-generated unique identifier for a call-to-action. | <ul><li>`advertiser_id` x `call_to_action_entity_id`</li><li>`campaign_id` x `call_to_action_entity_id`</li><li>`adgroup_id` x `call_to_action_entity_id`</li><li>`smart_plus_ad_id` x `call_to_action_entity_id`</li></ul> |
| `interactive_add_on_entity_id` | Setting dimension | Group by interactive add-on entity ID.<br><br>The ID is a system-generated unique identifier for an interactive add-on. | <ul><li>`advertiser_id` x `interactive_add_on_entity_id`</li><li>`campaign_id` x `interactive_add_on_entity_id`</li><li>`adgroup_id` x `interactive_add_on_entity_id`</li><li>`smart_plus_ad_id` x `interactive_add_on_entity_id`</li></ul> |
```
