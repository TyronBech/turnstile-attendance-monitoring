export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

const applyLightTheme = (): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';
};

export function initializeTheme(): void {
    applyLightTheme();
}

export function useAppearance(): UseAppearanceReturn {
    const updateAppearance = (): void => {
        applyLightTheme();
    };

    return {
        appearance: 'light',
        resolvedAppearance: 'light',
        updateAppearance,
    } as const;
}
