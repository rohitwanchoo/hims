<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // Patient Management
            ['permission_code' => 'patient.view', 'permission_name' => 'View Patients', 'module' => 'patient', 'action' => 'view'],
            ['permission_code' => 'patient.create', 'permission_name' => 'Create Patients', 'module' => 'patient', 'action' => 'create'],
            ['permission_code' => 'patient.update', 'permission_name' => 'Update Patients', 'module' => 'patient', 'action' => 'update'],
            ['permission_code' => 'patient.delete', 'permission_name' => 'Delete Patients', 'module' => 'patient', 'action' => 'delete'],

            // Appointments
            ['permission_code' => 'appointment.view', 'permission_name' => 'View Appointments', 'module' => 'appointment', 'action' => 'view'],
            ['permission_code' => 'appointment.create', 'permission_name' => 'Create Appointments', 'module' => 'appointment', 'action' => 'create'],
            ['permission_code' => 'appointment.cancel', 'permission_name' => 'Cancel Appointments', 'module' => 'appointment', 'action' => 'cancel'],
            ['permission_code' => 'appointment.reschedule', 'permission_name' => 'Reschedule Appointments', 'module' => 'appointment', 'action' => 'reschedule'],

            // OPD
            ['permission_code' => 'opd.view', 'permission_name' => 'View OPD Visits', 'module' => 'opd', 'action' => 'view'],
            ['permission_code' => 'opd.create', 'permission_name' => 'Create OPD Visits', 'module' => 'opd', 'action' => 'create'],
            ['permission_code' => 'opd.complete', 'permission_name' => 'Complete OPD Consultation', 'module' => 'opd', 'action' => 'complete'],
            ['permission_code' => 'opd.view_all', 'permission_name' => 'View All OPD Visits', 'module' => 'opd', 'action' => 'view_all'],

            // IPD
            ['permission_code' => 'ipd.view', 'permission_name' => 'View IPD Admissions', 'module' => 'ipd', 'action' => 'view'],
            ['permission_code' => 'ipd.admit', 'permission_name' => 'Admit Patients', 'module' => 'ipd', 'action' => 'admit'],
            ['permission_code' => 'ipd.discharge', 'permission_name' => 'Discharge Patients', 'module' => 'ipd', 'action' => 'discharge'],
            ['permission_code' => 'ipd.transfer_bed', 'permission_name' => 'Transfer Bed', 'module' => 'ipd', 'action' => 'transfer_bed'],
            ['permission_code' => 'ipd.progress_notes', 'permission_name' => 'Add Progress Notes', 'module' => 'ipd', 'action' => 'progress_notes'],

            // Laboratory
            ['permission_code' => 'lab.view_orders', 'permission_name' => 'View Lab Orders', 'module' => 'lab', 'action' => 'view_orders'],
            ['permission_code' => 'lab.create_orders', 'permission_name' => 'Create Lab Orders', 'module' => 'lab', 'action' => 'create_orders'],
            ['permission_code' => 'lab.enter_results', 'permission_name' => 'Enter Lab Results', 'module' => 'lab', 'action' => 'enter_results'],
            ['permission_code' => 'lab.verify_results', 'permission_name' => 'Verify Lab Results', 'module' => 'lab', 'action' => 'verify_results'],

            // Radiology
            ['permission_code' => 'radiology.view_orders', 'permission_name' => 'View Radiology Orders', 'module' => 'radiology', 'action' => 'view_orders'],
            ['permission_code' => 'radiology.create_orders', 'permission_name' => 'Create Radiology Orders', 'module' => 'radiology', 'action' => 'create_orders'],
            ['permission_code' => 'radiology.enter_report', 'permission_name' => 'Enter Radiology Report', 'module' => 'radiology', 'action' => 'enter_report'],
            ['permission_code' => 'radiology.verify_report', 'permission_name' => 'Verify Radiology Report', 'module' => 'radiology', 'action' => 'verify_report'],

            // Pharmacy
            ['permission_code' => 'pharmacy.view', 'permission_name' => 'View Pharmacy', 'module' => 'pharmacy', 'action' => 'view'],
            ['permission_code' => 'pharmacy.dispense', 'permission_name' => 'Dispense Medicines', 'module' => 'pharmacy', 'action' => 'dispense'],
            ['permission_code' => 'pharmacy.manage_stock', 'permission_name' => 'Manage Pharmacy Stock', 'module' => 'pharmacy', 'action' => 'manage_stock'],

            // Billing
            ['permission_code' => 'billing.view', 'permission_name' => 'View Bills', 'module' => 'billing', 'action' => 'view'],
            ['permission_code' => 'billing.create', 'permission_name' => 'Create Bills', 'module' => 'billing', 'action' => 'create'],
            ['permission_code' => 'billing.apply_discount', 'permission_name' => 'Apply Discount', 'module' => 'billing', 'action' => 'apply_discount'],
            ['permission_code' => 'billing.refund', 'permission_name' => 'Process Refunds', 'module' => 'billing', 'action' => 'refund'],
            ['permission_code' => 'billing.collect_payment', 'permission_name' => 'Collect Payments', 'module' => 'billing', 'action' => 'collect_payment'],

            // OT (Operation Theater)
            ['permission_code' => 'ot.view', 'permission_name' => 'View OT Schedule', 'module' => 'ot', 'action' => 'view'],
            ['permission_code' => 'ot.schedule', 'permission_name' => 'Schedule Surgery', 'module' => 'ot', 'action' => 'schedule'],
            ['permission_code' => 'ot.start_procedure', 'permission_name' => 'Start OT Procedure', 'module' => 'ot', 'action' => 'start_procedure'],
            ['permission_code' => 'ot.complete', 'permission_name' => 'Complete OT Procedure', 'module' => 'ot', 'action' => 'complete'],

            // Inventory
            ['permission_code' => 'inventory.view', 'permission_name' => 'View Inventory', 'module' => 'inventory', 'action' => 'view'],
            ['permission_code' => 'inventory.create_indent', 'permission_name' => 'Create Indent', 'module' => 'inventory', 'action' => 'create_indent'],
            ['permission_code' => 'inventory.approve_indent', 'permission_name' => 'Approve Indent', 'module' => 'inventory', 'action' => 'approve_indent'],
            ['permission_code' => 'inventory.create_po', 'permission_name' => 'Create Purchase Order', 'module' => 'inventory', 'action' => 'create_po'],
            ['permission_code' => 'inventory.approve_po', 'permission_name' => 'Approve Purchase Order', 'module' => 'inventory', 'action' => 'approve_po'],
            ['permission_code' => 'inventory.receive_goods', 'permission_name' => 'Receive Goods', 'module' => 'inventory', 'action' => 'receive_goods'],

            // MRD
            ['permission_code' => 'mrd.view_records', 'permission_name' => 'View Medical Records', 'module' => 'mrd', 'action' => 'view_records'],
            ['permission_code' => 'mrd.issue_file', 'permission_name' => 'Issue Patient File', 'module' => 'mrd', 'action' => 'issue_file'],
            ['permission_code' => 'mrd.upload_document', 'permission_name' => 'Upload Documents', 'module' => 'mrd', 'action' => 'upload_document'],
            ['permission_code' => 'mrd.archive', 'permission_name' => 'Archive Records', 'module' => 'mrd', 'action' => 'archive'],
            ['permission_code' => 'mrd.icd_coding', 'permission_name' => 'ICD Coding', 'module' => 'mrd', 'action' => 'icd_coding'],

            // Birth & Death
            ['permission_code' => 'birth.register', 'permission_name' => 'Register Birth', 'module' => 'birth', 'action' => 'register'],
            ['permission_code' => 'birth.issue_certificate', 'permission_name' => 'Issue Birth Certificate', 'module' => 'birth', 'action' => 'issue_certificate'],
            ['permission_code' => 'death.register', 'permission_name' => 'Register Death', 'module' => 'death', 'action' => 'register'],
            ['permission_code' => 'death.issue_certificate', 'permission_name' => 'Issue Death Certificate', 'module' => 'death', 'action' => 'issue_certificate'],

            // ABHA/ABDM
            ['permission_code' => 'abha.link_patient', 'permission_name' => 'Link ABHA to Patient', 'module' => 'abha', 'action' => 'link_patient'],
            ['permission_code' => 'abha.share_records', 'permission_name' => 'Share Health Records', 'module' => 'abha', 'action' => 'share_records'],
            ['permission_code' => 'abha.manage_consent', 'permission_name' => 'Manage ABHA Consents', 'module' => 'abha', 'action' => 'manage_consent'],

            // Admin
            ['permission_code' => 'admin.manage_users', 'permission_name' => 'Manage Users', 'module' => 'admin', 'action' => 'manage_users'],
            ['permission_code' => 'admin.manage_roles', 'permission_name' => 'Manage Roles', 'module' => 'admin', 'action' => 'manage_roles'],
            ['permission_code' => 'admin.view_reports', 'permission_name' => 'View Reports', 'module' => 'admin', 'action' => 'view_reports'],
            ['permission_code' => 'admin.manage_settings', 'permission_name' => 'Manage Settings', 'module' => 'admin', 'action' => 'manage_settings'],
            ['permission_code' => 'admin.manage_masters', 'permission_name' => 'Manage Master Data', 'module' => 'admin', 'action' => 'manage_masters'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['permission_code' => $permission['permission_code']],
                $permission
            );
        }

        // Create System Roles
        $roles = [
            [
                'role_code' => 'super_admin',
                'role_name' => 'Super Administrator',
                'description' => 'Full system access across all hospitals',
                'is_system_role' => true,
                'permissions' => 'all',
            ],
            [
                'role_code' => 'hospital_admin',
                'role_name' => 'Hospital Administrator',
                'description' => 'Full access within their hospital',
                'is_system_role' => true,
                'permissions' => 'all',
            ],
            [
                'role_code' => 'doctor',
                'role_name' => 'Doctor',
                'description' => 'Clinical staff - Doctors',
                'is_system_role' => true,
                'permissions' => [
                    'patient.view', 'patient.update',
                    'appointment.view', 'appointment.create',
                    'opd.view', 'opd.create', 'opd.complete',
                    'ipd.view', 'ipd.admit', 'ipd.discharge', 'ipd.progress_notes',
                    'lab.view_orders', 'lab.create_orders',
                    'radiology.view_orders', 'radiology.create_orders',
                    'ot.view', 'ot.schedule', 'ot.start_procedure', 'ot.complete',
                    'mrd.view_records',
                    'abha.share_records',
                ],
            ],
            [
                'role_code' => 'nurse',
                'role_name' => 'Nurse',
                'description' => 'Nursing staff',
                'is_system_role' => true,
                'permissions' => [
                    'patient.view',
                    'appointment.view',
                    'opd.view',
                    'ipd.view', 'ipd.progress_notes',
                    'lab.view_orders',
                    'mrd.view_records',
                ],
            ],
            [
                'role_code' => 'receptionist',
                'role_name' => 'Receptionist',
                'description' => 'Front desk staff',
                'is_system_role' => true,
                'permissions' => [
                    'patient.view', 'patient.create', 'patient.update',
                    'appointment.view', 'appointment.create', 'appointment.cancel', 'appointment.reschedule',
                    'opd.view', 'opd.create',
                    'billing.view', 'billing.create', 'billing.collect_payment',
                ],
            ],
            [
                'role_code' => 'pharmacist',
                'role_name' => 'Pharmacist',
                'description' => 'Pharmacy staff',
                'is_system_role' => true,
                'permissions' => [
                    'patient.view',
                    'pharmacy.view', 'pharmacy.dispense', 'pharmacy.manage_stock',
                    'billing.view', 'billing.create', 'billing.collect_payment',
                ],
            ],
            [
                'role_code' => 'lab_technician',
                'role_name' => 'Lab Technician',
                'description' => 'Laboratory staff',
                'is_system_role' => true,
                'permissions' => [
                    'patient.view',
                    'lab.view_orders', 'lab.enter_results',
                ],
            ],
            [
                'role_code' => 'radiologist',
                'role_name' => 'Radiologist',
                'description' => 'Radiology department staff',
                'is_system_role' => true,
                'permissions' => [
                    'patient.view',
                    'radiology.view_orders', 'radiology.enter_report', 'radiology.verify_report',
                ],
            ],
            [
                'role_code' => 'billing_staff',
                'role_name' => 'Billing Staff',
                'description' => 'Billing and accounts staff',
                'is_system_role' => true,
                'permissions' => [
                    'patient.view',
                    'billing.view', 'billing.create', 'billing.apply_discount', 'billing.collect_payment',
                ],
            ],
            [
                'role_code' => 'accounts',
                'role_name' => 'Accounts',
                'description' => 'Accounts department',
                'is_system_role' => true,
                'permissions' => [
                    'billing.view', 'billing.refund',
                    'admin.view_reports',
                ],
            ],
            [
                'role_code' => 'store_keeper',
                'role_name' => 'Store Keeper',
                'description' => 'Inventory/Store management',
                'is_system_role' => true,
                'permissions' => [
                    'inventory.view', 'inventory.create_indent', 'inventory.receive_goods',
                ],
            ],
            [
                'role_code' => 'mrd_staff',
                'role_name' => 'MRD Staff',
                'description' => 'Medical Records Department',
                'is_system_role' => true,
                'permissions' => [
                    'patient.view',
                    'mrd.view_records', 'mrd.issue_file', 'mrd.upload_document', 'mrd.archive', 'mrd.icd_coding',
                    'birth.register', 'birth.issue_certificate',
                    'death.register', 'death.issue_certificate',
                ],
            ],
        ];

        $allPermissions = Permission::all()->pluck('permission_id', 'permission_code');

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(
                ['role_code' => $roleData['role_code']],
                $roleData
            );

            if ($permissions === 'all') {
                $role->permissions()->sync($allPermissions->values());
            } else {
                $permissionIds = collect($permissions)
                    ->map(fn ($code) => $allPermissions[$code] ?? null)
                    ->filter()
                    ->values();
                $role->permissions()->sync($permissionIds);
            }
        }

        $this->command->info('Roles and Permissions seeded successfully!');
    }
}
