import { Link, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { useUiTheme } from '@/hooks/use-ui-theme';
import { dashboard, login, logout } from '@/routes';

interface OrgHeaderProps {
    variant?: 'public' | 'inside' | 'auth';
    maxWidth?: string;
    authTitle?: string;
    authDescription?: string;
}

export function OrgHeader({
    variant = 'public',
    maxWidth = 'max-w-6xl',
    authTitle,
    authDescription,
}: OrgHeaderProps) {
    const { auth } = usePage().props as {
        auth: { user?: { name?: string } | null };
    };
    const { theme, palette, rgb, orgInitial, orgName } = useUiTheme();

    const primary700 = rgb(palette.primary['700']);
    const primary500 = rgb(palette.primary['500']);

    return (
        <header
            className="border-b px-6 py-5 text-white md:px-10"
            style={{
                background: `linear-gradient(135deg, ${primary700} 0%, ${rgb(palette.primary['800'])} 100%)`,
                borderColor: rgb(palette.primary['600']),
            }}
        >
            <div className={`mx-auto flex items-center justify-between gap-4 ${maxWidth}`}>
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
                        <p className="text-xs font-semibold tracking-[0.34em] text-white/70 uppercase">
                            {orgInitial}
                        </p>
                        <h1 className="text-lg font-semibold tracking-tight text-white md:text-xl">
                            {orgName}
                        </h1>
                    </div>
                </div>

                <nav className="flex items-center gap-4">
                    {variant === 'public' && (
                        <>
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="rounded-full px-5 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90"
                                    style={{ backgroundColor: primary500 }}
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <Link
                                    href={login()}
                                    className="rounded-full border px-5 py-2 text-sm font-medium text-white/90 transition-colors hover:bg-white/10"
                                    style={{
                                        borderColor: rgb(palette.primary['500'], 0.55),
                                    }}
                                >
                                    Log in
                                </Link>
                            )}
                        </>
                    )}

                    {variant === 'inside' && auth.user && (
                        <>
                            <span className="hidden text-sm text-white/80 md:inline-block font-medium">
                                Logged in as: <span className="text-white font-semibold">{auth.user.name}</span>
                            </span>
                            <Link
                                href={logout()}
                                method="post"
                                as="button"
                                className="rounded-full px-5 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90 flex items-center gap-2"
                                style={{ backgroundColor: primary500 }}
                            >
                                Log Out
                                <LogOut className="size-4" />
                            </Link>
                        </>
                    )}

                    {variant === 'auth' && (
                        <div className="hidden text-right text-sm text-white/72 md:block">
                            <p className="font-medium">{authTitle}</p>
                            <p>{authDescription}</p>
                        </div>
                    )}
                </nav>
            </div>
        </header>
    );
}
