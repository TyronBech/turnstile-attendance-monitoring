import { Head, Link, usePage } from '@inertiajs/react';
import {
    BellRing,
    ChevronRight,
    FileBarChart2,
    LockKeyhole,
    MapPinned,
    RadioTower,
    ShieldCheck,
    Sparkles,
    TrendingUp,
} from 'lucide-react';
import type { CSSProperties } from 'react';
import { useUiTheme } from '@/hooks/use-ui-theme';
import { dashboard, login, register } from '@/routes';
import popQueryLogo from '../../../public/PopQuery-Logo.svg';

type WelcomeProps = {
    canRegister: boolean;
};

type FeatureCard = {
    title: string;
    description: string;
    icon: typeof RadioTower;
};

type WorkStep = {
    step: string;
    title: string;
    description: string;
};

const featureCards: FeatureCard[] = [
    {
        title: 'Real-Time Monitoring',
        description:
            'Track attendance flow, gate status, and on-site activity from one clear monitoring hub.',
        icon: RadioTower,
    },
    {
        title: 'Analytics That Explain Movement',
        description:
            'See traffic patterns, peak hours, and attendance trends without digging through raw logs.',
        icon: TrendingUp,
    },
    {
        title: 'Instant Alerts',
        description:
            'Catch device issues, failed syncs, and unusual activity fast so operations stay ahead.',
        icon: BellRing,
    },
    {
        title: 'Shareable Reports',
        description:
            'Generate clean summaries your admins and leadership teams can review with confidence.',
        icon: FileBarChart2,
    },
    {
        title: 'Multi-Location Oversight',
        description:
            'Manage multiple gates, buildings, and campuses in one consistent operational view.',
        icon: MapPinned,
    },
    {
        title: 'Security by Default',
        description:
            'Keep monitoring access protected with verified sessions and dependable access controls.',
        icon: LockKeyhole,
    },
];

const workSteps: WorkStep[] = [
    {
        step: '01',
        title: 'Connect Your Setup',
        description:
            'Bring your gates and attendance sources online with a setup designed to get moving quickly.',
    },
    {
        step: '02',
        title: 'Collect Live Activity',
        description:
            'PopQuery streams events, statuses, and scan data into one reliable monitoring surface.',
    },
    {
        step: '03',
        title: 'Spot What Matters',
        description:
            'Review trends, active issues, and movement patterns without bouncing between tools.',
    },
    {
        step: '04',
        title: 'Act With Clarity',
        description:
            'Use alerts, reporting, and visibility to respond faster and keep operations smooth.',
    },
];

export default function Welcome({ canRegister }: WelcomeProps) {
    const page = usePage();
    const { auth } = page.props as {
        auth: { user?: { name?: string } | null };
    };
    const { palette, rgb } = useUiTheme();
    const neutralCardClassName =
        'rounded-[1.75rem] border border-border bg-card p-6 text-card-foreground';
    const neutralPillClassName =
        'inline-flex items-center justify-center rounded-full border border-border bg-background/80 px-6 py-3.5 text-base font-semibold text-foreground';

    const shellStyle = {
        '--welcome-primary-50': palette.primary['50'],
        '--welcome-primary-100': palette.primary['100'],
        '--welcome-primary-500': palette.primary['500'],
        '--welcome-primary-700': palette.primary['700'],
        '--welcome-primary-800': palette.primary['800'],
        '--welcome-secondary-50': palette.secondary['50'],
        '--welcome-secondary-100': palette.secondary['100'],
        '--welcome-tertiary-500': palette.tertiary['500'],
    } as CSSProperties;

    return (
        <>
            <Head title="PopQuery" />

            <div
                className="min-h-screen"
                style={{
                    ...shellStyle,
                    background: `radial-gradient(circle at top left, ${rgb(palette.primary['100'], 0.92)} 0%, ${rgb(palette.secondary['50'])} 36%, ${rgb(palette.primary['50'])} 100%)`,
                }}
            >
                <header className="fixed inset-x-0 top-0 z-50 px-4 py-4 md:px-8">
                    <div className="mx-auto flex max-w-7xl items-center justify-between rounded-full border border-border bg-background/80 px-5 py-3 text-foreground shadow-[0_18px_40px_rgba(15,23,42,0.08)] backdrop-blur-xl">
                        <div className="flex items-center gap-3">
                            <img
                                src={popQueryLogo}
                                alt="PopQuery"
                                className="h-11 w-auto"
                            />
                            <div>
                                <p className="text-[10px] font-semibold tracking-[0.34em] text-muted-foreground uppercase">
                                    PopQuery
                                </p>
                                <p className="text-sm font-semibold">
                                    Smarter attendance visibility
                                </p>
                            </div>
                        </div>

                        <nav className="hidden items-center gap-8 md:flex">
                            <a
                                href="#features"
                                className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                            >
                                Features
                            </a>
                            <a
                                href="#how-it-works"
                                className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                            >
                                How It Works
                            </a>
                            <a
                                href="#cta"
                                className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                            >
                                Get Started
                            </a>
                        </nav>

                        <Link
                            href={auth.user ? dashboard() : login()}
                            className="inline-flex items-center gap-2 rounded-full border border-border bg-background px-5 py-2.5 text-sm font-semibold text-foreground transition-transform hover:scale-[1.02]"
                        >
                            Dashboard
                            <ChevronRight
                                className="size-4"
                                style={{ color: rgb(palette.primary['700']) }}
                            />
                        </Link>
                    </div>
                </header>

                <main className="mx-auto flex max-w-7xl flex-col gap-8 px-4 pt-30 pb-20 md:px-8 md:pt-36">
                    <section className="pt-6 md:pt-10">
                        <div className="mx-auto max-w-4xl text-center">
                            <div className="inline-flex items-center gap-2 rounded-full border border-border bg-background/80 px-4 py-2 text-sm font-semibold text-foreground">
                                <Sparkles
                                    className="size-4"
                                    style={{
                                        color: rgb(palette.primary['700']),
                                    }}
                                />
                                Your trusted IT solutions partner
                            </div>

                            <h1 className="mt-6 text-5xl leading-[0.95] font-black tracking-tight text-foreground md:text-6xl xl:text-7xl">
                                Solution That Moves Your Business Forward.
                            </h1>

                            <p className="mx-auto mt-6 max-w-2xl text-lg leading-8 text-muted-foreground md:text-xl">
                                Turning ideas into impactful digital experiences
                                with monitoring, visibility, and tools that help
                                teams move with confidence.
                            </p>

                            <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                <Link
                                    href={auth.user ? dashboard() : login()}
                                    className="inline-flex items-center justify-center gap-2 rounded-full border border-border bg-background px-6 py-3.5 text-base font-semibold text-foreground shadow-sm"
                                >
                                    {auth.user
                                        ? 'Open Dashboard'
                                        : 'Launch Dashboard'}
                                    <ChevronRight
                                        className="size-4"
                                        style={{
                                            color: rgb(palette.primary['700']),
                                        }}
                                    />
                                </Link>
                                {canRegister && !auth.user ? (
                                    <Link
                                        href={register()}
                                        className={neutralPillClassName}
                                    >
                                        Create Account
                                    </Link>
                                ) : null}
                                <a
                                    href="#features"
                                    className={neutralPillClassName}
                                >
                                    Explore Features
                                </a>
                            </div>
                        </div>
                    </section>

                    <section id="features" className="scroll-mt-28 pt-6">
                        <div className="max-w-2xl">
                            <p className="text-sm font-semibold tracking-[0.28em] text-muted-foreground uppercase">
                                Features
                            </p>
                            <h2 className="mt-3 text-3xl font-black tracking-tight text-foreground md:text-4xl">
                                Clean visibility for busy teams that need
                                answers fast.
                            </h2>
                        </div>

                        <div className="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {featureCards.map((feature) => {
                                const Icon = feature.icon;

                                return (
                                    <article
                                        key={feature.title}
                                        className={neutralCardClassName}
                                    >
                                        <div
                                            className="flex h-12 w-12 items-center justify-center rounded-2xl"
                                            style={{
                                                backgroundColor: rgb(
                                                    palette.primary['100'],
                                                ),
                                                color: rgb(
                                                    palette.primary['700'],
                                                ),
                                            }}
                                        >
                                            <Icon className="size-5" />
                                        </div>
                                        <h3 className="mt-5 text-xl font-black">
                                            {feature.title}
                                        </h3>
                                        <p className="mt-3 text-base leading-7 text-muted-foreground">
                                            {feature.description}
                                        </p>
                                    </article>
                                );
                            })}
                        </div>
                    </section>

                    <section id="how-it-works" className="scroll-mt-28 pt-6">
                        <div className="max-w-2xl">
                            <p className="text-sm font-semibold tracking-[0.28em] text-muted-foreground uppercase">
                                How It Works
                            </p>
                            <h2 className="mt-3 text-3xl font-black tracking-tight text-foreground md:text-4xl">
                                From setup to insights in four simple steps.
                            </h2>
                        </div>

                        <div className="mt-8 grid gap-4 lg:grid-cols-4">
                            {workSteps.map((item) => (
                                <article
                                    key={item.step}
                                    className={neutralCardClassName}
                                >
                                    <p className="text-sm font-semibold tracking-[0.28em] text-muted-foreground uppercase">
                                        {item.step}
                                    </p>
                                    <h3 className="mt-5 text-xl font-black">
                                        {item.title}
                                    </h3>
                                    <p className="mt-3 text-base leading-7 text-muted-foreground">
                                        {item.description}
                                    </p>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section id="cta" className="scroll-mt-28 pt-6">
                        <div
                            className="relative overflow-hidden rounded-[2rem] border p-8 md:p-10"
                            style={{
                                background: `linear-gradient(135deg, ${rgb(palette.primary['800'])} 0%, ${rgb(palette.primary['700'])} 56%, ${rgb(palette.primary['500'])} 100%)`,
                                borderColor: 'rgba(255,255,255,0.16)',
                                boxShadow: `0 28px 70px ${rgb(palette.primary['500'], 0.24)}`,
                            }}
                        >
                            <div
                                className="absolute -top-6 right-0 h-48 w-48 rounded-full blur-3xl"
                                style={{
                                    backgroundColor: rgb(
                                        palette.tertiary['500'],
                                        0.24,
                                    ),
                                }}
                            />
                            <div
                                className="absolute -bottom-10 left-8 h-40 w-40 rounded-full blur-3xl"
                                style={{
                                    backgroundColor: rgb(
                                        palette.secondary['100'],
                                        0.2,
                                    ),
                                }}
                            />

                            <div className="relative max-w-3xl">
                                <div className="inline-flex items-center gap-2 rounded-full border border-white/18 bg-white/8 px-4 py-2 text-sm font-semibold text-white/88">
                                    <ShieldCheck className="size-4" />
                                    Always-on operational awareness
                                </div>
                                <h2 className="mt-5 text-3xl font-black tracking-tight text-white md:text-5xl">
                                    Turn attendance activity into faster
                                    responses and better decisions.
                                </h2>
                                <p className="mt-4 max-w-2xl text-lg leading-8 text-white/76">
                                    PopQuery gives your team a polished,
                                    reliable way to monitor movement, review
                                    trends, and stay confident across every
                                    location you manage.
                                </p>
                                <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                    <Link
                                        href={auth.user ? dashboard() : login()}
                                        className="inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3.5 text-base font-semibold"
                                        style={{ color: 'inherit' }}
                                    >
                                        {auth.user
                                            ? 'Go to Dashboard'
                                            : 'Start With PopQuery'}
                                        <ChevronRight
                                            className="size-4"
                                            style={{
                                                color: rgb(
                                                    palette.primary['700'],
                                                ),
                                            }}
                                        />
                                    </Link>
                                    <a
                                        href="#how-it-works"
                                        className="inline-flex items-center justify-center rounded-full border border-white/20 px-6 py-3.5 text-base font-semibold text-white"
                                    >
                                        See How It Works
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </>
    );
}
