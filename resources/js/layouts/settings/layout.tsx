import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useUiTheme } from '@/hooks/use-ui-theme';
import { toUrl } from '@/lib/utils';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { palette, rgb, getReadableText } = useUiTheme();

    return (
        <div
            className="space-y-8 px-4 py-6"
            style={{
                background: `radial-gradient(circle at top left, ${rgb(palette.secondary['100'], 0.72)} 0%, ${rgb(palette.secondary['50'])} 42%, ${rgb(palette.primary['50'])} 100%)`,
            }}
        >
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="flex flex-col gap-6 lg:flex-row lg:gap-8">
                <aside className="w-full lg:max-w-[16rem]">
                    <nav
                        className="flex flex-col space-y-2 rounded-[1.5rem] border p-3 shadow-[0_18px_45px_rgba(15,23,42,0.06)]"
                        aria-label="Settings"
                        style={{
                            backgroundColor: rgb(palette.secondary['50'], 0.9),
                            borderColor: rgb(palette.primary['200'], 0.9),
                        }}
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className="h-11 w-full justify-start rounded-xl px-4 text-sm font-semibold shadow-none transition-all"
                                style={isCurrentOrParentUrl(item.href)
                                    ? {
                                        backgroundColor: rgb(palette.primary['700']),
                                        color: getReadableText(palette.primary['700']),
                                    }
                                    : {
                                        color: rgb(palette.primary['800']),
                                    }}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1">
                    <section
                        className="max-w-3xl space-y-8 rounded-[1.75rem] border p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)] md:p-8"
                        style={{
                            backgroundColor: rgb(palette.secondary['50'], 0.92),
                            borderColor: rgb(palette.primary['200'], 0.9),
                        }}
                    >
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
