<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RadiologyModalityController;
use App\Http\Controllers\Api\RadiologyTestController;
use App\Http\Controllers\Api\RadiologyOrderController;
use App\Http\Controllers\Api\RadiologyReportController;
use App\Http\Controllers\Api\OperationTheaterController;
use App\Http\Controllers\Api\SurgeryTypeController;
use App\Http\Controllers\Api\OtScheduleController;
use App\Http\Controllers\Api\OtProcedureController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\IndentController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\BirthRegistrationController;
use App\Http\Controllers\Api\DeathRegistrationController;
use App\Http\Controllers\Api\MrdController;
use App\Http\Controllers\Api\PatientPortalController;
use App\Http\Controllers\Api\AbhaController;
use App\Http\Controllers\Api\FhirController;
use App\Http\Controllers\Api\ClaudeAssistantController;
use App\Http\Controllers\Api\PrefixController;
use App\Http\Controllers\Api\GenderController;
use App\Http\Controllers\Api\BloodGroupController;
use App\Http\Controllers\Api\PatientTypeController;
use App\Http\Controllers\Api\MaritalStatusController;
use App\Http\Controllers\Api\ReferenceDoctorController;
use App\Http\Controllers\Api\InsuranceCompanyController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\DistrictController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\QualificationController;
use App\Http\Controllers\Api\ConsultMasterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/switch-hospital', [AuthController::class, 'switchHospital']);

    // Super Admin: Hospital Management
    Route::prefix('hospitals')->middleware('super_admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\HospitalController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\HospitalController::class, 'store']);
        Route::get('/stats', [\App\Http\Controllers\Api\HospitalController::class, 'stats']);
        Route::get('/{hospital}', [\App\Http\Controllers\Api\HospitalController::class, 'show']);
        Route::put('/{hospital}', [\App\Http\Controllers\Api\HospitalController::class, 'update']);
        Route::delete('/{hospital}', [\App\Http\Controllers\Api\HospitalController::class, 'destroy']);
    });

    // Dashboard
    Route::get('/dashboard', function () {
        return response()->json([
            'patients_today' => \App\Models\Patient::whereDate('created_at', today())->count(),
            'opd_visits_today' => \App\Models\OpdVisit::whereDate('visit_date', today())->count(),
            'ipd_admissions_active' => \App\Models\IpdAdmission::where('status', 'admitted')->count(),
            'pending_lab_orders' => \App\Models\LabOrder::where('status', 'pending')->count(),
        ]);
    });

    // Patients
    Route::apiResource('patients', \App\Http\Controllers\Api\PatientController::class);
    Route::get('patients/{patient}/visits', [\App\Http\Controllers\Api\PatientController::class, 'visits']);
    Route::get('patients/{patient}/admissions', [\App\Http\Controllers\Api\PatientController::class, 'admissions']);
    Route::get('patients/{patient}/vaccinations', [\App\Http\Controllers\Api\PatientController::class, 'vaccinations']);
    Route::get('patients/{patient}/history', [\App\Http\Controllers\Api\PatientController::class, 'history']);
    Route::get('patients/{patient}/client-documents', [\App\Http\Controllers\Api\PatientController::class, 'clientDocuments']);
    Route::get('patients/{patient}/documents', [\App\Http\Controllers\Api\PatientController::class, 'documents']);
    Route::post('patients/{patient}/mark-vip', [\App\Http\Controllers\Api\PatientController::class, 'markVip']);
    Route::post('patients/{patient}/mark-urgent', [\App\Http\Controllers\Api\PatientController::class, 'markUrgent']);
    Route::post('patients/{patient}/upload-document', [\App\Http\Controllers\Api\PatientController::class, 'uploadDocument']);
    Route::delete('patient-documents/{document}', [\App\Http\Controllers\Api\PatientController::class, 'deleteDocument']);
    Route::get('patients-search', [\App\Http\Controllers\Api\PatientController::class, 'search']);

    // Prefixes (Master > Reception)
    Route::apiResource('prefixes', PrefixController::class);
    Route::get('prefixes-active', [PrefixController::class, 'active']);

    // Genders (Master > Reception)
    Route::apiResource('genders', GenderController::class);
    Route::get('genders-active', [GenderController::class, 'active']);

    // Blood Groups (Master > Reception)
    Route::apiResource('blood-groups', BloodGroupController::class);
    Route::get('blood-groups-active', [BloodGroupController::class, 'active']);

    // Patient Types (Master > Reception)
    Route::apiResource('patient-types', PatientTypeController::class);
    Route::get('patient-types-active', [PatientTypeController::class, 'active']);

    // Marital Statuses (Master > Reception)
    Route::apiResource('marital-statuses', MaritalStatusController::class);
    Route::get('marital-statuses-active', [MaritalStatusController::class, 'active']);

    // Reference Doctors (Master > Reception)
    Route::apiResource('reference-doctors', ReferenceDoctorController::class);
    Route::get('reference-doctors-active', [ReferenceDoctorController::class, 'active']);

    // Insurance Companies (Master > Reception)
    Route::apiResource('insurance-companies', InsuranceCompanyController::class);
    Route::get('insurance-companies-active', [InsuranceCompanyController::class, 'active']);

    // Qualifications (Master > Reception)
    Route::apiResource('qualifications', QualificationController::class);
    Route::get('qualifications-active', [QualificationController::class, 'active']);

    // Consult Masters (Master > Reception) - Doctor Availability Setup
    Route::apiResource('consult-masters', ConsultMasterController::class);
    Route::get('consult-masters-active', [ConsultMasterController::class, 'active']);
    Route::get('consult-masters/doctors-by-department/{departmentId}', [ConsultMasterController::class, 'doctorsByDepartment']);
    Route::get('consult-masters/doctor-schedules/{doctorId}', [ConsultMasterController::class, 'doctorSchedules']);
    Route::post('consult-masters/available-slots', [ConsultMasterController::class, 'availableSlots']);
    Route::post('consult-masters/preview-slots', [ConsultMasterController::class, 'previewSlots']);

    // Countries (Master > Address)
    Route::apiResource('countries', CountryController::class);
    Route::get('countries-active', [CountryController::class, 'active']);
    Route::get('countries-default', [CountryController::class, 'getDefault']);

    // States (Master > Address)
    Route::apiResource('states', StateController::class);
    Route::get('states-active', [StateController::class, 'active']);
    Route::get('states-default', [StateController::class, 'getDefault']);
    Route::get('states/{state}/hierarchy', [StateController::class, 'getWithHierarchy']);

    // Districts (Master > Address)
    Route::apiResource('districts', DistrictController::class);
    Route::get('districts-active', [DistrictController::class, 'active']);

    // Cities/Talukas (Master > Address)
    Route::apiResource('cities', CityController::class);
    Route::get('cities-active', [CityController::class, 'active']);

    // Areas/Villages (Master > Address)
    Route::apiResource('areas', AreaController::class);
    Route::get('areas-active', [AreaController::class, 'active']);
    Route::get('areas/{area}/hierarchy', [AreaController::class, 'getHierarchy']);
    Route::get('areas-search-hierarchy', [AreaController::class, 'searchWithHierarchy']);
    Route::get('areas-by-state', [AreaController::class, 'getByState']);
    Route::get('areas-by-country', [AreaController::class, 'getByCountry']);

    // Doctors
    Route::apiResource('doctors', \App\Http\Controllers\Api\DoctorController::class);

    // Departments
    Route::apiResource('departments', \App\Http\Controllers\Api\DepartmentController::class);

    // Appointments
    Route::apiResource('appointments', \App\Http\Controllers\Api\AppointmentController::class);
    Route::post('appointments/{appointment}/confirm', [\App\Http\Controllers\Api\AppointmentController::class, 'confirm']);
    Route::post('appointments/{appointment}/check-in', [\App\Http\Controllers\Api\AppointmentController::class, 'checkIn']);
    Route::post('appointments/{appointment}/convert-to-opd', [\App\Http\Controllers\Api\AppointmentController::class, 'convertToOpd']);
    Route::post('appointments/{appointment}/no-show', [\App\Http\Controllers\Api\AppointmentController::class, 'noShow']);
    Route::post('appointments/{appointment}/reschedule', [\App\Http\Controllers\Api\AppointmentController::class, 'reschedule']);
    Route::post('appointments/{appointment}/cancel', [\App\Http\Controllers\Api\AppointmentController::class, 'cancel']);
    Route::get('appointments/doctor/{doctor}/schedule', [\App\Http\Controllers\Api\AppointmentController::class, 'doctorSchedule']);
    Route::get('appointments/doctor/{doctor}/available-slots', [\App\Http\Controllers\Api\AppointmentController::class, 'availableSlots']);
    Route::get('appointments/doctor/{doctor}/duty-schedule', [\App\Http\Controllers\Api\AppointmentController::class, 'doctorDutySchedule']);
    Route::get('appointments/doctor/{doctor}/available-time-slots', [\App\Http\Controllers\Api\AppointmentController::class, 'availableTimeSlots']);
    Route::get('appointments/patient/{patient}', [\App\Http\Controllers\Api\AppointmentController::class, 'patientAppointments']);
    Route::post('appointments-transfer', [\App\Http\Controllers\Api\AppointmentController::class, 'transferToDoctor']);
    Route::post('appointments-bulk', [\App\Http\Controllers\Api\AppointmentController::class, 'bulkStore']);

    // OPD Visits
    Route::apiResource('opd-visits', \App\Http\Controllers\Api\OpdVisitController::class);
    Route::post('opd-visits/{opdVisit}/start-consultation', [\App\Http\Controllers\Api\OpdVisitController::class, 'startConsultation']);
    Route::post('opd-visits/{opdVisit}/complete-consultation', [\App\Http\Controllers\Api\OpdVisitController::class, 'completeConsultation']);
    Route::post('opd-visits/{opdVisit}/add-investigation', [\App\Http\Controllers\Api\OpdVisitController::class, 'addInvestigation']);
    Route::post('opd-visits/{opdVisit}/add-service', [\App\Http\Controllers\Api\OpdVisitController::class, 'addService']);
    Route::post('opd-visits/{opdVisit}/payment', [\App\Http\Controllers\Api\OpdVisitController::class, 'recordPayment']);
    Route::get('opd-visits/{opdVisit}/case-paper', [\App\Http\Controllers\Api\OpdVisitController::class, 'casePaper']);
    Route::get('opd-visits/{opdVisit}/receipt', [\App\Http\Controllers\Api\OpdVisitController::class, 'receipt']);
    Route::get('opd-visits/patient/{patient}/history', [\App\Http\Controllers\Api\OpdVisitController::class, 'patientHistory']);
    Route::get('opd-visits/doctor/{doctor}/queue', [\App\Http\Controllers\Api\OpdVisitController::class, 'doctorQueue']);

    // Skill Sets (Doctor Specialties)
    Route::apiResource('skill-sets', \App\Http\Controllers\Api\SkillSetController::class);
    Route::get('skill-sets/{skillSet}/validity', [\App\Http\Controllers\Api\SkillSetController::class, 'getValidity']);
    Route::post('skill-sets/{skillSet}/validity', [\App\Http\Controllers\Api\SkillSetController::class, 'setValidity']);

    // Health Packages
    Route::apiResource('health-packages', \App\Http\Controllers\Api\HealthPackageController::class);
    Route::post('health-packages/{healthPackage}/services', [\App\Http\Controllers\Api\HealthPackageController::class, 'addService']);
    Route::delete('health-packages/{healthPackage}/services/{service}', [\App\Http\Controllers\Api\HealthPackageController::class, 'removeService']);

    // IPD Admissions
    Route::apiResource('ipd-admissions', \App\Http\Controllers\Api\IpdAdmissionController::class);
    Route::get('ipd-admissions-summary', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'summary']);
    Route::get('ipd-admissions-bed-availability', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'bedAvailability']);
    Route::get('ipd-admissions-available-beds/{wardId?}', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'availableBeds']);
    Route::get('ipd-admissions-doctor-patients', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'doctorPatientList']);
    // IPD Case Sheet Operations
    Route::post('ipd-admissions/{admission}/transfer-bed', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'transferBed']);
    Route::post('ipd-admissions/{admission}/progress-notes', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'addProgressNote']);
    Route::get('ipd-admissions/{admission}/progress-notes', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'getProgressNotes']);
    Route::post('ipd-admissions/{admission}/nursing-charts', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'addNursingChart']);
    Route::get('ipd-admissions/{admission}/nursing-charts', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'getNursingCharts']);
    Route::post('ipd-admissions/{admission}/services', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'addService']);
    Route::get('ipd-admissions/{admission}/services', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'getServices']);
    Route::post('ipd-admissions/{admission}/medications', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'addMedication']);
    Route::get('ipd-admissions/{admission}/medications', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'getMedications']);
    Route::put('ipd-admissions/{admission}/medications/{medication}', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'updateMedication']);
    Route::post('ipd-admissions/{admission}/investigations', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'addInvestigation']);
    Route::get('ipd-admissions/{admission}/investigations', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'getInvestigations']);
    Route::post('ipd-admissions/{admission}/advance-payments', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'collectAdvance']);
    Route::get('ipd-admissions/{admission}/advance-payments', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'getAdvancePayments']);
    Route::get('ipd-admissions/{admission}/running-bill', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'getRunningBill']);
    Route::post('ipd-admissions/{admission}/initiate-discharge', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'initiateDischarge']);
    Route::post('ipd-admissions/{admission}/complete-discharge', [\App\Http\Controllers\Api\IpdAdmissionController::class, 'completeDischarge']);

    // Wards & Beds
    Route::apiResource('wards', \App\Http\Controllers\Api\WardController::class);
    Route::apiResource('beds', \App\Http\Controllers\Api\BedController::class);
    Route::get('beds/available', [\App\Http\Controllers\Api\BedController::class, 'available']);

    // Laboratory
    Route::apiResource('lab-categories', \App\Http\Controllers\Api\LabCategoryController::class);
    Route::apiResource('lab-tests', \App\Http\Controllers\Api\LabTestController::class);
    Route::apiResource('lab-orders', \App\Http\Controllers\Api\LabOrderController::class);
    Route::post('lab-orders/{order}/results', [\App\Http\Controllers\Api\LabOrderController::class, 'storeResults']);

    // Pharmacy
    Route::apiResource('drug-categories', \App\Http\Controllers\Api\DrugCategoryController::class);
    Route::apiResource('drugs', \App\Http\Controllers\Api\DrugController::class);
    Route::apiResource('drug-batches', \App\Http\Controllers\Api\DrugBatchController::class);
    Route::apiResource('pharmacy-sales', \App\Http\Controllers\Api\PharmacySaleController::class);

    // Billing
    Route::apiResource('services', \App\Http\Controllers\Api\ServiceController::class);
    Route::post('services/{service}/revise-rate', [\App\Http\Controllers\Api\ServiceController::class, 'reviseRate']);
    Route::get('services-get-rate', [\App\Http\Controllers\Api\ServiceController::class, 'getRate']);
    Route::get('services-class-rates/{classId}', [\App\Http\Controllers\Api\ServiceController::class, 'getClassRates']);
    Route::get('services-by-type/{type}', [\App\Http\Controllers\Api\ServiceController::class, 'byType']);
    Route::get('services-followup', [\App\Http\Controllers\Api\ServiceController::class, 'followupServices']);
    Route::get('services-health-checkup', [\App\Http\Controllers\Api\ServiceController::class, 'healthCheckupServices']);
    Route::apiResource('bills', \App\Http\Controllers\Api\BillController::class);
    Route::apiResource('payments', \App\Http\Controllers\Api\PaymentController::class);

    // OPD Masters
    // Clients (TPA/Insurance/Corporate)
    Route::apiResource('clients', \App\Http\Controllers\Api\ClientController::class);

    // Patient Classes
    Route::apiResource('patient-classes', \App\Http\Controllers\Api\PatientClassController::class);
    Route::post('patient-classes/{patientClass}/copy-rates', [\App\Http\Controllers\Api\PatientClassController::class, 'copyRates']);
    Route::post('patient-classes/{patientClass}/revise-rates', [\App\Http\Controllers\Api\PatientClassController::class, 'reviseRates']);

    // Cashless Price List
    Route::apiResource('cashless-price-lists', \App\Http\Controllers\Api\CashlessPriceListController::class);
    Route::post('cashless-price-lists/copy-from-standard', [\App\Http\Controllers\Api\CashlessPriceListController::class, 'copyFromStandard']);
    Route::get('cashless-price-lists/get-rate', [\App\Http\Controllers\Api\CashlessPriceListController::class, 'getRate']);

    // Payment Modes
    Route::apiResource('payment-modes', \App\Http\Controllers\Api\PaymentModeController::class);

    // Cancel Reasons
    Route::apiResource('cancel-reasons', \App\Http\Controllers\Api\CancelReasonController::class);

    // Vaccinations
    Route::apiResource('vaccinations', \App\Http\Controllers\Api\VaccinationController::class);
    Route::get('patients/{patient}/vaccinations', [\App\Http\Controllers\Api\VaccinationController::class, 'patientRecords']);
    Route::post('patient-vaccinations', [\App\Http\Controllers\Api\VaccinationController::class, 'scheduleVaccination']);
    Route::put('patient-vaccinations/{patientVaccination}/administer', [\App\Http\Controllers\Api\VaccinationController::class, 'administerVaccination']);

    // OPD Configuration
    Route::get('opd-configuration', [\App\Http\Controllers\Api\OpdConfigurationController::class, 'index']);
    Route::post('opd-configuration', [\App\Http\Controllers\Api\OpdConfigurationController::class, 'store']);
    Route::put('opd-configuration/{config}', [\App\Http\Controllers\Api\OpdConfigurationController::class, 'update']);

    // Doctor OPD Rates
    Route::apiResource('doctor-opd-rates', \App\Http\Controllers\Api\DoctorOpdRateController::class);
    Route::get('doctor-opd-rates/doctor/{doctor}', [\App\Http\Controllers\Api\DoctorOpdRateController::class, 'doctorRates']);
    Route::get('doctor-opd-rates-get-rate', [\App\Http\Controllers\Api\DoctorOpdRateController::class, 'getRate']);
    Route::post('doctor-opd-rates/doctor/{doctor}/bulk', [\App\Http\Controllers\Api\DoctorOpdRateController::class, 'bulkSetRates']);

    // Doctor Groups (Units/Teams)
    Route::apiResource('doctor-groups', \App\Http\Controllers\Api\DoctorGroupController::class);
    Route::post('doctor-groups/{group}/members', [\App\Http\Controllers\Api\DoctorGroupController::class, 'addMember']);
    Route::delete('doctor-groups/{group}/members/{doctor}', [\App\Http\Controllers\Api\DoctorGroupController::class, 'removeMember']);
    Route::get('doctor-groups/{group}/consulting-doctors', [\App\Http\Controllers\Api\DoctorGroupController::class, 'consultingDoctors']);

    // Entry Cards
    Route::apiResource('entry-cards', \App\Http\Controllers\Api\EntryCardController::class)->except(['update', 'destroy']);
    Route::post('entry-cards/{entryCard}/cancel', [\App\Http\Controllers\Api\EntryCardController::class, 'cancel']);
    Route::post('entry-cards/{entryCard}/renew', [\App\Http\Controllers\Api\EntryCardController::class, 'renew']);
    Route::get('entry-cards/patient/{patient}/check', [\App\Http\Controllers\Api\EntryCardController::class, 'checkValidity']);

    // OPD Time Slots
    Route::apiResource('opd-time-slots', \App\Http\Controllers\Api\OpdTimeSlotController::class);
    Route::get('opd-time-slots-available', [\App\Http\Controllers\Api\OpdTimeSlotController::class, 'availableSlots']);
    Route::get('opd-time-slots/doctor/{doctor}/schedule', [\App\Http\Controllers\Api\OpdTimeSlotController::class, 'doctorSchedule']);
    Route::post('opd-time-slots/bulk', [\App\Http\Controllers\Api\OpdTimeSlotController::class, 'bulkCreate']);

    // Rate Change Requests (Doctor Approval Workflow)
    Route::apiResource('rate-change-requests', \App\Http\Controllers\Api\RateChangeRequestController::class)->except(['update']);
    Route::post('rate-change-requests/{request}/approve', [\App\Http\Controllers\Api\RateChangeRequestController::class, 'approve']);
    Route::post('rate-change-requests/{request}/reject', [\App\Http\Controllers\Api\RateChangeRequestController::class, 'reject']);
    Route::get('rate-change-requests-pending-count', [\App\Http\Controllers\Api\RateChangeRequestController::class, 'pendingCount']);
    Route::post('rate-change-requests/bulk-approve', [\App\Http\Controllers\Api\RateChangeRequestController::class, 'bulkApprove']);

    // Users (Admin only)
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);

    // Settings
    Route::get('settings', [\App\Http\Controllers\Api\SettingController::class, 'index']);
    Route::post('settings', [\App\Http\Controllers\Api\SettingController::class, 'store']);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('patient-summary', [\App\Http\Controllers\Api\ReportController::class, 'patientSummary']);
        Route::get('revenue', [\App\Http\Controllers\Api\ReportController::class, 'revenue']);
        Route::get('department-wise', [\App\Http\Controllers\Api\ReportController::class, 'departmentWise']);
    });

    // Claude AI Chat (Super Admin Only)
    Route::prefix('claude-chat')->group(function () {
        Route::post('/send', [\App\Http\Controllers\Api\ClaudeChatController::class, 'sendMessage']);
        Route::get('/history', [\App\Http\Controllers\Api\ClaudeChatController::class, 'getHistory']);
        Route::post('/clear', [\App\Http\Controllers\Api\ClaudeChatController::class, 'clearConversation']);
        Route::get('/conversations', [\App\Http\Controllers\Api\ClaudeChatController::class, 'listConversations']);
    });

    // ============================================
    // RBAC - Role Based Access Control
    // ============================================
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/permissions', [RoleController::class, 'permissions']);
        Route::get('/{role}', [RoleController::class, 'show']);
        Route::put('/{role}', [RoleController::class, 'update']);
        Route::delete('/{role}', [RoleController::class, 'destroy']);
        Route::post('/assign-to-user', [RoleController::class, 'assignToUser']);
    });

    // ============================================
    // Notifications - SMS/Email
    // ============================================
    Route::prefix('notifications')->group(function () {
        // SMS Gateways
        Route::get('/gateways', [NotificationController::class, 'gateways']);
        Route::post('/gateways', [NotificationController::class, 'storeGateway']);
        Route::put('/gateways/{gateway}', [NotificationController::class, 'updateGateway']);
        Route::post('/gateways/{gateway}/test', [NotificationController::class, 'testGateway']);

        // Templates
        Route::get('/templates', [NotificationController::class, 'templates']);
        Route::post('/templates', [NotificationController::class, 'storeTemplate']);
        Route::put('/templates/{template}', [NotificationController::class, 'updateTemplate']);

        // Logs
        Route::get('/logs', [NotificationController::class, 'logs']);

        // Send
        Route::post('/send', [NotificationController::class, 'send']);
    });

    // ============================================
    // Radiology Module
    // ============================================
    Route::prefix('radiology')->group(function () {
        // Modalities
        Route::apiResource('modalities', RadiologyModalityController::class);

        // Tests
        Route::apiResource('tests', RadiologyTestController::class);

        // Orders
        Route::get('/orders', [RadiologyOrderController::class, 'index']);
        Route::post('/orders', [RadiologyOrderController::class, 'store']);
        Route::get('/orders/worklist', [RadiologyOrderController::class, 'worklist']);
        Route::get('/orders/{order}', [RadiologyOrderController::class, 'show']);
        Route::post('/orders/{order}/status', [RadiologyOrderController::class, 'updateStatus']);
        Route::post('/orders/details/{detail}/status', [RadiologyOrderController::class, 'updateDetailStatus']);

        // Reports
        Route::get('/reports', [RadiologyReportController::class, 'index']);
        Route::post('/reports', [RadiologyReportController::class, 'store']);
        Route::get('/reports/pending', [RadiologyReportController::class, 'pendingReports']);
        Route::get('/reports/{report}', [RadiologyReportController::class, 'show']);
        Route::put('/reports/{report}', [RadiologyReportController::class, 'update']);
        Route::post('/reports/{report}/verify', [RadiologyReportController::class, 'verify']);
        Route::post('/reports/{report}/upload-image', [RadiologyReportController::class, 'uploadImage']);
        Route::get('/reports/{report}/pdf', [RadiologyReportController::class, 'generatePdf']);
    });

    // ============================================
    // Operation Theater (OT) Management
    // ============================================
    Route::prefix('ot')->group(function () {
        // Theaters
        Route::apiResource('theaters', OperationTheaterController::class);
        Route::get('/theaters-availability', [OperationTheaterController::class, 'availability']);

        // Surgery Types
        Route::apiResource('surgery-types', SurgeryTypeController::class);

        // Schedules
        Route::get('/schedules', [OtScheduleController::class, 'index']);
        Route::post('/schedules', [OtScheduleController::class, 'store']);
        Route::get('/schedules/today', [OtScheduleController::class, 'todaySchedule']);
        Route::get('/schedules/{schedule}', [OtScheduleController::class, 'show']);
        Route::put('/schedules/{schedule}', [OtScheduleController::class, 'update']);
        Route::post('/schedules/{schedule}/status', [OtScheduleController::class, 'updateStatus']);
        Route::post('/schedules/{schedule}/checklist', [OtScheduleController::class, 'updateChecklist']);

        // Procedures
        Route::get('/procedures', [OtProcedureController::class, 'index']);
        Route::post('/schedules/{schedule}/start', [OtProcedureController::class, 'start']);
        Route::get('/procedures/{procedure}', [OtProcedureController::class, 'show']);
        Route::put('/procedures/{procedure}', [OtProcedureController::class, 'update']);
        Route::post('/procedures/{procedure}/complete', [OtProcedureController::class, 'complete']);
        Route::post('/procedures/{procedure}/consumables', [OtProcedureController::class, 'addConsumable']);
        Route::post('/procedures/{procedure}/anesthesia', [OtProcedureController::class, 'updateAnesthesia']);
        Route::get('/procedures/{procedure}/ot-notes', [OtProcedureController::class, 'otNotes']);
    });

    // ============================================
    // Inventory Management
    // ============================================
    Route::prefix('inventory')->group(function () {
        // Stores
        Route::apiResource('stores', StoreController::class);

        // Item Categories
        Route::apiResource('item-categories', \App\Http\Controllers\Api\ItemCategoryController::class);

        // Items
        Route::get('/items', [ItemController::class, 'index']);
        Route::post('/items', [ItemController::class, 'store']);
        Route::get('/items/low-stock', [ItemController::class, 'lowStock']);
        Route::get('/items/expiring', [ItemController::class, 'expiringItems']);
        Route::get('/items/{item}', [ItemController::class, 'show']);
        Route::put('/items/{item}', [ItemController::class, 'update']);
        Route::get('/items/{item}/stock', [ItemController::class, 'stock']);

        // Suppliers
        Route::apiResource('suppliers', \App\Http\Controllers\Api\SupplierController::class);

        // Indents
        Route::get('/indents', [IndentController::class, 'index']);
        Route::post('/indents', [IndentController::class, 'store']);
        Route::get('/indents/{indent}', [IndentController::class, 'show']);
        Route::post('/indents/{indent}/submit', [IndentController::class, 'submit']);
        Route::post('/indents/{indent}/approve', [IndentController::class, 'approve']);
        Route::post('/indents/{indent}/reject', [IndentController::class, 'reject']);
        Route::post('/indents/{indent}/fulfill', [IndentController::class, 'fulfill']);

        // Purchase Orders
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve']);
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveGoods']);
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
    });

    // ============================================
    // Birth & Death Registration
    // ============================================
    Route::prefix('birth-registrations')->group(function () {
        Route::get('/', [BirthRegistrationController::class, 'index']);
        Route::post('/', [BirthRegistrationController::class, 'store']);
        Route::get('/{registration}', [BirthRegistrationController::class, 'show']);
        Route::put('/{registration}', [BirthRegistrationController::class, 'update']);
        Route::post('/{registration}/issue-certificate', [BirthRegistrationController::class, 'issueCertificate']);
        Route::post('/{registration}/govt-register', [BirthRegistrationController::class, 'registerWithGovernment']);
    });

    Route::prefix('death-registrations')->group(function () {
        Route::get('/', [DeathRegistrationController::class, 'index']);
        Route::post('/', [DeathRegistrationController::class, 'store']);
        Route::get('/{registration}', [DeathRegistrationController::class, 'show']);
        Route::put('/{registration}', [DeathRegistrationController::class, 'update']);
        Route::post('/{registration}/issue-certificate', [DeathRegistrationController::class, 'issueCertificate']);
        Route::post('/{registration}/govt-register', [DeathRegistrationController::class, 'registerWithGovernment']);
    });

    // ============================================
    // MRD - Medical Records Department
    // ============================================
    Route::prefix('mrd')->group(function () {
        // Patient Documents
        Route::get('/patients/{patient}/documents', [MrdController::class, 'documents']);
        Route::post('/patients/{patient}/documents', [MrdController::class, 'uploadDocument']);

        // File Movements - All (for File Tracking page)
        Route::get('/file-movements', [MrdController::class, 'allFileMovements']);

        // File Movements - Patient specific
        Route::get('/patients/{patient}/file-movements', [MrdController::class, 'fileMovements']);
        Route::post('/patients/{patient}/issue-file', [MrdController::class, 'issueFile']);
        Route::post('/file-movements/{movement}/return', [MrdController::class, 'returnFile']);

        // Record Requests
        Route::get('/record-requests', [MrdController::class, 'recordRequests']);
        Route::post('/record-requests', [MrdController::class, 'createRecordRequest']);
        Route::post('/record-requests/{recordRequest}/process', [MrdController::class, 'processRecordRequest']);
        Route::post('/record-requests/{recordRequest}/approve', [MrdController::class, 'approveRecordRequest']);
        Route::post('/record-requests/{recordRequest}/reject', [MrdController::class, 'rejectRecordRequest']);
        Route::post('/record-requests/{recordRequest}/complete', [MrdController::class, 'completeRecordRequest']);

        // Consents - Global (for Consents page)
        Route::get('/consents', [MrdController::class, 'allConsents']);
        Route::post('/consents/{consent}/revoke', [MrdController::class, 'revokeConsent']);

        // Consents - Patient specific
        Route::get('/patients/{patient}/consents', [MrdController::class, 'consents']);
        Route::post('/patients/{patient}/consents', [MrdController::class, 'recordConsent']);

        // ICD Coding - Patient specific
        Route::get('/patients/{patient}/diagnoses', [MrdController::class, 'diagnoses']);
        Route::post('/patients/{patient}/diagnoses', [MrdController::class, 'addDiagnosis']);

        // ICD Coding - Global (for ICD Coding page)
        Route::get('/icd-codes', [MrdController::class, 'searchIcdCodes']);
        Route::get('/uncoded-visits', [MrdController::class, 'uncodedVisits']);
        Route::get('/coded-visits', [MrdController::class, 'codedVisits']);
        Route::get('/visits/{visit}/codes', [MrdController::class, 'getVisitCodes']);
        Route::post('/visits/{visit}/codes', [MrdController::class, 'saveVisitCodes']);

        // Complete Record
        Route::get('/patients/{patient}/complete-record', [MrdController::class, 'completeRecord']);
    });

    // ============================================
    // ABHA / ABDM Integration
    // ============================================
    Route::prefix('abha')->group(function () {
        // ABHA Registration Flow
        Route::post('/generate-aadhaar-otp', [AbhaController::class, 'generateAadhaarOtp']);
        Route::post('/verify-aadhaar-otp', [AbhaController::class, 'verifyAadhaarOtp']);
        Route::post('/create-abha', [AbhaController::class, 'createAbha']);
        Route::post('/link-patient/{patient}', [AbhaController::class, 'linkToPatient']);
        Route::get('/patient/{patient}', [AbhaController::class, 'getPatientAbha']);

        // Health Records Sharing
        Route::post('/share-records/{patient}', [AbhaController::class, 'shareHealthRecords']);

        // Consent Management
        Route::get('/consents/{patient}', [AbhaController::class, 'consentRequests']);
        Route::post('/consents/{consent}/process', [AbhaController::class, 'processConsent']);

        // ABDM Callbacks
        Route::post('/callback/on-generate-otp', [AbhaController::class, 'onGenerateOtp']);
        Route::post('/callback/on-consent-request', [AbhaController::class, 'onConsentRequest']);
    });

    // ============================================
    // FHIR R4 API
    // ============================================
    Route::prefix('fhir/r4')->group(function () {
        Route::get('/metadata', [FhirController::class, 'capability']);
        Route::get('/Patient', [FhirController::class, 'searchPatients']);
        Route::get('/Patient/{id}', [FhirController::class, 'readPatient']);
        Route::get('/Observation', [FhirController::class, 'searchObservations']);
        Route::get('/DiagnosticReport', [FhirController::class, 'searchDiagnosticReports']);
        Route::get('/MedicationRequest', [FhirController::class, 'searchMedicationRequests']);
    });

    // HL7 Messages
    Route::prefix('hl7')->group(function () {
        Route::get('/messages', [FhirController::class, 'hl7Messages']);
        Route::post('/messages', [FhirController::class, 'processHl7Message']);
    });

    // ICD Code Lookup
    Route::get('/icd-codes/search', [FhirController::class, 'searchIcdCodes']);

    // ============================================
    // Claude AI Development Assistant
    // ============================================
    Route::prefix('claude-assistant')->group(function () {
        Route::post('/chat', [ClaudeAssistantController::class, 'chat']);
        Route::get('/models', [ClaudeAssistantController::class, 'models']);
        Route::post('/get-file', [ClaudeAssistantController::class, 'getFile']);
        Route::post('/apply-changes', [ClaudeAssistantController::class, 'applyChanges']);
        Route::post('/preview-diff', [ClaudeAssistantController::class, 'previewDiff']);
        Route::get('/list-files', [ClaudeAssistantController::class, 'listFiles']);
        Route::get('/search-files', [ClaudeAssistantController::class, 'searchFiles']);
    });
});

// ============================================
// Patient Portal (Separate Auth Guard)
// ============================================
Route::prefix('patient-portal')->group(function () {
    // Public routes
    Route::post('/register', [PatientPortalController::class, 'register']);
    Route::post('/login', [PatientPortalController::class, 'login']);
    Route::post('/verify-otp', [PatientPortalController::class, 'verifyOtp']);
    Route::post('/resend-otp', [PatientPortalController::class, 'resendOtp']);

    // Protected routes (Patient Auth)
    Route::middleware('auth:patient')->group(function () {
        Route::post('/logout', [PatientPortalController::class, 'logout']);
        Route::get('/profile', [PatientPortalController::class, 'profile']);
        Route::put('/profile', [PatientPortalController::class, 'updateProfile']);

        // Appointment Requests
        Route::get('/appointment-requests', [PatientPortalController::class, 'appointmentRequests']);
        Route::post('/appointment-requests', [PatientPortalController::class, 'requestAppointment']);
        Route::post('/appointment-requests/{appointmentRequest}/cancel', [PatientPortalController::class, 'cancelAppointmentRequest']);

        // View Medical Records
        Route::get('/appointments', [PatientPortalController::class, 'appointments']);
        Route::get('/lab-reports', [PatientPortalController::class, 'labReports']);
        Route::get('/prescriptions', [PatientPortalController::class, 'prescriptions']);
        Route::get('/bills', [PatientPortalController::class, 'bills']);

        // Feedback
        Route::post('/feedback', [PatientPortalController::class, 'submitFeedback']);
        Route::get('/my-feedback', [PatientPortalController::class, 'myFeedback']);
    });
});
