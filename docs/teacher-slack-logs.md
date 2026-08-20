# Teacher Slack Operation Logs

Each teacher may connect one personal Slack Incoming Webhook from the **Settings** page in the teacher portal. The LMS stores only the webhook configuration, encrypted at rest; it does **not** persist activity-log payloads, delivery history, request bodies, student names, credentials, or Slack responses. When enabled, successful mutating API operations and failed API responses are sent directly to that teacher’s configured Slack channel.

The destination accepts only HTTPS Slack Incoming Webhook URLs hosted on `hooks.slack.com` or `hooks.slack-gov.com` with a `/services/` path. The saved URL is never returned by the API or displayed after saving. Saving, replacing, and removing a destination run within explicit database transactions; storage failures roll back and return a safe Arabic error response.

## Teacher setup

Create or open a Slack app, enable **Incoming Webhooks**, create a webhook for the intended channel, and paste that URL into the teacher portal. Slack assigns each incoming webhook to one channel and treats the URL as a secret, so it must never be committed, copied into client-side code, or shared in public messages.[1]

The delivered message contains only the HTTP method, an identifier-redacted API route, status code, and request duration. The setup endpoint itself is excluded from logging to prevent the configuration workflow from emitting its own activity events. Delivery is best-effort and cannot delay the LMS response.

## Operational boundary

| Concern | Design boundary |
| --- | --- |
| Channel ownership | One encrypted destination per teacher account. |
| Secret exposure | Webhook URL is hidden from API resources and password-masked in the UI. |
| Log retention | No operation log payloads or delivery records are written to LMS storage. |
| Failure handling | Database writes are transactional; outbound Slack delivery is non-blocking. |
| Content minimization | Request bodies, learner data, credentials, and webhook secrets are excluded. |

## References

[1]: https://docs.slack.dev/messaging/sending-messages-using-incoming-webhooks "Slack developer documentation: Sending messages using incoming webhooks"
