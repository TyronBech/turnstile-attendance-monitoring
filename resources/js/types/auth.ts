export type User = {
    id: number;
    student_id: string;
    rfid: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    name: string;
    email: string;
    guardian_name: string;
    guardian_contact_number: string;
    status: boolean;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type StudentDetail = {
    id: number;
    id_number: string;
    level: string;
    section: string;
    guardian_name: string;
    guardian_contact_number: string;
};

export type EmployeeDetail = {
    id: number;
    employee_id: string;
    employee_role: string | null;
};

export type UserWithDetails = User & {
    student_detail?: StudentDetail | null;
    employee_detail?: EmployeeDetail | null;
};

export type PaginatedData<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type UserTab = 'students' | 'employees';

export type UserMaintenancePageProps = {
    users: PaginatedData<UserWithDetails>;
    tab: UserTab;
    search: string;
    perPage: number;
};

export type Auth = {
    user: User;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
