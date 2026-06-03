import { useUiTheme } from '@/hooks/use-ui-theme';

export default function AppLogo() {
    const { orgName, theme } = useUiTheme();

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground overflow-hidden">
                {theme.logoUrl ? (
                    <img src={theme.logoUrl} alt={orgName} className="h-full w-full object-contain p-1" />
                ) : (
                    <span className="text-xs font-black tracking-tight uppercase">{theme.orgInitial ?? 'TAM'}</span>
                )}
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {orgName}
                </span>
            </div>
        </>
    );
}
