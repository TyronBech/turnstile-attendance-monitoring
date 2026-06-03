import { usePage } from '@inertiajs/react';

type PaletteShade = '50' | '100' | '200' | '300' | '400' | '500' | '600' | '700' | '800' | '900';

type ThemePalette = Record<PaletteShade, string>;

type ThemeColors = {
    primary: string;
    secondary: string;
    tertiary: string;
};

export type UiSettingsTheme = {
    orgName: string | null;
    orgInitial: string | null;
    orgAddress: string | null;
    email: string | null;
    contactNumber: string | null;
    logoUrl: string | null;
    socialLinks: Record<string, string>;
    themeColors: ThemeColors;
    themePalette: {
        primary: ThemePalette;
        secondary: ThemePalette;
        tertiary: ThemePalette;
    };
};

const defaultTheme: UiSettingsTheme = {
    orgName: null,
    orgInitial: null,
    orgAddress: null,
    email: null,
    contactNumber: null,
    logoUrl: null,
    socialLinks: {},
    themeColors: {
        primary: '#20246b',
        secondary: '#ebf5ff',
        tertiary: '#ffcf01',
    },
    themePalette: {
        primary: {
            '50': '244 244 251',
            '100': '225 225 241',
            '200': '187 188 221',
            '300': '148 150 202',
            '400': '90 94 154',
            '500': '32 36 107',
            '600': '27 31 91',
            '700': '22 25 75',
            '800': '16 18 54',
            '900': '10 11 32',
        },
        secondary: {
            '50': '253 254 255',
            '100': '251 253 255',
            '200': '247 250 255',
            '300': '243 248 255',
            '400': '239 246 255',
            '500': '235 245 255',
            '600': '200 208 217',
            '700': '165 172 179',
            '800': '118 123 128',
            '900': '71 74 77',
        },
        tertiary: {
            '50': '255 251 230',
            '100': '255 247 204',
            '200': '255 239 153',
            '300': '255 231 102',
            '400': '255 219 52',
            '500': '255 207 1',
            '600': '217 176 1',
            '700': '179 145 1',
            '800': '128 104 1',
            '900': '77 62 0',
        },
    },
};

const rgb = (channels: string, alpha?: number): string => {
    return alpha === undefined
        ? `rgb(${channels})`
        : `rgb(${channels} / ${alpha})`;
};

export function useUiTheme() {
    const { uiSettings } = usePage().props as { uiSettings?: UiSettingsTheme };
    const theme = uiSettings ?? defaultTheme;

    return {
        theme,
        rgb,
        colors: theme.themeColors,
        palette: theme.themePalette,
        orgName: theme.orgName ?? 'Turnstile Attendance Monitoring',
        orgInitial: theme.orgInitial ?? 'TAM',
    } as const;
}
