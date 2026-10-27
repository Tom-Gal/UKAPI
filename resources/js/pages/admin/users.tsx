import { Form, Head } from '@inertiajs/react';
import { ExternalLink, KeyRound, ShieldAlert } from 'lucide-react';
import { AdminNav } from '@/components/admin-nav';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type ApiKey = {
    id: number;
    name: string;
    prefix: string;
    environment: string;
    status: string;
    expires_at: string | null;
};

type User = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    email_verified_at: string | null;
    created_at: string;
    plan: string;
    monthly_usage: number;
    monthly_quota: number;
    rate_limit_rejections: number;
    quota_rejections: number;
    subscription: {
        status: string;
        ends_at: string | null;
        stripe_id: string;
    } | null;
    stripe_url: string | null;
    api_keys: ApiKey[];
};

export default function AdminUsers({ users }: { users: User[] }) {
    return (
        <>
            <Head title="Admin users" />
            <DashboardPageHeader
                eyebrow="Internal"
                title="Users and entitlements"
                description="Locally synchronised plan, subscription, quota, rejection, and API-key state. Stripe links open the matching customer without making a billing API call."
            />
            <AdminNav />
            <main className="space-y-5 p-5 lg:p-8">
                {users.length === 0 ? (
                    <EmptyState title="No users">
                        <p>No account records are available.</p>
                    </EmptyState>
                ) : (
                    users.map((user) => (
                        <article
                            key={user.id}
                            className="border border-slate-200 bg-white"
                        >
                            <div className="grid gap-5 border-b border-slate-200 p-5 lg:grid-cols-[1.2fr_.8fr_.8fr]">
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="font-semibold text-slate-950">
                                            {user.name}
                                        </h2>
                                        <Badge>
                                            {user.is_admin ? 'Admin' : 'User'}
                                        </Badge>
                                        <Badge>{user.plan}</Badge>
                                    </div>
                                    <p className="mt-1 text-sm text-slate-600">
                                        {user.email}
                                    </p>
                                    <p className="mt-2 text-xs text-slate-500">
                                        {user.email_verified_at
                                            ? 'Verified'
                                            : 'Email unverified'}{' '}
                                        · Joined{' '}
                                        {new Date(
                                            user.created_at,
                                        ).toLocaleDateString('en-GB')}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs font-bold tracking-wide text-slate-500 uppercase">
                                        Monthly usage
                                    </p>
                                    <p className="mt-2 font-mono text-lg font-semibold">
                                        {user.monthly_usage.toLocaleString()} /{' '}
                                        {user.monthly_quota.toLocaleString()}
                                    </p>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {user.rate_limit_rejections.toLocaleString()}{' '}
                                        rate-limit,{' '}
                                        {user.quota_rejections.toLocaleString()}{' '}
                                        quota rejections
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs font-bold tracking-wide text-slate-500 uppercase">
                                        Subscription
                                    </p>
                                    <p className="mt-2 text-sm font-semibold text-slate-800">
                                        {user.subscription
                                            ? user.subscription.status
                                            : 'No paid subscription'}
                                    </p>
                                    {user.subscription?.ends_at && (
                                        <p className="mt-1 text-xs text-slate-500">
                                            Ends{' '}
                                            {new Date(
                                                user.subscription.ends_at,
                                            ).toLocaleDateString('en-GB')}
                                        </p>
                                    )}
                                    {user.stripe_url && (
                                        <a
                                            href={user.stripe_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-[#174ea6] underline underline-offset-4"
                                        >
                                            Open Stripe customer
                                            <ExternalLink className="size-3" />
                                        </a>
                                    )}
                                </div>
                            </div>

                            <div className="p-5">
                                <h3 className="flex items-center gap-2 text-sm font-semibold">
                                    <KeyRound className="size-4" /> Recent API
                                    keys
                                </h3>
                                {user.api_keys.length === 0 ? (
                                    <p className="mt-3 text-sm text-slate-500">
                                        No API keys.
                                    </p>
                                ) : (
                                    <div className="mt-3 divide-y divide-slate-100 border-y border-slate-100">
                                        {user.api_keys.map((apiKey) => (
                                            <div
                                                key={apiKey.id}
                                                className="grid gap-3 py-4 lg:grid-cols-[1fr_auto] lg:items-center"
                                            >
                                                <div>
                                                    <p className="text-sm font-medium text-slate-900">
                                                        {apiKey.name}{' '}
                                                        <span className="text-xs font-normal text-slate-500">
                                                            ({apiKey.status})
                                                        </span>
                                                    </p>
                                                    <p className="mt-1 font-mono text-xs text-slate-500">
                                                        {apiKey.prefix}…
                                                    </p>
                                                </div>
                                                {apiKey.status === 'active' && (
                                                    <Form
                                                        action={`/admin/api-keys/${apiKey.id}/revoke`}
                                                        method="post"
                                                        className="flex flex-col gap-2 sm:flex-row sm:items-start"
                                                    >
                                                        {({
                                                            processing,
                                                            errors,
                                                        }) => (
                                                            <>
                                                                <div>
                                                                    <Input
                                                                        name="reason"
                                                                        required
                                                                        minLength={
                                                                            10
                                                                        }
                                                                        maxLength={
                                                                            500
                                                                        }
                                                                        placeholder="Security or support reason"
                                                                        className="min-w-[240px]"
                                                                        aria-label={`Reason for revoking ${apiKey.name}`}
                                                                    />
                                                                    {errors.reason && (
                                                                        <p className="mt-1 text-xs text-red-600">
                                                                            {
                                                                                errors.reason
                                                                            }
                                                                        </p>
                                                                    )}
                                                                </div>
                                                                <Button
                                                                    type="submit"
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                    variant="outline"
                                                                    className="border-red-200 text-red-700 hover:bg-red-50"
                                                                >
                                                                    <ShieldAlert className="size-4" />
                                                                    Revoke key
                                                                </Button>
                                                            </>
                                                        )}
                                                    </Form>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                                <p className="mt-3 text-xs leading-5 text-slate-500">
                                    Support revocation requires recent password
                                    confirmation, records the administrator and
                                    reason, and notifies the account owner. No
                                    impersonation or entitlement editing is
                                    available here.
                                </p>
                            </div>
                        </article>
                    ))
                )}
            </main>
        </>
    );
}

function Badge({ children }: { children: React.ReactNode }) {
    return (
        <span className="bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
            {children}
        </span>
    );
}

AdminUsers.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Users', href: '/admin/users' },
    ],
};
