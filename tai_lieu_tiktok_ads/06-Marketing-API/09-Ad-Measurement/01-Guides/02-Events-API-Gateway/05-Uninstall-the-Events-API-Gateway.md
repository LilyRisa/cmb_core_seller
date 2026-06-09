---
title: "Uninstall the Events API Gateway"
doc_id: 1835087643351106
path: "Marketing API / Ad Measurement / Guides / Events API Gateway / Uninstall the Events API Gateway"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Uninstall the Events API Gateway

1. Disconnect Gateway from TikTok Events Manager.
2. Go to your DNS provider and undo the DNS configuration you did when onboarding.
3. Go to AWS CloudFormation stack. Find the deployment stack and click the **Delete** button. It will delete all the AWS resources created by the Cloudformation stack, including EC2 instances and security groups.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/26291ca21e26b9899a34a54c0edff64b" alt="Delete AWS Resources" width="60%" style="color:#808080" /><br>

4. Delete domain certificate & DNS record if the host domain has been set on the Gateway Hub.
