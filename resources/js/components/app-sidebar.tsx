import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    ChartNoAxesCombined,
    CreditCard,
    KeyRound,
    LayoutGrid,
    ScrollText,
    Settings2,
    Shield,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    { title: 'API keys', href: '/api-keys', icon: KeyRound },
    { title: 'Usage', href: '/usage', icon: ChartNoAxesCombined },
    { title: 'Billing', href: '/billing', icon: CreditCard },
    { title: 'Request logs', href: '/request-logs', icon: ScrollText },
    { title: 'Account & security', href: '/settings/profile', icon: Settings2 },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Documentation',
        href: '/docs',
        icon: Activity,
    },
    {
        title: 'Service status',
        href: '/status',
        icon: Shield,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const items = auth.user?.is_admin
        ? [...mainNavItems, { title: 'Admin', href: '/admin', icon: Shield }]
        : mainNavItems;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <p className="px-3 py-2 text-[10px] leading-4 text-slate-500 group-data-[collapsible=icon]:hidden">
                    Independent developer service. Not affiliated with the UK
                    Government.
                </p>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
