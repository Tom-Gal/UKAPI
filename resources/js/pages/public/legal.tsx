import { Head, usePage } from '@inertiajs/react';
import acceptableUsePolicy from '../../content/acceptable-use-policy';
import privacyPolicy from '../../content/privacy-policy';
import termsOfUse from '../../content/terms-of-use';

export type LegalDocument = {
    title: string;
    summary: string;
    sections: { title: string; paragraphs: string[] }[];
};

const documents: Record<string, LegalDocument> = {
    terms: termsOfUse,
    'paid-plan-terms': {
        title: 'Paid Plan Terms',
        summary:
            'The additional terms for paid subscriptions, billing, renewal, cancellation, and refunds.',
        sections: [
            {
                title: 'Plans, prices, and checkout',
                paragraphs: [
                    'Current paid Plans, features, usage limits, and prices are shown on the Pricing page. Prices are in pounds sterling and the total payable, including VAT where applicable, is shown before purchase. You pay for access to the Service, not ownership of Data.',
                    'Your Plan contract starts when we confirm your purchase by email. Payment is processed through Stripe; UKApi does not store your full card details.',
                ],
            },
            {
                title: 'Billing and renewal',
                paragraphs: [
                    'Paid Plans are billed monthly or annually in advance and renew automatically for the same period at the then-current price until cancelled. Annual Plans are priced at approximately ten monthly payments, representing two months free. We will show the renewal terms before purchase and send any renewal reminders required by law.',
                    'If a payment fails, we will email you and retry it. If payment still cannot be collected after warnings, we may suspend the paid Plan and move the account to the free Plan. Amounts already due remain payable.',
                ],
            },
            {
                title: 'Features, limits, changes, and cancellation',
                paragraphs: [
                    'Each Plan has a documented feature set, monthly request quota, burst Rate Limit, and maximum page size. When the monthly quota is reached, requests are blocked until the next monthly reset or an upgrade. There is no overage billing at launch and UKApi does not automatically charge for requests above quota. Upgrades take effect immediately; cancellation takes effect at the end of the period already paid for and access continues until then.',
                    'Historical data, change feeds, commercial use and bulk export are available only on the Plans shown on the Pricing page. We may change prices with at least 30 days’ written notice, effective only from the next renewal. You can cancel before the change takes effect without being charged the new price.',
                ],
            },
            {
                title: 'Consumer cancellation and refunds',
                paragraphs: [
                    'Consumers may cancel within 14 days of purchase without giving a reason. If you asked us to start the Service during that period, we may deduct an amount proportionate to the Service supplied before cancellation. We will refund the balance within 14 days using the original payment method.',
                    'Your statutory rights are not affected. If a paid feature or dataset is materially removed, you may cancel and receive a refund for the unused part of the paid period.',
                ],
            },
            {
                title: 'Service level and records',
                paragraphs: [
                    'We do not offer a guaranteed service level on any Plan. Billing records are retained for the period required by tax and accounting law. No change to these Terms increases what you owe for a period you have already paid for.',
                ],
            },
            {
                title: 'Model cancellation wording',
                paragraphs: [
                    'To cancel, email the support address shown in the footer and state that you cancel your UKApi Plan, the order date, your name and address, and the date of cancellation. A signed paper form is optional.',
                ],
            },
        ],
    },
    privacy: privacyPolicy,
    'acceptable-use': acceptableUsePolicy,
};

export default function Legal() {
    const key =
        usePage().url.split('?')[0].split('/').filter(Boolean).at(-1) ??
        'terms';
    const document = documents[key] ?? documents.terms;

    return (
        <>
            <Head title={document.title}>
                <meta name="description" content={document.summary} />
            </Head>
            <article className="atlas-shell max-w-4xl py-16 lg:py-24">
                <p className="atlas-kicker">
                    Legal · Last updated 18 October 2026
                </p>
                <h1 className="mt-4 text-5xl font-semibold tracking-[-.045em]">
                    {document.title}
                </h1>
                <p className="mt-4 text-lg leading-8 text-slate-600">
                    {document.summary}
                </p>
                <div className="mt-10 divide-y divide-slate-300 border border-slate-950 bg-white">
                    {document.sections.map((section) => (
                        <section key={section.title} className="p-6 sm:p-8">
                            <h2 className="font-sans text-xl font-bold">
                                {section.title}
                            </h2>
                            <div className="mt-4 space-y-4 leading-7 text-slate-600">
                                {section.paragraphs.map((paragraph) => (
                                    <p key={paragraph}>{paragraph}</p>
                                ))}
                            </div>
                        </section>
                    ))}
                </div>
            </article>
        </>
    );
}
