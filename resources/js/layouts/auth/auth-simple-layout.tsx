import AppLogoIcon from '@/components/app-logo-icon';
import { OrgFooter } from '@/components/org-footer';
import { OrgHeader } from '@/components/org-header';
import { useUiTheme } from '@/hooks/use-ui-theme';
import type { AuthLayoutProps } from '@/types';
import type { CSSProperties } from 'react';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { theme, palette, rgb, orgName } = useUiTheme();
    const primary700 = rgb(palette.primary['700']);
    const panelForeground = rgb(palette.primary['900']);
    const panelMutedForeground = rgb(palette.secondary['900']);
    const panelThemeVariables = {
        color: panelForeground,
        '--foreground': panelForeground,
        '--card-foreground': panelForeground,
        '--popover-foreground': panelForeground,
        '--muted-foreground': panelMutedForeground,
    } as CSSProperties;

    return (
        <div
            className="flex min-h-svh flex-col"
            style={{
                background: `linear-gradient(180deg, ${rgb(palette.secondary['50'])} 0%, ${rgb(palette.primary['50'])} 100%)`,
            }}
        >
            <OrgHeader variant="auth" maxWidth="max-w-5xl" authTitle={title} authDescription={description} />

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

                        <div
                            className="px-8 py-8"
                            style={panelThemeVariables}
                        >
                            {children}
                        </div>
                    </div>
                </div>
            </main>

            <OrgFooter maxWidth="max-w-5xl" />
        </div>
    );
}
