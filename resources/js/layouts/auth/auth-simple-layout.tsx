import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { useUiTheme } from '@/hooks/use-ui-theme';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { theme, palette, rgb, orgInitial, orgName } = useUiTheme();
    const primary700 = rgb(palette.primary['700']);

    return (
        <div
            className="flex min-h-svh flex-col"
            style={{
                background: `linear-gradient(180deg, ${rgb(palette.secondary['50'])} 0%, ${rgb(palette.primary['50'])} 100%)`,
            }}
        >
            <header
                className="border-b px-6 py-5 text-white md:px-10"
                style={{
                    backgroundColor: primary700,
                    borderColor: rgb(palette.primary['600']),
                }}
            >
                <div className="mx-auto flex w-full max-w-5xl items-center justify-between gap-4">
                    <Link
                        href={home()}
                        className="flex items-center gap-3 font-medium"
                    >
                        <div
                            className="flex h-11 w-11 items-center justify-center rounded-2xl border"
                            style={{
                                backgroundColor: rgb(palette.primary['500'], 0.22),
                                borderColor: rgb(palette.primary['500'], 0.45),
                            }}
                        >
                            {theme.logoUrl ? (
                                <img
                                    src={theme.logoUrl}
                                    alt={orgName}
                                    className="h-7 w-7 object-contain"
                                />
                            ) : (
                                <AppLogoIcon className="size-7 fill-current text-white" />
                            )}
                        </div>
                        <div>
                            <p className="text-sm/4 font-semibold tracking-[0.22em] uppercase text-white/70">
                                {orgInitial}
                            </p>
                            <p className="text-sm font-medium text-white">
                                {orgName}
                            </p>
                        </div>
                    </Link>
                    <div className="hidden text-right text-sm text-white/72 md:block">
                        <p className="font-medium">{title}</p>
                        <p>{description}</p>
                    </div>
                </div>
            </header>

            <main className="flex flex-1 items-center justify-center px-6 py-10 md:px-10 md:py-14">
                <div className="w-full max-w-md">
                    <div
                        className="overflow-hidden rounded-[2rem] border bg-white/95 shadow-[0_28px_80px_rgba(15,23,42,0.14)] backdrop-blur"
                        style={{ borderColor: rgb(palette.primary['200'], 0.75) }}
                    >
                        <div
                            className="px-8 py-7 text-white"
                            style={{
                                background: `linear-gradient(135deg, ${primary700} 0%, ${rgb(palette.primary['600'])} 100%)`,
                            }}
                        >
                            <div className="flex items-center gap-3">
                                <div
                                    className="flex h-12 w-12 items-center justify-center rounded-2xl border"
                                    style={{
                                        backgroundColor: rgb(palette.primary['500'], 0.18),
                                        borderColor: rgb(palette.primary['400'], 0.28),
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
                                    <p className="text-xs font-semibold tracking-[0.28em] uppercase text-white/70">
                                        Secure Access
                                    </p>
                                    <h1 className="text-xl font-semibold text-white">
                                        {title}
                                    </h1>
                                </div>
                            </div>
                            <p className="mt-4 text-sm leading-6 text-white/78">
                                {description}
                            </p>
                        </div>

                        <div className="px-8 py-8">
                            {children}
                        </div>
                    </div>
                </div>
            </main>

            <footer
                className="border-t px-6 py-4 text-white md:px-10"
                style={{
                    backgroundColor: primary700,
                    borderColor: rgb(palette.primary['600']),
                }}
            >
                <div className="mx-auto flex w-full max-w-5xl flex-col gap-1 text-sm md:flex-row md:items-center md:justify-between">
                    <p className="font-medium text-white">{orgName}</p>
                    <p className="text-white/72">Attendance system.</p>
                </div>
            </footer>
        </div>
    );
}
