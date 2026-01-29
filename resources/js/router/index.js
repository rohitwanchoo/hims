import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Layouts
import MainLayout from '../components/layout/MainLayout.vue';

// Views
import Login from '../views/Login.vue';
import Dashboard from '../views/Dashboard.vue';

// Patients
import PatientList from '../views/patients/PatientList.vue';
import PatientForm from '../views/patients/PatientForm.vue';
import PatientView from '../views/patients/PatientView.vue';

// Doctors
import DoctorList from '../views/doctors/DoctorList.vue';
import DoctorForm from '../views/doctors/DoctorForm.vue';

// Departments
import DepartmentList from '../views/departments/DepartmentList.vue';

// Appointments
import AppointmentList from '../views/appointments/AppointmentList.vue';
import AppointmentForm from '../views/appointments/AppointmentForm.vue';
import CalendarView from '../views/appointments/CalendarView.vue';

// OPD
import OpdList from '../views/opd/OpdList.vue';
import OpdForm from '../views/opd/OpdForm.vue';
import OpdConsultation from '../views/opd/OpdConsultation.vue';

// Doctor Workbench
import DoctorWorkbench from '../views/doctor-workbench/DoctorWorkbench.vue';

// Consultation Forms
import ConsultationFormList from '../views/consultation-forms/ConsultationFormList.vue';
import ConsultationFormBuilder from '../views/consultation-forms/ConsultationFormBuilder.vue';
import ConsultationForm from '../views/consultation-forms/ConsultationForm.vue';

// IPD
import IpdList from '../views/ipd/IpdList.vue';
import IpdForm from '../views/ipd/IpdForm.vue';

// Discharge Summary
import DischargeSummaryList from '../views/discharge-summary/DischargeSummaryList.vue';
import DischargeSummaryForm from '../views/discharge-summary/DischargeSummaryForm.vue';

// Bed Transfer
import BedTransferMaster from '../views/clinical/BedTransferMaster.vue';

// Laboratory
import LabTestList from '../views/laboratory/LabTestList.vue';
import LabOrderList from '../views/laboratory/LabOrderList.vue';
import LabOrderForm from '../views/laboratory/LabOrderForm.vue';

// Pharmacy
import DrugList from '../views/pharmacy/DrugList.vue';
import PharmacySaleList from '../views/pharmacy/PharmacySaleList.vue';

// Billing
import BillList from '../views/billing/BillList.vue';
import BillForm from '../views/billing/BillForm.vue';

// Payments
import PaymentList from '../views/payments/PaymentList.vue';

// Users
import UserList from '../views/users/UserList.vue';

// Reports
import ReportList from '../views/reports/ReportList.vue';

// Hospitals (Super Admin)
import HospitalList from '../views/hospitals/HospitalList.vue';
import HospitalForm from '../views/hospitals/HospitalForm.vue';

// Settings
import Settings from '../views/settings/Settings.vue';
import OpdConfiguration from '../views/settings/OpdConfiguration.vue';
import RateChangeRequests from '../views/settings/RateChangeRequests.vue';
import OpdTimeSlots from '../views/settings/OpdTimeSlots.vue';

// Admin Tools
import ClaudeChat from '../views/admin/ClaudeChat.vue';

// Radiology
import RadiologyOrderList from '../views/radiology/RadiologyOrderList.vue';
import RadiologyOrderForm from '../views/radiology/RadiologyOrderForm.vue';
import RadiologyWorklist from '../views/radiology/RadiologyWorklist.vue';

// Operation Theater
import OtScheduleList from '../views/ot/OtScheduleList.vue';
import OtScheduleForm from '../views/ot/OtScheduleForm.vue';

// Inventory
import InventoryDashboard from '../views/inventory/InventoryDashboard.vue';
import ItemList from '../views/inventory/ItemList.vue';
import StoreList from '../views/inventory/StoreList.vue';
import IndentList from '../views/inventory/IndentList.vue';
import IndentForm from '../views/inventory/IndentForm.vue';
import PurchaseOrderList from '../views/inventory/PurchaseOrderList.vue';
import PurchaseOrderForm from '../views/inventory/PurchaseOrderForm.vue';

// Birth & Death Registration
import BirthRegistrationList from '../views/birth-death/BirthRegistrationList.vue';
import BirthRegistrationForm from '../views/birth-death/BirthRegistrationForm.vue';
import DeathRegistrationList from '../views/birth-death/DeathRegistrationList.vue';
import DeathRegistrationForm from '../views/birth-death/DeathRegistrationForm.vue';

// Medical Records Department
import MrdDashboard from '../views/mrd/MrdDashboard.vue';
import FileTracking from '../views/mrd/FileTracking.vue';
import RecordRequests from '../views/mrd/RecordRequests.vue';
import IcdCoding from '../views/mrd/IcdCoding.vue';
import MrdDocuments from '../views/mrd/Documents.vue';
import PatientRecords from '../views/mrd/PatientRecords.vue';
import Consents from '../views/mrd/Consents.vue';

// RBAC
import RoleList from '../views/rbac/RoleList.vue';

// Notifications
import NotificationSettings from '../views/notifications/NotificationSettings.vue';

// ABHA / ABDM
import AbhaManagement from '../views/abha/AbhaManagement.vue';

// Masters - Reception
import PrefixList from '../views/masters/reception/PrefixList.vue';
import GenderList from '../views/masters/reception/GenderList.vue';
import BloodGroupList from '../views/masters/reception/BloodGroupList.vue';
import PatientTypeList from '../views/masters/reception/PatientTypeList.vue';
import MaritalStatusList from '../views/masters/reception/MaritalStatusList.vue';
import ReferenceDoctorList from '../views/masters/reception/ReferenceDoctorList.vue';
import InsuranceCompanyList from '../views/masters/reception/InsuranceCompanyList.vue';
import QualificationList from '../views/masters/reception/QualificationList.vue';
import ConsultMasterList from '../views/masters/reception/ConsultMasterList.vue';

// Masters - Prescription
import PrescriptionMaster from '../views/masters/prescription/PrescriptionMaster.vue';

// Masters - Bed Allocation
import BedAllocationMaster from '../views/masters/BedAllocationMaster.vue';

// Masters - Hospital Services
import HospitalServicesMaster from '../views/masters/HospitalServicesMaster.vue';

// Masters - GST Plan
import GstPlanMaster from '../views/masters/GstPlanMaster.vue';

// Masters - Address
import CountryList from '../views/masters/address/CountryList.vue';
import StateList from '../views/masters/address/StateList.vue';
import DistrictList from '../views/masters/address/DistrictList.vue';
import CityList from '../views/masters/address/CityList.vue';
import AreaList from '../views/masters/address/AreaList.vue';

// Prescription
import PrescriptionPrint from '../views/prescription/PrescriptionPrint.vue';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { guest: true }
    },
    {
        path: '/prescription/:id/print',
        name: 'prescription.print',
        component: PrescriptionPrint,
        meta: { requiresAuth: true }
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: Dashboard
            },
            // Masters - Reception
            {
                path: 'masters/reception/prefix',
                name: 'masters.reception.prefix',
                component: PrefixList
            },
            {
                path: 'masters/reception/gender',
                name: 'masters.reception.gender',
                component: GenderList
            },
            {
                path: 'masters/reception/blood-group',
                name: 'masters.reception.blood-group',
                component: BloodGroupList
            },
            {
                path: 'masters/reception/patient-type',
                name: 'masters.reception.patient-type',
                component: PatientTypeList
            },
            {
                path: 'masters/reception/marital-status',
                name: 'masters.reception.marital-status',
                component: MaritalStatusList
            },
            {
                path: 'masters/reception/reference-doctor',
                name: 'masters.reception.reference-doctor',
                component: ReferenceDoctorList
            },
            {
                path: 'masters/reception/insurance-company',
                name: 'masters.reception.insurance-company',
                component: InsuranceCompanyList
            },
            {
                path: 'masters/reception/qualification',
                name: 'masters.reception.qualification',
                component: QualificationList
            },
            {
                path: 'masters/reception/consult-master',
                name: 'masters.reception.consult-master',
                component: ConsultMasterList
            },
            {
                path: 'masters/prescription',
                name: 'masters.prescription',
                component: PrescriptionMaster
            },
            {
                path: 'masters/bed-allocation',
                name: 'masters.bed-allocation',
                component: BedAllocationMaster
            },
            {
                path: 'masters/hospital-services',
                name: 'masters.hospital-services',
                component: HospitalServicesMaster
            },
            {
                path: 'masters/gst-plan',
                name: 'masters.gst-plan',
                component: GstPlanMaster
            },
            // Masters - Address
            {
                path: 'masters/address/country',
                name: 'masters.address.country',
                component: CountryList
            },
            {
                path: 'masters/address/state',
                name: 'masters.address.state',
                component: StateList
            },
            {
                path: 'masters/address/district',
                name: 'masters.address.district',
                component: DistrictList
            },
            {
                path: 'masters/address/city',
                name: 'masters.address.city',
                component: CityList
            },
            {
                path: 'masters/address/area',
                name: 'masters.address.area',
                component: AreaList
            },
            // Patients
            {
                path: 'patients',
                name: 'patients',
                component: PatientList
            },
            {
                path: 'patients/create',
                name: 'patients.create',
                component: PatientForm
            },
            {
                path: 'patients/:id',
                name: 'patients.view',
                component: PatientView
            },
            {
                path: 'patients/:id/edit',
                name: 'patients.edit',
                component: PatientForm
            },
            // Doctors
            {
                path: 'doctors',
                name: 'doctors',
                component: DoctorList
            },
            {
                path: 'doctors/create',
                name: 'doctors.create',
                component: DoctorForm
            },
            {
                path: 'doctors/:id/edit',
                name: 'doctors.edit',
                component: DoctorForm
            },
            // Departments
            {
                path: 'departments',
                name: 'departments',
                component: DepartmentList
            },
            // Appointments
            {
                path: 'appointments',
                name: 'appointments',
                component: AppointmentList
            },
            {
                path: 'appointments/create',
                name: 'appointments.create',
                component: AppointmentForm
            },
            {
                path: 'appointments/:id',
                name: 'appointments.view',
                component: AppointmentForm
            },
            {
                path: 'calendar',
                name: 'calendar',
                component: CalendarView
            },
            // OPD
            {
                path: 'opd',
                name: 'opd',
                component: OpdList
            },
            {
                path: 'opd/create',
                name: 'opd.create',
                component: OpdForm
            },
            {
                path: 'opd/:id',
                name: 'opd.view',
                component: OpdForm
            },
            {
                path: 'opd/:id/consultation',
                name: 'opd.consultation',
                component: OpdConsultation
            },
            // Doctor Workbench
            {
                path: 'doctor-workbench',
                name: 'doctor-workbench',
                component: DoctorWorkbench
            },
            // Consultation Forms
            {
                path: 'consultation-forms',
                name: 'consultation-forms',
                component: ConsultationFormList
            },
            {
                path: 'consultation-forms/create',
                name: 'consultation-forms.create',
                component: ConsultationFormBuilder
            },
            {
                path: 'consultation-forms/:formId/edit',
                name: 'consultation-forms.edit',
                component: ConsultationFormBuilder
            },
            {
                path: 'consultation/:opdId',
                name: 'consultation.fill',
                component: ConsultationForm
            },
            {
                path: 'consultation/:opdId/:recordId',
                name: 'consultation.edit',
                component: ConsultationForm
            },
            // IPD
            {
                path: 'ipd',
                name: 'ipd',
                component: IpdList
            },
            {
                path: 'ipd/create',
                name: 'ipd.create',
                component: IpdForm
            },
            {
                path: 'ipd/:id',
                name: 'ipd.view',
                component: IpdForm
            },
            {
                path: 'discharge-summary',
                name: 'discharge-summary',
                component: DischargeSummaryList
            },
            {
                path: 'discharge-summary/create',
                name: 'discharge-summary.create',
                component: DischargeSummaryForm
            },
            {
                path: 'discharge-summary/:id',
                name: 'discharge-summary.view',
                component: DischargeSummaryForm
            },
            {
                path: 'bed-transfers',
                name: 'bed-transfers',
                component: BedTransferMaster
            },
            // Laboratory
            {
                path: 'laboratory/tests',
                name: 'lab.tests',
                component: LabTestList
            },
            {
                path: 'laboratory/orders',
                name: 'lab.orders',
                component: LabOrderList
            },
            {
                path: 'laboratory/orders/create',
                name: 'lab.orders.create',
                component: LabOrderForm
            },
            {
                path: 'laboratory/orders/:id/edit',
                name: 'lab.orders.edit',
                component: LabOrderForm
            },
            // Radiology
            {
                path: 'radiology/orders',
                name: 'radiology.orders',
                component: RadiologyOrderList
            },
            {
                path: 'radiology/orders/create',
                name: 'radiology.orders.create',
                component: RadiologyOrderForm
            },
            {
                path: 'radiology/orders/:id',
                name: 'radiology.orders.view',
                component: RadiologyOrderForm
            },
            {
                path: 'radiology/orders/:id/edit',
                name: 'radiology.orders.edit',
                component: RadiologyOrderForm
            },
            {
                path: 'radiology/worklist',
                name: 'radiology.worklist',
                component: RadiologyWorklist
            },
            // Operation Theater
            {
                path: 'ot/schedules',
                name: 'ot.schedules',
                component: OtScheduleList
            },
            {
                path: 'ot/schedules/create',
                name: 'ot.schedules.create',
                component: OtScheduleForm
            },
            {
                path: 'ot/schedules/:id',
                name: 'ot.schedules.view',
                component: OtScheduleForm
            },
            {
                path: 'ot/schedules/:id/edit',
                name: 'ot.schedules.edit',
                component: OtScheduleForm
            },
            // Pharmacy
            {
                path: 'pharmacy/drugs',
                name: 'pharmacy.drugs',
                component: DrugList
            },
            {
                path: 'pharmacy/sales',
                name: 'pharmacy.sales',
                component: PharmacySaleList
            },
            // Inventory
            {
                path: 'inventory',
                name: 'inventory',
                component: InventoryDashboard
            },
            {
                path: 'inventory/items',
                name: 'inventory.items',
                component: ItemList
            },
            {
                path: 'inventory/stores',
                name: 'inventory.stores',
                component: StoreList
            },
            {
                path: 'inventory/indents',
                name: 'inventory.indents',
                component: IndentList
            },
            {
                path: 'inventory/indents/create',
                name: 'inventory.indents.create',
                component: IndentForm
            },
            {
                path: 'inventory/indents/:id',
                name: 'inventory.indents.view',
                component: IndentForm
            },
            {
                path: 'inventory/purchase-orders',
                name: 'inventory.purchase-orders',
                component: PurchaseOrderList
            },
            {
                path: 'inventory/purchase-orders/create',
                name: 'inventory.purchase-orders.create',
                component: PurchaseOrderForm
            },
            {
                path: 'inventory/purchase-orders/:id',
                name: 'inventory.purchase-orders.view',
                component: PurchaseOrderForm
            },
            // Billing
            {
                path: 'billing',
                name: 'billing',
                component: BillList
            },
            {
                path: 'billing/create',
                name: 'billing.create',
                component: BillForm
            },
            {
                path: 'billing/:id',
                name: 'billing.view',
                component: BillForm
            },
            // Payments
            {
                path: 'payments',
                name: 'payments',
                component: PaymentList
            },
            // Users
            {
                path: 'users',
                name: 'users',
                component: UserList
            },
            // Reports
            {
                path: 'reports',
                name: 'reports',
                component: ReportList
            },
            // Medical Records Department
            {
                path: 'mrd',
                name: 'mrd',
                component: MrdDashboard
            },
            {
                path: 'mrd/file-tracking',
                name: 'mrd.file-tracking',
                component: FileTracking
            },
            {
                path: 'mrd/record-requests',
                name: 'mrd.record-requests',
                component: RecordRequests
            },
            {
                path: 'mrd/icd-coding',
                name: 'mrd.icd-coding',
                component: IcdCoding
            },
            {
                path: 'mrd/documents',
                name: 'mrd.documents',
                component: MrdDocuments
            },
            {
                path: 'mrd/patients/:id/records',
                name: 'mrd.patient-records',
                component: PatientRecords
            },
            {
                path: 'mrd/consents',
                name: 'mrd.consents',
                component: Consents
            },
            // Birth & Death Registration
            {
                path: 'birth-registrations',
                name: 'birth-registrations',
                component: BirthRegistrationList
            },
            {
                path: 'birth-registrations/create',
                name: 'birth-registrations.create',
                component: BirthRegistrationForm
            },
            {
                path: 'birth-registrations/:id/edit',
                name: 'birth-registrations.edit',
                component: BirthRegistrationForm
            },
            {
                path: 'death-registrations',
                name: 'death-registrations',
                component: DeathRegistrationList
            },
            {
                path: 'death-registrations/create',
                name: 'death-registrations.create',
                component: DeathRegistrationForm
            },
            {
                path: 'death-registrations/:id/edit',
                name: 'death-registrations.edit',
                component: DeathRegistrationForm
            },
            // ABHA / ABDM
            {
                path: 'abha',
                name: 'abha',
                component: AbhaManagement
            },
            // RBAC
            {
                path: 'roles',
                name: 'roles',
                component: RoleList
            },
            // Notifications
            {
                path: 'notifications/settings',
                name: 'notifications.settings',
                component: NotificationSettings
            },
            // Settings
            {
                path: 'settings',
                name: 'settings',
                component: Settings
            },
            {
                path: 'settings/opd-configuration',
                name: 'settings.opd-configuration',
                component: OpdConfiguration
            },
            {
                path: 'settings/rate-requests',
                name: 'settings.rate-requests',
                component: RateChangeRequests
            },
            {
                path: 'settings/opd-time-slots',
                name: 'settings.opd-time-slots',
                component: OpdTimeSlots
            },
            // Hospitals (Super Admin)
            {
                path: 'hospitals',
                name: 'hospitals',
                component: HospitalList,
                meta: { superAdmin: true }
            },
            {
                path: 'hospitals/create',
                name: 'hospitals.create',
                component: HospitalForm,
                meta: { superAdmin: true }
            },
            {
                path: 'hospitals/:id/edit',
                name: 'hospitals.edit',
                component: HospitalForm,
                meta: { superAdmin: true }
            },
            // Admin Tools (Super Admin)
            {
                path: 'admin/claude-chat',
                name: 'admin.claude-chat',
                component: ClaudeChat,
                meta: { superAdmin: true }
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();

    // Initialize auth headers from localStorage on every navigation
    authStore.initializeAuth();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' });
    } else if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
