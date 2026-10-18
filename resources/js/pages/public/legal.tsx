import { Head, usePage } from '@inertiajs/react';

type LegalDocument = { title: string; summary: string; sections: { title: string; paragraphs: string[] }[] };

const shared = [
    'UKApi is an independent developer service. It is not part of, authorised by, or endorsed by any government department, public body, or other data Source.',
    'Our website and API provide general information, not legal, financial, medical, planning, safety, or other professional advice. Check the original Source before relying on data for an important decision.',
];

const documents: Record<string, LegalDocument> = {
    terms: {
        title: 'Terms of Use', summary: 'The terms that apply when you create an account or use the UKApi website and API.',
        sections: [
            { title: 'About the Service', paragraphs: [...shared, 'UKApi collects, standardises, and delivers data from public UK Sources. Each dataset has its own Source, licence, and attribution requirements. The Source Licence governs what you may do with the Data; these Terms govern how you access the Service.'] },
            { title: 'Accounts, keys, and Plans', paragraphs: ['Keep your registration details, login credentials, and API keys accurate and confidential. Do not share a key except with people working on your application, publish it in public code, or create multiple accounts to evade a limit.', 'The free and paid Plans, features, quotas, and prices are shown on the Pricing page. Paid Plans are also subject to the Paid Plan Terms.'] },
            { title: 'Availability, data, and changes', paragraphs: ['We take reasonable care to deliver Data as published by its Source, but Data may be delayed, cached, reformatted, incomplete, or inaccurate. Do not rely on it for decisions where an error could cause serious harm without checking the Source.', 'We may add, change, or remove features or datasets when a Source changes its publication, format, licence, or access, or when law or security requires it. We will give notice of Breaking Changes where reasonably possible.'] },
            { title: 'Acceptable use', paragraphs: ['You must follow the Acceptable Use Policy. Do not attack or overload the Service, misuse credentials, evade Rate Limits or billing, conceal your identity to avoid controls, break the law, misstate a Source, identify individuals from Data, or offer the Service itself as a competing proxy without written permission.', 'Automated tools, including machine-learning tools, may use Data only as far as the Source Licence allows and must observe Rate Limits, bulk-export guidance, attribution requirements, and an identifying User-Agent.'] },
            { title: 'Suspension, liability, and ending access', paragraphs: ['We may suspend or restrict access for serious AUP or Rate Limit breaches, compromised keys, legal or Source requirements, or unpaid amounts, and will explain what happened where we can. You may close your account at any time; Paid Plan cancellation is covered by the Paid Plan Terms.', 'Nothing limits liability that cannot legally be limited, including death or personal injury caused by negligence, fraud, or statutory consumer rights. Business-user liability exclusions and the contractual cap apply as set out in the full agreement.'] },
            { title: 'Privacy, complaints, and governing law', paragraphs: ['Our Privacy Notice explains how we use personal information. Concerns about Data or rights can be sent to the legal contact shown below. These Terms are governed by the law of England and Wales, subject to consumer rights to bring proceedings where they live.'] },
        ],
    },
    'paid-plan-terms': {
        title: 'Paid Plan Terms', summary: 'The additional terms for paid subscriptions, billing, renewal, cancellation, and refunds.',
        sections: [
            { title: 'Plans, prices, and checkout', paragraphs: ['Current paid Plans, features, usage limits, and prices are shown on the Pricing page. Prices are in pounds sterling and the total payable, including VAT where applicable, is shown before purchase. You pay for access to the Service, not ownership of Data.', 'Your Plan contract starts when we confirm your purchase by email. Payment is processed through Stripe; UKApi does not store your full card details.'] },
            { title: 'Billing and renewal', paragraphs: ['Paid Plans are billed monthly in advance and renew automatically for the same period at the then-current price until cancelled. We will show the renewal terms before purchase and send any renewal reminders required by law.', 'If a payment fails, we will email you and retry it. If payment still cannot be collected after warnings, we may suspend the paid Plan and move the account to the free Plan. Amounts already due remain payable.'] },
            { title: 'Limits, changes, and cancellation', paragraphs: ['Each Plan has a monthly request quota and burst Rate Limit. When the monthly quota is reached, requests are blocked until the next monthly reset or an upgrade. Upgrades take effect immediately; cancellation takes effect at the end of the period already paid for and access continues until then.', 'We may change prices with at least 30 days’ written notice, effective only from the next renewal. You can cancel before the change takes effect without being charged the new price.'] },
            { title: 'Consumer cancellation and refunds', paragraphs: ['Consumers may cancel within 14 days of purchase without giving a reason. If you asked us to start the Service during that period, we may deduct an amount proportionate to the Service supplied before cancellation. We will refund the balance within 14 days using the original payment method.', 'Your statutory rights are not affected. If a paid feature or dataset is materially removed, you may cancel and receive a refund for the unused part of the paid period.'] },
            { title: 'Service level and records', paragraphs: ['We do not offer a guaranteed service level on any Plan. Billing records are retained for the period required by tax and accounting law. No change to these Terms increases what you owe for a period you have already paid for.'] },
            { title: 'Model cancellation wording', paragraphs: ['To cancel, email the support address shown in the footer and state that you cancel your UKApi Plan, the order date, your name and address, and the date of cancellation. A signed paper form is optional.'] },
        ],
    },
    privacy: {
        title: 'Privacy Notice', summary: 'How UKApi uses personal information about website visitors, account holders, API users, customers, and people who contact us.',
        sections: [
            { title: 'Who we are', paragraphs: ['UKApi is operated by the legal entity identified in the footer and is the controller of the personal information described here. Questions, rights requests, and privacy complaints can be sent to privacy@ukapi.co.uk. ICO registration details are maintained in the deployment configuration.'] },
            { title: 'Information we collect', paragraphs: ['We collect account details; API-key identifiers and usage records; billing and transaction references; support communications; website, device, IP, and cookie information; marketing preferences; and security or abuse records. We do not receive or store full card numbers, and we do not knowingly collect special-category data or children’s data.', 'API requests may contain a postcode or place name. Do not include another person’s personal information in a request. The Data delivered by the API is not intended to contain personal data; report anything that appears to identify a person.'] },
            { title: 'How we use it', paragraphs: ['We use information to provide accounts and the Service, issue keys, take payment, send service and security notices, enforce Rate Limits and the AUP, prevent fraud and abuse, support users, monitor and improve reliability, meet legal obligations, and establish or defend legal claims. Marketing is sent only with consent or where the PECR soft opt-in applies, and every marketing email includes an unsubscribe link.'] },
            { title: 'Sharing and transfers', paragraphs: ['We share information only as needed with hosting, payment, email, support, security, and other service providers acting on our instructions; professional advisers; authorities where required; and a buyer or successor in a business reorganisation. We do not sell personal information or use it for third-party advertising. International transfers use a lawful safeguard such as UK adequacy regulations, the UK–US data bridge where applicable, the ICO IDTA, or the UK Addendum.'] },
            { title: 'Retention and your rights', paragraphs: ['We retain account information while the account is open and for a limited period after closure; request logs for operational purposes only; billing records as required for tax and accounting; support and security records only as long as needed; and marketing preferences until withdrawal plus a minimal suppression record. We may retain information longer for legal claims or where law requires.', 'You may request access, correction, deletion, restriction, portability, or object to processing based on legitimate interests or direct marketing. We normally respond within one month and may extend complex requests by up to two further months. You may complain to the ICO at ico.org.uk/make-a-complaint.'] },
            { title: 'Cookies and security', paragraphs: ['The website uses strictly necessary session and CSRF cookies. The API does not use cookies. We do not set non-essential analytics cookies unless consent has been obtained. We use encryption in transit, restricted access, hashed credentials, and regular security review.'] },
        ],
    },
    'acceptable-use': {
        title: 'Acceptable Use Policy', summary: 'Rules for accessing UKApi fairly, securely, and consistently with Source Licences.',
        sections: [
            { title: 'You must not', paragraphs: ['Attack, disrupt, degrade, overload, scan, probe, or security-test the Service without permission; ignore Rate Limits, retry-after headers, or HTTP errors; misuse another person’s account or key; or bypass quotas, Plan features, or billing.', 'You must not conceal your identity to avoid controls, break the law, infringe rights, distribute malware or spam, misstate a Source or imply endorsement, remove required attribution, identify individuals from Data, or resell the Service itself as a competing API without written agreement.'] },
            { title: 'Automated tools and AI', paragraphs: ['You may use Data in automated tools, including training or evaluating machine-learning models, only as far as the applicable Source Licence permits. Follow Rate Limits, use bulk exports for large volumes, provide required attribution, and identify automated clients in the User-Agent with a contact address.'] },
            { title: 'Reporting and enforcement', paragraphs: ['Report a vulnerability to security@ukapi.co.uk and report Data that appears inaccurate, infringes rights, or identifies a person to legal@ukapi.co.uk or privacy@ukapi.co.uk. Good-faith researchers who minimise access, avoid disruption, and allow reasonable remediation time will not be pursued under this policy.', 'Where possible we start with a warning or throttling. We may suspend or revoke a key immediately when a breach is serious or risks the Service or other users. If you think we have made a mistake, contact support and we will review it.'] },
        ],
    },
};

export default function Legal() {
    const key = usePage().url.split('?')[0].split('/').filter(Boolean).at(-1) ?? 'terms';
    const document = documents[key] ?? documents.terms;

    return (
        <>
            <Head title={document.title}><meta name="description" content={document.summary} /></Head>
            <article className="atlas-shell max-w-4xl py-16 lg:py-24">
                <p className="atlas-kicker">Legal · Last updated 18 October 2026</p>
                <h1 className="mt-4 text-5xl font-semibold tracking-[-.045em]">{document.title}</h1>
                <p className="mt-4 text-lg leading-8 text-slate-600">{document.summary}</p>
                <div className="mt-10 divide-y divide-slate-300 border border-slate-950 bg-white">
                    {document.sections.map((section) => (
                        <section key={section.title} className="p-6 sm:p-8">
                            <h2 className="font-sans text-xl font-bold">{section.title}</h2>
                            <div className="mt-4 space-y-4 leading-7 text-slate-600">
                                {section.paragraphs.map((paragraph) => <p key={paragraph}>{paragraph}</p>)}
                            </div>
                        </section>
                    ))}
                </div>
            </article>
        </>
    );
}
