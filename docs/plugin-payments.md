# Plugin Store Payments

## Supported release-one methods

The plugin store supports **Vodafone Cash**, **InstaPay Egypt**, and **Fawry** as administrator-configured manual payment methods. A center administrator configures the public recipient/account or merchant code plus the customer-facing instructions. These values are intentionally display data; payment-provider API secrets are never accepted in the LMS dashboard or exposed by its API.

| Lifecycle stage | Customer action                        | System behavior                                               | Entitlement effect                                                                     |
| --------------- | -------------------------------------- | ------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| `pending`       | Selects an enabled payment method.     | Creates one open payment transaction for the user and plugin. | None.                                                                                  |
| `submitted`     | Enters the provider receipt/reference. | Locks the request for administrator review.                   | None.                                                                                  |
| `approved`      | No further customer action.            | Administrator approves the reference in the review queue.     | Creates the completed `plugin_purchases` entitlement transactionally and idempotently. |
| `rejected`      | May begin a new payment request.       | Stores review metadata without creating an entitlement.       | None.                                                                                  |

Only a user with the `admin` role can configure payment instructions, view the review queue, approve payment, or reject payment. Teachers, parents, and students cannot access these administrator APIs. Approval is deliberately required before a paid plugin becomes installable.

## Stripe card payments

Stripe card checkout is intentionally **not enabled** until the project owner configures Stripe’s secure server credentials in the project **Settings → Payment** panel. The project’s Stripe sandbox could not be provisioned automatically in the current region. Once valid Stripe credentials are available, the next implementation step is a server-created checkout session and a signature-verified webhook that calls the same idempotent fulfillment boundary used by manual approvals.

Do not enter Stripe secret keys, Vodafone API keys, Fawry merchant secrets, or InstaPay integration secrets into the LMS payment configuration panel. Keep provider secrets in the project payment settings or the provider’s managed credential store.

## Quality gate

Every requested feature is verified before checkpointing using Laravel feature tests, Larastan, frontend tests, TypeScript checking, frontend linting, a production Vite build, Composer validation, CI workflow formatting, and a whitespace diff check. The plugin payment addition passed these checks with 225 Laravel assertions and zero Larastan findings.
