# Egyptian Plugin Payment Provider Notes

## Decision boundary

The plugin store will support **administrator-configured manual payment instructions** for Vodafone Cash and InstaPay Egypt, with proof/reference submission and administrator approval. It must not represent either method as automatically verified until the center has a merchant contract and production integration credentials for the relevant provider or payment service provider.

Vodafone’s [Developer Marketplace onboarding guide](https://developer.vodafone.com/docs/get-started) documents the sandbox-app and API-key flow. It also states that a company must be validated before requesting production access. The project will therefore keep Vodafone Cash configuration as public payment instructions until the owner supplies a verified supported product and production credentials.

The public Vodafone Cash consumer payment page was not reachable from the project environment during research, so it is not used as evidence of a merchant API contract. No official public InstaPay Egypt merchant API documentation was identified in this review. Consequently, the first release uses a manual-reference workflow for both Egyptian methods.

| Method              | Initial capability                                                                                          | Entitlement rule                                                                          |
| ------------------- | ----------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| Stripe card payment | Disabled until secure project payment credentials are configured.                                           | Fulfill only from a verified provider webhook.                                            |
| Vodafone Cash       | Display center-configured account/mobile instructions and collect a customer reference.                     | Fulfill only after administrator review, unless a verified merchant API is later enabled. |
| InstaPay Egypt      | Display center-configured payment handle/account instructions and collect a customer reference.             | Fulfill only after administrator review, unless a verified merchant API is later enabled. |
| Fawry               | Display center-configured merchant code or collection instructions and collect the Fawry receipt reference. | Fulfill only after administrator review, unless a verified merchant API is later enabled. |

Provider API secrets are never stored in the teacher dashboard or returned by the API. Only a center administrator can configure non-secret payment instructions and review manual payments.
