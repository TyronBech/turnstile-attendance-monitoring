import { useEffect } from 'react';
import { useAppearance } from '@/hooks/use-appearance';
import { useUiTheme } from '@/hooks/use-ui-theme';

type AppThemeProviderProps = {
    children: React.ReactNode;
};

export function AppThemeProvider({ children }: AppThemeProviderProps) {
    const { colors, palette } = useUiTheme();
    const { resolvedAppearance } = useAppearance();

    useEffect(() => {
        const root = document.documentElement;

        if (resolvedAppearance === 'dark') {
            const primary300 = palette.primary['300'] || colors.primary;
            const primary900 = palette.primary['900'] || '0 0 0';
            const secondary800 = palette.secondary['800'] || colors.secondary;
            const secondary100 = palette.secondary['100'] || '255 255 255';

            root.style.setProperty('--primary', primary300.includes(' ') ? `rgb(${primary300})` : primary300);
            root.style.setProperty('--primary-foreground', primary900.includes(' ') ? `rgb(${primary900})` : primary900);
            root.style.setProperty('--secondary', secondary800.includes(' ') ? `rgb(${secondary800})` : secondary800);
            root.style.setProperty('--secondary-foreground', secondary100.includes(' ') ? `rgb(${secondary100})` : secondary100);

            root.style.setProperty('--ring', primary300.includes(' ') ? `rgb(${primary300})` : primary300);
            root.style.setProperty('--sidebar-primary', primary300.includes(' ') ? `rgb(${primary300})` : primary300);
            root.style.setProperty('--sidebar-primary-foreground', primary900.includes(' ') ? `rgb(${primary900})` : primary900);
            root.style.setProperty('--sidebar-accent', secondary800.includes(' ') ? `rgb(${secondary800})` : secondary800);
            root.style.setProperty('--sidebar-accent-foreground', secondary100.includes(' ') ? `rgb(${secondary100})` : secondary100);
        } else {
            const primary500 = palette.primary['500'] || colors.primary;
            const primary50 = palette.primary['50'] || '255 255 255';
            const secondary500 = palette.secondary['500'] || colors.secondary;
            const secondary900 = palette.secondary['900'] || '0 0 0';

            root.style.setProperty('--primary', primary500.includes(' ') ? `rgb(${primary500})` : primary500);
            root.style.setProperty('--primary-foreground', primary50.includes(' ') ? `rgb(${primary50})` : '#ffffff');
            root.style.setProperty('--secondary', secondary500.includes(' ') ? `rgb(${secondary500})` : secondary500);
            root.style.setProperty('--secondary-foreground', secondary900.includes(' ') ? `rgb(${secondary900})` : secondary900);

            root.style.setProperty('--ring', primary500.includes(' ') ? `rgb(${primary500})` : primary500);
            root.style.setProperty('--sidebar-primary', primary500.includes(' ') ? `rgb(${primary500})` : primary500);
            root.style.setProperty('--sidebar-primary-foreground', primary50.includes(' ') ? `rgb(${primary50})` : '#ffffff');
            root.style.setProperty('--sidebar-accent', secondary500.includes(' ') ? `rgb(${secondary500})` : secondary500);
            root.style.setProperty('--sidebar-accent-foreground', primary500.includes(' ') ? `rgb(${primary500})` : primary500);
        }
    }, [colors, palette, resolvedAppearance]);

    return <>{children}</>;
}
