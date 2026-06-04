import { useCallback, useRef, useState } from 'react';
import { FileSpreadsheet, Loader2, Upload } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

type ImportUploadCardProps = {
    title: string;
    description: string;
    storeUrl: string;
    templateUrl: string;
    onImportStarted: (importLogId: number) => void;
};

const ACCEPTED_TYPES = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'text/csv',
];
const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB

export function ImportUploadCard({
    title,
    description,
    storeUrl,
    templateUrl,
    onImportStarted,
}: ImportUploadCardProps) {
    const [isDragging, setIsDragging] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement | null>(null);

    const validateFile = (file: File): string | null => {
        if (
            !ACCEPTED_TYPES.includes(file.type) &&
            !file.name.match(/\.(xlsx|xls|csv)$/i)
        ) {
            return 'Invalid file format. Please use .xlsx, .xls, or .csv files.';
        }

        if (file.size > MAX_FILE_SIZE_BYTES) {
            return 'File is too large. Maximum allowed size is 10 MB.';
        }

        return null;
    };

    const uploadFile = useCallback(
        async (file: File) => {
            const validationError = validateFile(file);

            if (validationError !== null) {
                setError(validationError);

                return;
            }

            setError(null);
            setIsUploading(true);

            try {
                const formData = new FormData();
                formData.append('file', file);

                const csrfToken =
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '';

                const response = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const errorData = (await response.json()) as {
                        message?: string;
                        errors?: Record<string, string[]>;
                    };
                    const message =
                        errorData.errors?.file?.[0] ??
                        errorData.message ??
                        'Upload failed. Please try again.';
                    setError(message);

                    return;
                }

                const data = (await response.json()) as {
                    import_log_id: number;
                };
                onImportStarted(data.import_log_id);
            } catch {
                setError(
                    'An unexpected error occurred during upload. Please try again.',
                );
            } finally {
                setIsUploading(false);

                if (fileInputRef.current !== null) {
                    fileInputRef.current.value = '';
                }
            }
        },
        [storeUrl, onImportStarted],
    );

    const handleDragOver = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(true);
    };

    const handleDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(false);
    };

    const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(false);

        const file = e.dataTransfer.files[0];

        if (file !== undefined) {
            void uploadFile(file);
        }
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];

        if (file !== undefined) {
            void uploadFile(file);
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Upload className="h-5 w-5" />
                    {title}
                </CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {/* Download Template */}
                <div className="flex items-center justify-between rounded-lg border border-dashed p-4">
                    <div className="flex items-center gap-3">
                        <FileSpreadsheet className="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
                        <div>
                            <p className="text-sm font-medium">
                                Download Import Template
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Excel template with instructions and sample data
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <a href={templateUrl} download>
                            Download
                        </a>
                    </Button>
                </div>

                {/* Upload Zone */}
                <div
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                    onClick={() => fileInputRef.current?.click()}
                    className={cn(
                        'flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 text-center transition-colors',
                        isDragging
                            ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20'
                            : 'border-muted-foreground/25 hover:border-muted-foreground/50 hover:bg-accent/50',
                        isUploading && 'pointer-events-none opacity-60',
                    )}
                >
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        onChange={handleFileChange}
                        className="hidden"
                        disabled={isUploading}
                    />

                    {isUploading ? (
                        <>
                            <Loader2 className="mb-3 h-10 w-10 animate-spin text-blue-500" />
                            <p className="text-sm font-medium">
                                Uploading file...
                            </p>
                        </>
                    ) : (
                        <>
                            <Upload
                                className={cn(
                                    'mb-3 h-10 w-10',
                                    isDragging
                                        ? 'text-blue-500'
                                        : 'text-muted-foreground',
                                )}
                            />
                            <p className="text-sm font-medium">
                                {isDragging
                                    ? 'Drop your file here'
                                    : 'Drag and drop your file here, or click to browse'}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Supports .xlsx, .xls, .csv (max 10 MB)
                            </p>
                        </>
                    )}
                </div>

                {/* Error Display */}
                {error !== null && (
                    <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300">
                        {error}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
