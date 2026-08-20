# Notification Channel Research

## Recommended first release

The first release uses the LMS’s database-backed in-app inbox. It has no external sender credential, consent, or per-message billing requirement, and it provides the authoritative delivery and read state in the application database.

## WhatsApp Business Platform boundary

Meta’s WhatsApp Business Platform Cloud API can send individual and group messages. Free-form service messages are only allowed during a user’s open 24-hour customer-service window; outbound messages after that window require an approved template and documented recipient opt-in. Accepted API responses do not prove delivery, so any production adapter must reconcile delivery state from Meta’s `messages` webhooks.[1] [2]

The Groups API may create and manage invite-only groups, but it requires an Official Business Account, supports at most eight participants per group, is unavailable to WhatsApp Business app and Multi-solution Conversations numbers, and uses per-message pricing. Therefore, the LMS will only activate WhatsApp group automation after the owner supplies an eligible verified business configuration and users opt in.[1]

## References

[1]: https://developers.facebook.com/documentation/business-messaging/whatsapp/groups "Meta: WhatsApp Groups API"
[2]: https://developers.facebook.com/documentation/business-messaging/whatsapp/messages/send-messages "Meta: WhatsApp service messages"
[3]: https://developers.facebook.com/documentation/business-messaging/whatsapp/about-the-platform "Meta: WhatsApp Business Platform overview"
