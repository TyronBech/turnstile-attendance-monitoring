import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { useUiTheme } from '@/hooks/use-ui-theme';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props as {
        auth: { user?: { name?: string } | null };
    };
    const { theme, palette, rgb, orgInitial, orgName } = useUiTheme();

    const primary700 = rgb(palette.primary['700']);
    const primary500 = rgb(palette.primary['500']);

    return (
        <>
            <Head title="Welcome" />

            <div
                className="flex min-h-screen flex-col text-slate-900"
                style={{
                    background: `radial-gradient(circle at top left, ${rgb(palette.secondary['100'])} 0%, ${rgb(palette.secondary['50'])} 42%, ${rgb(palette.primary['50'])} 100%)`,
                }}
            >
                <header
                    className="border-b px-6 py-5 text-white md:px-10"
                    style={{
                        background: `linear-gradient(135deg, ${primary700} 0%, ${rgb(palette.primary['800'])} 100%)`,
                        borderColor: rgb(palette.primary['600']),
                    }}
                >
                    <div className="mx-auto flex max-w-6xl items-center justify-between gap-4">
                        <div className="flex items-center gap-4">
                            <div
                                className="flex h-13 w-13 items-center justify-center rounded-3xl border"
                                style={{
                                    backgroundColor: rgb(palette.primary['500'], 0.18),
                                    borderColor: rgb(palette.primary['500'], 0.42),
                                }}
                            >
                                {theme.logoUrl ? (
                                    <img
                                        src={theme.logoUrl}
                                        alt={orgName}
                                        className="h-8 w-8 object-contain"
                                    />
                                ) : (
                                    <AppLogoIcon className="size-8 fill-current text-white" />
                                )}
                            </div>
                            <div>
                                <p className="text-xs font-semibold tracking-[0.34em] uppercase text-white/70">
                                    {orgInitial}
                                </p>
                                <h1 className="text-lg font-semibold tracking-tight text-white md:text-xl">
                                    {orgName}
                                </h1>
                            </div>
                        </div>

                        <nav className="flex items-center gap-3">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="rounded-full px-5 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90"
                                    style={{ backgroundColor: primary500 }}
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="rounded-full border px-5 py-2 text-sm font-medium text-white/90 transition-colors hover:bg-white/10"
                                        style={{
                                            borderColor: rgb(palette.primary['500'], 0.55),
                                        }}
                                    >
                                        Log in
                                    </Link>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex-1 px-6 py-10 md:px-10 md:py-16">
                    <div className="mx-auto max-w-5xl">
                        <section
                            className="overflow-hidden rounded-[2rem] border bg-white shadow-[0_36px_80px_rgba(15,23,42,0.12)]"
                            style={{ borderColor: rgb(palette.primary['200']) }}
                        >
                            <div
                                className="px-8 py-10 md:px-10 md:py-12"
                                style={{
                                    background: `linear-gradient(145deg, ${primary700} 0%, ${rgb(palette.primary['600'])} 68%, ${primary500} 100%)`,
                                }}
                            >
                                <div className="max-w-2xl space-y-4">
                                    <p className="text-sm font-semibold tracking-[0.28em] uppercase text-white/72">
                                        {orgInitial}
                                    </p>
                                    <h2 className="text-4xl font-semibold tracking-tight text-white md:text-5xl">
                                        {orgName}
                                    </h2>
                                    <p className="text-base text-white/80 md:text-lg">
                                        Organization Attendance System
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-3 px-8 py-8 md:px-10">
                                <Link
                                    href={auth.user ? dashboard() : login()}
                                    className="rounded-full px-5 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90"
                                    style={{ backgroundColor: primary500 }}
                                >
                                    {auth.user ? 'Dashboard' : 'Log in'}
                                </Link>
                            </div>
                        </section>
                    </div>
                </main>

                <footer
                    className="mt-auto border-t px-6 py-5 text-white md:px-10"
                    style={{
                        backgroundColor: primary700,
                        borderColor: rgb(palette.primary['600']),
                    }}
                >
                    <div className="mx-auto flex max-w-6xl flex-col gap-2 text-sm md:flex-row md:items-center md:justify-between">
                        <p className="font-medium text-white">{orgName}</p>
                        <p className="text-white/72">Attendance system.</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
