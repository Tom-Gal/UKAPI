import { Head, Link, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { PageHero } from '@/pages/public/api-catalogue';
import { plans } from '@/lib/ukapi-content';

export default function Pricing() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Pricing">
                <meta
                    name="description"
                    content="Simple UKAPI.io plans and predictable monthly request quotas."
                />
            </Head>
            <PageHero
                eyebrow="Pricing"
                title="Pay for more headroom, not a more complicated product."
                description="Every MVP endpoint counts as one request. There are no launch overages; upgrade when your quota needs to grow."
            />
            <div className="mx-auto max-w-6xl px-5 pb-20 lg:px-8">
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {plans.map((plan) => (
                        <article
                            key={plan.name}
                            className={`flex flex-col rounded-xl border p-6 ${plan.emphasis ? 'border-[#1248e8] bg-blue-50 shadow-sm' : 'border-slate-200 bg-white'}`}
                        >
                            <h2 className="font-semibold">{plan.name}</h2>
                            <p className="mt-5 text-4xl font-semibold tracking-tight">
                                {plan.price}
                                <span className="text-base font-medium text-slate-500">
                                    /month
                                </span>
                            </p>
                            <p className="mt-3 font-mono text-sm text-[#1248e8]">
                                {plan.quota} requests/month
                            </p>
                            <p className="mt-4 min-h-10 text-sm leading-5 text-slate-600">
                                {plan.description}
                            </p>
                            <ul className="mt-6 space-y-3 border-t border-slate-200/80 pt-5 text-sm text-slate-700">
                                <li className="flex gap-2">
                                    <Check className="size-4 shrink-0 text-[#1248e8]" />
                                    One API key account
                                </li>
                                <li className="flex gap-2">
                                    <Check className="size-4 shrink-0 text-[#1248e8]" />
                                    Predictable JSON contracts
                                </li>
                                <li className="flex gap-2">
                                    <Check className="size-4 shrink-0 text-[#1248e8]" />
                                    Documented coverage
                                </li>
                            </ul>
                            <Link
                                href={auth.user ? '/billing' : '/register'}
                                className={`mt-8 rounded-lg px-4 py-2.5 text-center text-sm font-semibold transition ${plan.emphasis ? 'bg-[#1248e8] text-white hover:bg-[#0f3dc4]' : 'border border-slate-300 bg-white text-slate-900 hover:border-slate-400'}`}
                            >
                                {auth.user
                                    ? `Choose ${plan.name}`
                                    : 'Create account'}
                            </Link>
                        </article>
                    ))}
                </div>
                <p className="mt-8 max-w-2xl text-sm leading-6 text-slate-500">
                    The Free plan is available when you create an account. Paid
                    plans are managed securely through Stripe.
                </p>
            </div>
        </>
    );
}
