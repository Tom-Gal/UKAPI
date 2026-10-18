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
                    content="Simple UKApi.co.uk plans and predictable monthly request quotas."
                />
            </Head>
            <PageHero
                eyebrow="Pricing"
                title="Pay for more headroom, not a more complicated product."
                description="Every MVP endpoint counts as one request. There are no launch overages; upgrade when your quota needs to grow."
            />
            <div className="atlas-shell pb-24">
                <div className="grid border-t border-l border-slate-950 md:grid-cols-2 lg:grid-cols-4">
                    {plans.map((plan) => (
                        <article
                            key={plan.name}
                            className={`relative flex flex-col border-r border-b border-slate-950 p-6 ${plan.emphasis ? 'bg-[#122019] text-white' : 'bg-white'}`}
                        >
                            {plan.emphasis && (
                                <span className="absolute top-0 right-0 bg-[#2563c7] px-2 py-1 font-mono text-[9px] tracking-widest uppercase">
                                    Recommended
                                </span>
                            )}
                            <h2 className="font-sans font-bold">{plan.name}</h2>
                            <p className="mt-5 text-4xl font-semibold tracking-tight">
                                {plan.price}
                                <span
                                    className={`text-base font-medium ${plan.emphasis ? 'text-[#aeb8af]' : 'text-slate-500'}`}
                                >
                                    /month
                                </span>
                            </p>
                            <p
                                className={`mt-3 font-mono text-sm ${plan.emphasis ? 'text-[#b9cba6]' : 'text-[#2563c7]'}`}
                            >
                                {plan.quota} requests/month
                            </p>
                            <p
                                className={`mt-4 min-h-10 text-sm leading-5 ${plan.emphasis ? 'text-[#cbd2c7]' : 'text-slate-600'}`}
                            >
                                {plan.description}
                            </p>
                            <ul
                                className={`mt-6 space-y-3 border-t pt-5 text-sm ${plan.emphasis ? 'border-[#526158] text-[#e7ece6]' : 'border-slate-300 text-slate-700'}`}
                            >
                                <li className="flex gap-2">
                                    <Check className="size-4 shrink-0 text-[#2563c7]" />
                                    One API key account
                                </li>
                                <li className="flex gap-2">
                                    <Check className="size-4 shrink-0 text-[#2563c7]" />
                                    Predictable JSON contracts
                                </li>
                                <li className="flex gap-2">
                                    <Check className="size-4 shrink-0 text-[#2563c7]" />
                                    Documented coverage
                                </li>
                            </ul>
                            <Link
                                href={auth.user ? '/billing' : '/register'}
                                className={`mt-8 border px-4 py-3 text-center text-sm font-bold transition ${plan.emphasis ? 'border-white bg-white text-[#122019] hover:bg-[#dce9ff]' : 'border-slate-950 bg-transparent text-slate-900 hover:bg-[#e4ece7]'}`}
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
