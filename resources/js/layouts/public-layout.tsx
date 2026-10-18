import { Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Menu, X } from 'lucide-react';
import { useState, type PropsWithChildren } from 'react';
import { UkapiMark } from '@/components/ukapi-mark';

const navigation = [
    ['API catalogue', '/api-catalogue'],
    ['Documentation', '/docs'],
    ['Pricing', '/pricing'],
    ['Status', '/status'],
];

export default function PublicLayout({ children }: PropsWithChildren) {
    const page = usePage();
    const { auth } = page.props;
    const currentPath = page.url.split('?')[0];
    const [menuOpen, setMenuOpen] = useState(false);
    const isActive = (href: string) =>
        currentPath === href ||
        (href === '/docs' && currentPath.startsWith('/docs/'));

    return (
        <div className="min-h-svh overflow-x-clip bg-[#f5f7f4] text-slate-950">
            <a
                href="#main-content"
                className="fixed top-3 left-3 z-[60] -translate-y-20 border border-slate-950 bg-slate-950 px-4 py-2 text-sm font-bold text-white focus:translate-y-0"
            >
                Skip to content
            </a>
            <div className="border-b border-slate-950 bg-[#2563c7] text-white">
                <div className="atlas-shell flex items-center justify-between py-1.5 font-mono text-[9px] tracking-[0.16em] uppercase sm:text-[10px]">
                    <span>Independent UK developer service</span>
                    <span className="hidden sm:inline">API release · v1</span>
                </div>
            </div>
            <header className="sticky top-0 z-50 border-b border-slate-950 bg-[#f5f7f4]/95 backdrop-blur">
                <nav
                    className="atlas-shell flex min-h-[72px] items-center justify-between gap-5"
                    aria-label="Main navigation"
                >
                    <Link
                        href="/"
                        aria-label="UKApi.co.uk home"
                        className="shrink-0"
                    >
                        <UkapiMark />
                    </Link>
                    <div className="hidden h-[72px] items-stretch border-x border-slate-300 lg:flex">
                        {navigation.map(([label, href]) => (
                            <Link
                                key={href}
                                href={href}
                                aria-current={
                                    isActive(href) ? 'page' : undefined
                                }
                                className={`flex items-center border-r border-slate-300 px-5 font-mono text-[11px] font-bold tracking-[0.08em] uppercase transition last:border-r-0 ${isActive(href) ? 'bg-[#e4ece7] text-slate-950 shadow-[inset_0_-3px_0_#2563c7]' : 'text-slate-600 hover:bg-white/60 hover:text-slate-950'}`}
                            >
                                {label}
                            </Link>
                        ))}
                    </div>
                    <div className="flex items-center gap-2">
                        {auth.user ? (
                            <Link href="/dashboard" className="atlas-button">
                                Dashboard <ArrowUpRight className="size-4" />
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="hidden px-3 py-2 text-sm font-bold text-slate-700 hover:text-[#2563c7] sm:block"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href="/register"
                                    className="atlas-button px-3 sm:px-4"
                                >
                                    <span>
                                        <span className="hidden sm:inline">
                                            Get an{' '}
                                        </span>
                                        API key
                                    </span>
                                    <ArrowUpRight className="hidden size-4 sm:block" />
                                </Link>
                            </>
                        )}
                        <button
                            type="button"
                            className="grid size-11 place-items-center border border-slate-950 lg:hidden"
                            aria-expanded={menuOpen}
                            aria-controls="mobile-navigation"
                            onClick={() => setMenuOpen(!menuOpen)}
                        >
                            {menuOpen ? (
                                <X className="size-5" />
                            ) : (
                                <Menu className="size-5" />
                            )}
                            <span className="sr-only">Toggle navigation</span>
                        </button>
                    </div>
                </nav>
                {menuOpen && (
                    <div
                        id="mobile-navigation"
                        className="border-t border-slate-950 bg-white lg:hidden"
                    >
                        <div className="atlas-shell grid grid-cols-2 p-0">
                            {navigation.map(([label, href]) => (
                                <Link
                                    key={href}
                                    href={href}
                                    onClick={() => setMenuOpen(false)}
                                    aria-current={
                                        isActive(href) ? 'page' : undefined
                                    }
                                    className={`border-r border-b border-slate-300 px-4 py-4 font-mono text-xs font-bold uppercase ${isActive(href) ? 'bg-[#e4ece7] text-[#2563c7]' : 'text-slate-700'}`}
                                >
                                    {label}
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </header>
            <main id="main-content">{children}</main>
            <footer className="border-t border-slate-950 bg-[#122019] text-[#f5f7f4]">
                <div className="atlas-shell grid gap-10 py-12 md:grid-cols-[1.4fr_.8fr_.8fr]">
                    <div>
                        <UkapiMark className="text-[#f5f7f4] [&_span:last-child_span:last-child]:text-[#8fb49f]" />
                        <p className="mt-5 max-w-md text-sm leading-6 text-[#cbd2c7]">
                            Useful UK data through one predictable API. Built
                            for developers, with source limits left in view.
                        </p>
                    </div>
                    <FooterLinks
                        title="Build"
                        links={[
                            ['Documentation', '/docs'],
                            ['Data sources', '/data-sources'],
                            ['Service status', '/status'],
                        ]}
                    />
                    <FooterLinks
                        title="Policies"
                        links={[
                            ['Terms', '/terms'],
                            ['Privacy', '/privacy'],
                            ['Acceptable use', '/acceptable-use'],
                        ]}
                    />
                </div>
                <div className="border-t border-[#415047]">
                    <div className="atlas-shell flex flex-col gap-2 py-5 font-mono text-[10px] leading-5 tracking-[0.08em] text-[#aeb8af] uppercase sm:flex-row sm:justify-between">
                        <span>© {new Date().getFullYear()} UKApi.co.uk</span>
                        <span>
                            Not affiliated with or endorsed by the UK Government
                        </span>
                    </div>
                </div>
            </footer>
        </div>
    );
}

function FooterLinks({ title, links }: { title: string; links: string[][] }) {
    return (
        <div>
            <p className="font-mono text-[10px] font-bold tracking-[0.18em] text-[#8fb49f] uppercase">
                {title}
            </p>
            <div className="mt-4 grid gap-2 text-sm">
                {links.map(([label, href]) => (
                    <Link
                        key={href}
                        href={href}
                        className="w-fit border-b border-transparent py-1 text-[#e9eee7] hover:border-[#6ea1f2] hover:text-white"
                    >
                        {label}
                    </Link>
                ))}
            </div>
        </div>
    );
}
