import { Head, Link, usePage } from '@inertiajs/react';
import { OrgFooter } from '@/components/org-footer';
import { OrgHeader } from '@/components/org-header';
import { useUiTheme } from '@/hooks/use-ui-theme';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props as {
        auth: { user?: { name?: string } | null };
    };
    const { palette, rgb, orgInitial, orgName } = useUiTheme();

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
                <OrgHeader variant="public" maxWidth="max-w-6xl" />

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
                                    <p className="text-sm font-semibold tracking-[0.28em] text-white/72 uppercase">
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

                <OrgFooter maxWidth="max-w-6xl" />
            </div>
        </>
    );
}
