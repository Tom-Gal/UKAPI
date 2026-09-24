import { Head } from '@inertiajs/react';
import { AdminNav } from '@/components/admin-nav';
import {
    DashboardPageHeader,
    EmptyState,
} from '@/components/dashboard-page-header';

type User = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    email_verified_at: string | null;
    api_keys_count: number;
    created_at: string;
};
export default function AdminUsers({ users }: { users: User[] }) {
    return (
        <>
            <Head title="Admin users" />
            <DashboardPageHeader
                eyebrow="Internal"
                title="Users"
                description="The 50 most recently created accounts. Role assignment and account changes remain deliberately outside this read-only scaffold."
            />
            <AdminNav />
            <main className="p-5 lg:p-8">
                {users.length === 0 ? (
                    <EmptyState title="No users">
                        <p>No account records are available.</p>
                    </EmptyState>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                        <table className="w-full min-w-[720px] text-left text-sm">
                            <thead className="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
                                <tr>
                                    <th className="px-5 py-3">User</th>
                                    <th className="px-5 py-3">Verification</th>
                                    <th className="px-5 py-3">Keys</th>
                                    <th className="px-5 py-3">Role</th>
                                    <th className="px-5 py-3">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="border-t border-slate-100"
                                    >
                                        <td className="px-5 py-4">
                                            <p className="font-medium">
                                                {user.name}
                                            </p>
                                            <p className="text-slate-500">
                                                {user.email}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4 text-slate-600">
                                            {user.email_verified_at
                                                ? 'Verified'
                                                : 'Unverified'}
                                        </td>
                                        <td className="px-5 py-4 text-slate-600">
                                            {user.api_keys_count}
                                        </td>
                                        <td className="px-5 py-4">
                                            <span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                                                {user.is_admin
                                                    ? 'Admin'
                                                    : 'User'}
                                            </span>
                                        </td>
                                        <td className="px-5 py-4 text-slate-600">
                                            {new Date(
                                                user.created_at,
                                            ).toLocaleDateString()}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </main>
        </>
    );
}
AdminUsers.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'Users', href: '/admin/users' },
    ],
};
