<?php

namespace App\Models;

use App\Helpers\ColorHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class UiSetting extends Model
{
    use SoftDeletes;

    public const DEFAULT_THEME_COLORS = [
        'primary' => '#20246b',
        'secondary' => '#ebf5ff',
        'tertiary' => '#ffcf01',
    ];

    private const ALLOWED_IMAGE_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'image/bmp',
        'image/x-icon',
        'image/vnd.microsoft.icon',
        'image/svg+xml',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ui_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'org_name',
        'org_initial',
        'org_address',
        'org_logo',
        'org_logo_full',
        'email',
        'contact_number',
        'social_links',
        'theme_colors',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'theme_colors' => 'array',
            'social_links' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public static function primary(): ?self
    {
        if (! Schema::hasTable('ui_settings')) {
            return null;
        }

        /** @var self|null $uiSetting */
        $uiSetting = self::query()->first();

        return $uiSetting;
    }

    /**
     * Resolve app-wide UI settings payload for the frontend.
     *
     * @return array{
     *     orgName: string|null,
     *     orgInitial: string|null,
     *     orgAddress: string|null,
     *     email: string|null,
     *     contactNumber: string|null,
     *     logoUrl: string|null,
     *     socialLinks: array<mixed>,
     *     themeColors: array{primary: string, secondary: string, tertiary: string},
     *     themePalette: array<string, array<int, string>>
     * }
     */
    public static function frontendTheme(): array
    {
        $uiSetting = rescue(static fn (): ?self => self::primary(), null, report: false);

        $themeColors = array_merge(
            self::DEFAULT_THEME_COLORS,
            array_filter((array) ($uiSetting?->theme_colors ?? []), static fn (mixed $value): bool => filled($value)),
        );
        $themeColors = self::normalizeThemeColors($themeColors);

        return [
            'orgName' => $uiSetting?->org_name,
            'orgInitial' => $uiSetting?->org_initial,
            'orgAddress' => $uiSetting?->org_address,
            'email' => $uiSetting?->email,
            'contactNumber' => $uiSetting?->contact_number,
            'logoUrl' => $uiSetting?->getLogoDataUrl(),
            'socialLinks' => (array) ($uiSetting?->social_links ?? []),
            'themeColors' => $themeColors,
            'themePalette' => [
                'primary' => ColorHelper::generatePalette($themeColors['primary']),
                'secondary' => ColorHelper::generatePalette($themeColors['secondary']),
                'tertiary' => ColorHelper::generatePalette($themeColors['tertiary']),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $themeColors
     * @return array{primary: string, secondary: string, tertiary: string}
     */
    private static function normalizeThemeColors(array $themeColors): array
    {
        return [
            'primary' => self::normalizeHexColor($themeColors['primary'] ?? null, self::DEFAULT_THEME_COLORS['primary']),
            'secondary' => self::normalizeHexColor($themeColors['secondary'] ?? null, self::DEFAULT_THEME_COLORS['secondary']),
            'tertiary' => self::normalizeHexColor($themeColors['tertiary'] ?? null, self::DEFAULT_THEME_COLORS['tertiary']),
        ];
    }

    private static function normalizeHexColor(mixed $value, string $fallback): string
    {
        if (! is_string($value)) {
            return strtolower($fallback);
        }

        $value = trim($value);

        if (! preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return strtolower($fallback);
        }

        if (strlen($value) === 4) {
            return sprintf(
                '#%1$s%1$s%2$s%2$s%3$s%3$s',
                strtolower($value[1]),
                strtolower($value[2]),
                strtolower($value[3]),
            );
        }

        return strtolower($value);
    }

    public function getLogoDataUrl(): ?string
    {
        $logoData = $this->normalizeBinaryValue($this->org_logo);

        if ($logoData === null) {
            return null;
        }

        if (str_starts_with($logoData, 'data:image/')) {
            return $logoData;
        }

        $mimeType = $this->detectImageMimeType($logoData);

        if ($mimeType === null) {
            return null;
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($logoData);
    }

    private function normalizeBinaryValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value, -1, 0);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private function detectImageMimeType(string $binary): ?string
    {
        try {
            $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary) ?: null;
        } catch (\Throwable) {
            return null;
        }

        $mimeType = is_string($mimeType) ? strtolower($mimeType) : null;

        return in_array($mimeType, self::ALLOWED_IMAGE_MIME_TYPES, true)
            ? $mimeType
            : null;
    }
}
