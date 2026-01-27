<template>
    <div class="ipd-form">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">{{ isViewMode ? 'IPD Case Sheet' : 'New Admission' }}</h4>
                <small v-if="admission.ipd_number" class="text-muted">{{ admission.ipd_number }}</small>
            </div>
            <div class="d-flex gap-2">
                <router-link to="/ipd" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </router-link>
                <!-- Print Dropdown -->
                <div v-if="isViewMode" class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" @click.prevent="printDischargeSummary">
                            <i class="bi bi-file-text"></i> Discharge Summary
                        </a></li>
                        <li><a class="dropdown-item" href="#" @click.prevent="printCaseSheet">
                            <i class="bi bi-clipboard2-pulse"></i> Case Sheet
                        </a></li>
                    </ul>
                </div>
                <button v-if="isViewMode && admission.status === 'admitted'" class="btn btn-success" @click="showDischargeModal = true">
                    <i class="bi bi-box-arrow-right"></i> Discharge
                </button>
            </div>
        </div>

        <!-- Patient Info Banner (View Mode) -->
        <div v-if="isViewMode && admission.patient" class="card bg-light mb-4">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h5 class="mb-1">{{ admission.patient?.patient_name || (admission.patient?.first_name + ' ' + admission.patient?.last_name) }}</h5>
                        <small class="text-muted">
                            {{ admission.patient?.gender }} / {{ admission.patient?.age || (admission.patient?.age_years + ' yrs') }} |
                            {{ admission.patient?.mobile }}
                        </small>
                    </div>
                    <div class="col-md-2 text-center border-start border-end">
                        <small class="text-muted d-block">Ward / Bed</small>
                        <strong>{{ admission.ward?.ward_name }} - {{ admission.bed?.bed_number }}</strong>
                    </div>
                    <div class="col-md-2 text-center border-end">
                        <small class="text-muted d-block">Doctor</small>
                        <strong>{{ admission.treating_doctor?.full_name }}</strong>
                    </div>
                    <div class="col-md-2 text-center border-end">
                        <small class="text-muted d-block">LOS</small>
                        <strong>{{ admission.los_days }} Days</strong>
                    </div>
                    <div class="col-md-2 text-center">
                        <small class="text-muted d-block">Status</small>
                        <span :class="getStatusBadge(admission.status)">{{ formatStatus(admission.status) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Mode: Tabs -->
        <div v-if="isViewMode">
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: activeTab === 'overview' }" href="#" @click.prevent="activeTab = 'overview'">
                        <i class="bi bi-info-circle"></i> Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: activeTab === 'clinical' }" href="#" @click.prevent="activeTab = 'clinical'">
                        <i class="bi bi-clipboard2-pulse"></i> Clinical Notes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: activeTab === 'nursing' }" href="#" @click.prevent="activeTab = 'nursing'">
                        <i class="bi bi-heart-pulse"></i> Nursing Charts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: activeTab === 'medications' }" href="#" @click.prevent="activeTab = 'medications'">
                        <i class="bi bi-capsule"></i> Medications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: activeTab === 'investigations' }" href="#" @click.prevent="activeTab = 'investigations'">
                        <i class="bi bi-clipboard-data"></i> Investigations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: activeTab === 'billing' }" href="#" @click.prevent="activeTab = 'billing'">
                        <i class="bi bi-receipt"></i> Billing
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div v-show="activeTab === 'overview'" class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span>Admission Details</span>
                            <button class="btn btn-sm btn-outline-primary" @click="showEditAdmission = true" v-if="admission.status === 'admitted'">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label text-muted small">Admission Date/Time</label>
                                    <div>{{ formatDate(admission.admission_date) }} {{ admission.admission_time }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small">Admission Type</label>
                                    <div>{{ admission.admission_type || '-' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small">Source</label>
                                    <div>{{ admission.admission_source || '-' }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small">Provisional Diagnosis</label>
                                    <div>{{ admission.provisional_diagnosis || admission.diagnosis_at_admission || '-' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small">ICD Code</label>
                                    <div>{{ admission.icd_code || '-' }}</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Treatment Plan</label>
                                <div>{{ admission.treatment_plan || '-' }}</div>
                            </div>
                            <div class="mb-3" v-if="admission.mlc_case">
                                <div class="alert alert-danger mb-0">
                                    <strong>MLC Case:</strong> {{ admission.mlc_number }}<br>
                                    <small>Police Station: {{ admission.police_station }} | Brought By: {{ admission.brought_by }}</small>
                                </div>
                            </div>
                            <div v-if="admission.insurance_applicable && admission.insurance_company && admission.scheme_type !== 'none'" class="alert alert-info mb-0">
                                <strong>Insurance:</strong> {{ admission.insurance_company }} ({{ admission.scheme_type }})<br>
                                <small>TPA: {{ admission.tpa_name || 'N/A' }} | Pre-Auth: Rs {{ admission.pre_auth_amount || 0 }}</small>
                            </div>
                            <div v-if="admission.attendant_name" class="alert alert-secondary mb-0 mt-2">
                                <strong>Responsible Person:</strong> {{ admission.attendant_name }}<br>
                                <small>
                                    <strong>Relation:</strong> {{ admission.attendant_relation || 'N/A' }} |
                                    <strong>Mobile:</strong> {{ admission.attendant_mobile || 'N/A' }}
                                    <span v-if="admission.attendant_email"> | <strong>Email:</strong> {{ admission.attendant_email }}</span>
                                </small>
                                <div v-if="admission.attendant_address" class="mt-1">
                                    <small><strong>Address:</strong> {{ admission.attendant_address }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Progress Notes -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <span>Recent Progress Notes</span>
                            <button class="btn btn-sm btn-primary" @click="showAddNote = true" v-if="admission.status === 'admitted'">
                                <i class="bi bi-plus"></i> Add Note
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div v-if="progressNotes.length === 0" class="text-center text-muted py-4">
                                No progress notes recorded
                            </div>
                            <div v-else class="list-group list-group-flush">
                                <div v-for="note in progressNotes.slice(0, 5)" :key="note.note_id" class="list-group-item">
                                    <div class="d-flex justify-content-between mb-2">
                                        <strong>{{ note.doctor?.full_name }}</strong>
                                        <small class="text-muted">{{ formatDate(note.note_date) }} {{ note.note_time }}</small>
                                    </div>
                                    <div v-if="note.subjective"><strong>S:</strong> {{ note.subjective }}</div>
                                    <div v-if="note.objective"><strong>O:</strong> {{ note.objective }}</div>
                                    <div v-if="note.assessment"><strong>A:</strong> {{ note.assessment }}</div>
                                    <div v-if="note.plan"><strong>P:</strong> {{ note.plan }}</div>
                                    <div v-if="note.general_notes">{{ note.general_notes }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Running Bill Summary -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">Running Bill</div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Bed Charges ({{ runningBill.bed_details?.total_days || 0 }} days)</span>
                                <strong>Rs {{ runningBill.billing?.bed_charges || 0 }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Services</span>
                                <strong>Rs {{ runningBill.billing?.services_total || 0 }}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Gross Total</span>
                                <strong>Rs {{ runningBill.billing?.gross_total || 0 }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Discount</span>
                                <strong>- Rs {{ runningBill.billing?.discount || 0 }}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Net Amount</strong>
                                <strong>Rs {{ runningBill.billing?.net_total || 0 }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2 text-primary">
                                <span>Advance Paid</span>
                                <strong>Rs {{ runningBill.billing?.advance_paid || 0 }}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong class="text-danger">Balance Due</strong>
                                <strong class="text-danger">Rs {{ runningBill.billing?.balance_due || 0 }}</strong>
                            </div>
                            <button class="btn btn-outline-primary btn-sm w-100 mt-3" @click="showAdvancePayment = true" v-if="admission.status === 'admitted'">
                                <i class="bi bi-plus"></i> Collect Advance
                            </button>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card" v-if="admission.status === 'admitted'">
                        <div class="card-header">Quick Actions</div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" @click="showTransferBed = true">
                                    <i class="bi bi-arrows-move"></i> Transfer Bed
                                </button>
                                <button class="btn btn-outline-success btn-sm" @click="showAddService = true">
                                    <i class="bi bi-plus-circle"></i> Add Service
                                </button>
                                <button class="btn btn-outline-info btn-sm" @click="showAddMedication = true">
                                    <i class="bi bi-capsule"></i> Add Medication
                                </button>
                                <button class="btn btn-outline-warning btn-sm" @click="showAddInvestigation = true">
                                    <i class="bi bi-clipboard-data"></i> Order Investigation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clinical Notes Tab -->
            <div v-show="activeTab === 'clinical'">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <span>Progress Notes</span>
                        <button class="btn btn-sm btn-primary" @click="showAddNote = true" v-if="admission.status === 'admitted'">
                            <i class="bi bi-plus"></i> Add Note
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div v-if="progressNotes.length === 0" class="text-center text-muted py-4">
                            No progress notes recorded
                        </div>
                        <div v-else class="list-group list-group-flush">
                            <div v-for="note in progressNotes" :key="note.note_id" class="list-group-item">
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <strong>{{ note.doctor?.full_name }}</strong>
                                        <span class="badge bg-secondary ms-2">{{ note.note_type }}</span>
                                    </div>
                                    <small class="text-muted">{{ formatDate(note.note_date) }} {{ note.note_time }}</small>
                                </div>
                                <div v-if="note.subjective" class="mb-1"><strong>Subjective:</strong> {{ note.subjective }}</div>
                                <div v-if="note.objective" class="mb-1"><strong>Objective:</strong> {{ note.objective }}</div>
                                <div v-if="note.assessment" class="mb-1"><strong>Assessment:</strong> {{ note.assessment }}</div>
                                <div v-if="note.plan" class="mb-1"><strong>Plan:</strong> {{ note.plan }}</div>
                                <div v-if="note.instructions" class="mb-1"><strong>Instructions:</strong> {{ note.instructions }}</div>
                                <div v-if="note.general_notes">{{ note.general_notes }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nursing Charts Tab -->
            <div v-show="activeTab === 'nursing'">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <span>Nursing Charts</span>
                        <button class="btn btn-sm btn-primary" @click="showAddNursingChart = true" v-if="admission.status === 'admitted'">
                            <i class="bi bi-plus"></i> Add Entry
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date/Shift</th>
                                        <th>BP</th>
                                        <th>Pulse</th>
                                        <th>Temp</th>
                                        <th>SpO2</th>
                                        <th>Sugar</th>
                                        <th>Intake</th>
                                        <th>Output</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="nursingCharts.length === 0">
                                        <td colspan="9" class="text-center text-muted py-3">No entries recorded</td>
                                    </tr>
                                    <tr v-for="chart in nursingCharts" :key="chart.chart_id">
                                        <td>
                                            {{ formatDate(chart.chart_date) }}<br>
                                            <small class="text-muted">{{ chart.shift }}</small>
                                        </td>
                                        <td>{{ chart.blood_pressure || '-' }}</td>
                                        <td>{{ chart.pulse || '-' }}</td>
                                        <td>{{ chart.temperature || '-' }}</td>
                                        <td>{{ chart.spo2 || '-' }}%</td>
                                        <td>{{ chart.blood_sugar || '-' }}</td>
                                        <td>{{ chart.total_intake || 0 }} ml</td>
                                        <td>{{ chart.total_output || 0 }} ml</td>
                                        <td><small>{{ chart.nursing_notes || '-' }}</small></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medications Tab -->
            <div v-show="activeTab === 'medications'">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <span>Medications</span>
                        <button class="btn btn-sm btn-primary" @click="showAddMedication = true" v-if="admission.status === 'admitted'">
                            <i class="bi bi-plus"></i> Add Medication
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Drug</th>
                                        <th>Dosage</th>
                                        <th>Route</th>
                                        <th>Frequency</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="medications.length === 0">
                                        <td colspan="7" class="text-center text-muted py-3">No medications ordered</td>
                                    </tr>
                                    <tr v-for="med in medications" :key="med.medication_id">
                                        <td>{{ med.drug_name }}</td>
                                        <td>{{ med.dosage }}</td>
                                        <td>{{ med.route }}</td>
                                        <td>{{ med.frequency }}</td>
                                        <td>{{ med.duration_days }} days</td>
                                        <td>
                                            <span :class="getMedicationStatusBadge(med.status)">{{ med.status }}</span>
                                        </td>
                                        <td>
                                            <button v-if="med.status === 'ordered'" class="btn btn-xs btn-outline-danger" @click="stopMedication(med)">
                                                Stop
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Investigations Tab -->
            <div v-show="activeTab === 'investigations'">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <span>Investigations</span>
                        <button class="btn btn-sm btn-primary" @click="showAddInvestigation = true" v-if="admission.status === 'admitted'">
                            <i class="bi bi-plus"></i> Order Investigation
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Investigation</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="investigations.length === 0">
                                        <td colspan="6" class="text-center text-muted py-3">No investigations ordered</td>
                                    </tr>
                                    <tr v-for="inv in investigations" :key="inv.investigation_id">
                                        <td>{{ formatDate(inv.order_date) }}</td>
                                        <td>{{ inv.investigation_type }}</td>
                                        <td>{{ inv.investigation_name }}</td>
                                        <td>
                                            <span :class="inv.priority === 'stat' ? 'badge bg-danger' : inv.priority === 'urgent' ? 'badge bg-warning' : 'badge bg-secondary'">
                                                {{ inv.priority }}
                                            </span>
                                        </td>
                                        <td>{{ inv.status }}</td>
                                        <td><small>{{ inv.result || '-' }}</small></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Tab -->
            <div v-show="activeTab === 'billing'">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <span>Services & Charges</span>
                                <button class="btn btn-sm btn-primary" @click="showAddService = true" v-if="admission.status === 'admitted'">
                                    <i class="bi bi-plus"></i> Add Service
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Service</th>
                                                <th>Qty</th>
                                                <th>Rate</th>
                                                <th>Amount</th>
                                                <th style="width: 100px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="services.length === 0">
                                                <td colspan="7" class="text-center text-muted py-3">No services added</td>
                                            </tr>
                                            <tr v-for="svc in services" :key="svc.ipd_service_id">
                                                <td>{{ formatDate(svc.service_date) }}</td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ svc.service_type }}</span>
                                                </td>
                                                <td>
                                                    {{ svc.service_name }}
                                                    <span v-if="svc.is_package" class="badge bg-info ms-1">Package</span>
                                                </td>
                                                <td>{{ svc.quantity }}</td>
                                                <td>Rs {{ svc.rate }}</td>
                                                <td>
                                                    <strong>Rs {{ svc.net_amount }}</strong>
                                                    <small v-if="svc.discount > 0" class="text-muted d-block">
                                                        (Disc: Rs {{ svc.discount }})
                                                    </small>
                                                </td>
                                                <td>
                                                    <button v-if="admission.status === 'admitted'" class="btn btn-sm btn-outline-primary me-1" @click="editServiceItem(svc)" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button v-if="admission.status === 'admitted'" class="btn btn-sm btn-outline-danger" @click="deleteServiceItem(svc.ipd_service_id)" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between">
                                <span>Advance Payments</span>
                                <button class="btn btn-sm btn-primary" @click="showAdvancePayment = true" v-if="admission.status === 'admitted'">
                                    <i class="bi bi-plus"></i> Collect
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <div v-if="advancePayments.length === 0" class="list-group-item text-center text-muted">
                                        No advance payments
                                    </div>
                                    <div v-for="pay in advancePayments" :key="pay.advance_id" class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between">
                                                    <small class="text-primary">{{ pay.receipt_number }}</small>
                                                    <small class="text-muted">{{ formatDate(pay.payment_date) }}</small>
                                                </div>
                                                <div class="d-flex justify-content-between mt-1">
                                                    <span class="badge bg-info">{{ pay.payment_mode }}</span>
                                                    <strong>Rs {{ pay.amount }}</strong>
                                                </div>
                                                <small v-if="pay.remarks" class="text-muted d-block mt-1">{{ pay.remarks }}</small>
                                            </div>
                                            <div v-if="admission.status === 'admitted'" class="ms-2">
                                                <button class="btn btn-sm btn-outline-primary me-1" @click="editAdvancePayment(pay)" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" @click="deleteAdvancePayment(pay.advance_id)" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Mode: Admission Form -->
        <div v-else>
            <form @submit.prevent="submitAdmission">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Patient Selection -->
                        <div class="card mb-4">
                            <div class="card-header">Patient Details</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <div class="flex-grow-1 position-relative">
                                            <input
                                                type="text"
                                                class="form-control pe-5"
                                                v-model="patientSearch"
                                                @input="filterPatients"
                                                @focus="showPatientDropdown = true"
                                                @blur="hidePatientDropdown"
                                                placeholder="Search by name, mobile, ID..."
                                                autocomplete="off"
                                            />
                                            <button
                                                v-if="form.patient_id && patientSearch"
                                                type="button"
                                                class="btn-clear-patient"
                                                @click="clearPatient"
                                                title="Clear selected patient">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                            <!-- Patient Dropdown -->
                                            <div v-if="showPatientDropdown && filteredTodaysPatients.length > 0"
                                                 class="patient-dropdown">
                                                <div class="dropdown-section-header">
                                                    <i class="bi bi-calendar-check me-1"></i>
                                                    Today's OPD Patients
                                                </div>
                                                <div class="patient-dropdown-item"
                                                     v-for="p in filteredTodaysPatients"
                                                     :key="p.patient_id"
                                                     @mousedown.prevent="selectPatient(p)"
                                                     :class="{ 'active': form.patient_id === p.patient_id }">
                                                    <div class="patient-color-indicator" :style="{ backgroundColor: getPatientColor(p) }"></div>
                                                    <div class="patient-info">
                                                        <div class="patient-name">
                                                            {{ p.first_name }} {{ p.last_name }}
                                                            <span class="badge ms-1" :class="getGenderBadgeClass(p.gender)">
                                                                {{ p.gender || 'N/A' }}
                                                            </span>
                                                        </div>
                                                        <div class="patient-details">
                                                            <small class="text-muted">
                                                                <i class="bi bi-person-badge me-1"></i>ID: {{ p.patient_id }} |
                                                                <i class="bi bi-calendar3 ms-1 me-1"></i>{{ p.age_display || 'N/A' }} |
                                                                <i class="bi bi-telephone ms-1 me-1"></i>{{ p.mobile || 'N/A' }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-if="filteredTodaysPatients.length === 0 && !patientSearch" class="patient-dropdown-empty">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    No OPD patients today
                                                </div>
                                                <div v-if="filteredTodaysPatients.length === 0 && patientSearch" class="patient-dropdown-empty">
                                                    <i class="bi bi-search me-2"></i>
                                                    No patients found matching "{{ patientSearch }}"
                                                </div>
                                                <div v-if="filteredTodaysPatients.length > 0" class="patient-dropdown-footer">
                                                    Showing {{ filteredTodaysPatients.length }} of {{ todaysOpdPatients.length }} patients
                                                </div>
                                            </div>
                                        </div>
                                        <router-link
                                            :to="{ path: '/patients/create', query: { returnTo: $route.fullPath } }"
                                            class="btn btn-primary d-flex align-items-center"
                                            style="white-space: nowrap;"
                                            title="Add New Patient">
                                            <i class="bi bi-plus-lg me-1"></i> Add Patient
                                        </router-link>
                                    </div>
                                    <input type="hidden" v-model="form.patient_id" required>
                                </div>
                                <div v-if="selectedPatient" class="alert alert-info mb-3 py-2">
                                    <small>
                                        <strong>Selected:</strong> {{ selectedPatient.first_name }} {{ selectedPatient.last_name }} |
                                        <strong>Mobile:</strong> {{ selectedPatient.mobile || 'N/A' }} |
                                        <strong>Gender:</strong> {{ selectedPatient.gender || 'N/A' }} |
                                        <strong>Age:</strong> {{ selectedPatient.age_display || 'N/A' }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Admission Details -->
                        <div class="card mb-4">
                            <div class="card-header">Admission Details</div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Admission Date *</label>
                                        <input type="date" class="form-control" v-model="form.admission_date" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Admission Time</label>
                                        <input type="time" class="form-control" v-model="form.admission_time">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Admission Type *</label>
                                        <select class="form-select" v-model="form.admission_type" required>
                                            <option value="elective">Elective</option>
                                            <option value="emergency">Emergency</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Source</label>
                                        <select class="form-select" v-model="form.admission_source">
                                            <option value="direct">Direct</option>
                                            <option value="opd">From OPD</option>
                                            <option value="emergency">Emergency</option>
                                            <option value="transfer">Transfer</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Department *</label>
                                        <select class="form-select" v-model="form.department_id" required @change="loadDepartmentDoctors">
                                            <option value="">Select Department</option>
                                            <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                                {{ dept.department_name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Treating Doctor *</label>
                                        <select class="form-select" v-model="form.treating_doctor_id" required>
                                            <option value="">Select Doctor</option>
                                            <option v-for="doc in filteredDoctors" :key="doc.doctor_id" :value="doc.doctor_id">
                                                {{ doc.full_name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Provisional Diagnosis</label>
                                        <textarea class="form-control" v-model="form.provisional_diagnosis" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Treatment Plan</label>
                                        <textarea class="form-control" v-model="form.treatment_plan" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- MLC Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" v-model="form.is_mlc">
                                    <label class="form-check-label">MLC Case (Medico-Legal Case)</label>
                                </div>
                            </div>
                            <div class="card-body" v-if="form.is_mlc">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">MLC Number</label>
                                        <input type="text" class="form-control" v-model="form.mlc_number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Police Station</label>
                                        <input type="text" class="form-control" v-model="form.police_station">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Brought By</label>
                                        <input type="text" class="form-control" v-model="form.brought_by">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Insurance Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" v-model="form.insurance_applicable">
                                    <label class="form-check-label">Insurance / TPA Applicable</label>
                                </div>
                            </div>
                            <div class="card-body" v-if="form.insurance_applicable">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Scheme Type</label>
                                        <select class="form-select" v-model="form.scheme_type">
                                            <option value="none">None</option>
                                            <option value="ayushman">Ayushman Bharat</option>
                                            <option value="cghs">CGHS</option>
                                            <option value="esi">ESI</option>
                                            <option value="state_scheme">State Scheme</option>
                                            <option value="corporate">Corporate</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Insurance Company</label>
                                        <select class="form-select" v-model="form.insurance_company">
                                            <option value="">Select Insurance Company</option>
                                            <option v-for="company in insuranceCompanies" :key="company.insurance_id" :value="company.company_name">
                                                {{ company.company_name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">TPA Name</label>
                                        <input type="text" class="form-control" v-model="form.tpa_name">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Policy Number</label>
                                        <input type="text" class="form-control" v-model="form.policy_number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pre-Auth Amount</label>
                                        <input type="number" class="form-control" v-model="form.pre_auth_amount">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Credit Limit</label>
                                        <input type="number" class="form-control" v-model="form.credit_limit">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Responsible Person / Attendant -->
                        <div class="card mb-4">
                            <div class="card-header">Responsible Person / Attendant Information</div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Attendant Name</label>
                                        <input type="text" class="form-control" v-model="form.attendant_name" placeholder="Enter attendant name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Relation with Patient</label>
                                        <select class="form-select" v-model="form.attendant_relation">
                                            <option value="">Select Relation</option>
                                            <option value="Father">Father</option>
                                            <option value="Mother">Mother</option>
                                            <option value="Spouse">Spouse</option>
                                            <option value="Son">Son</option>
                                            <option value="Daughter">Daughter</option>
                                            <option value="Brother">Brother</option>
                                            <option value="Sister">Sister</option>
                                            <option value="Guardian">Guardian</option>
                                            <option value="Friend">Friend</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Attendant Mobile</label>
                                        <input type="tel" class="form-control" v-model="form.attendant_mobile" placeholder="Enter mobile number">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="form-label">Attendant Address</label>
                                        <textarea class="form-control" v-model="form.attendant_address" rows="2" placeholder="Enter complete address"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Attendant Email</label>
                                        <input type="email" class="form-control" v-model="form.attendant_email" placeholder="Enter email address">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Ward & Bed Selection -->
                        <div class="card mb-4">
                            <div class="card-header">Ward & Bed Selection *</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Ward</label>
                                    <select class="form-select" v-model="form.ward_id" @change="loadAvailableBeds" required>
                                        <option value="">Select Ward</option>
                                        <option v-for="ward in wards" :key="ward.ward_id" :value="ward.ward_id">
                                            {{ ward.ward_name }} ({{ ward.ward_type }})
                                        </option>
                                    </select>
                                </div>
                                <div v-if="bedsByRoom.length > 0">
                                    <label class="form-label">Available Beds by Room</label>
                                    <div class="beds-by-room">
                                        <div v-for="roomGroup in bedsByRoom" :key="roomGroup.room_id" class="room-group mb-3">
                                            <div class="room-header">
                                                <i class="bi bi-door-closed me-1"></i>
                                                <strong>{{ roomGroup.room_name }}</strong>
                                                <span class="badge bg-info ms-2">{{ roomGroup.room_type }}</span>
                                                <small class="text-muted ms-2">({{ roomGroup.beds.length }} beds available)</small>
                                            </div>
                                            <div class="bed-selection mt-2">
                                                <div v-for="bed in roomGroup.beds" :key="bed.bed_id"
                                                     :class="['bed-option', { selected: form.bed_id === bed.bed_id }]"
                                                     @click="form.bed_id = bed.bed_id">
                                                    <div class="bed-number">{{ bed.bed_number }}</div>
                                                    <small v-if="bed.charges_per_day" class="text-muted">₹{{ bed.charges_per_day }}/day</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else-if="form.ward_id" class="alert alert-warning mb-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    No beds available in this ward
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" :disabled="submitting || !canSubmit">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                Admit Patient
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Modals -->
        <!-- Add Progress Note Modal -->
        <div class="modal fade" :class="{ show: showAddNote }" :style="{ display: showAddNote ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Progress Note</h5>
                        <button type="button" class="btn-close" @click="showAddNote = false"></button>
                    </div>
                    <form @submit.prevent="saveProgressNote">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Note Type</label>
                                <select class="form-select" v-model="noteForm.note_type" required>
                                    <option value="round">Daily Round</option>
                                    <option value="consultation">Consultation</option>
                                    <option value="procedure">Procedure</option>
                                    <option value="handover">Handover</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subjective (Patient's complaints)</label>
                                <textarea class="form-control" v-model="noteForm.subjective" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Objective (Examination findings)</label>
                                <textarea class="form-control" v-model="noteForm.objective" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assessment</label>
                                <textarea class="form-control" v-model="noteForm.assessment" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Plan</label>
                                <textarea class="form-control" v-model="noteForm.plan" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Instructions</label>
                                <textarea class="form-control" v-model="noteForm.instructions" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showAddNote = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Note</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Advance Payment Modal -->
        <div class="modal fade" :class="{ show: showAdvancePayment }" :style="{ display: showAdvancePayment ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingAdvanceId ? 'Edit Advance Payment' : 'Collect Advance Payment' }}</h5>
                        <button type="button" class="btn-close" @click="closeAdvanceModal"></button>
                    </div>
                    <form @submit.prevent="saveAdvancePayment">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Amount *</label>
                                <input type="number" class="form-control" v-model="advanceForm.amount" required min="1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Mode *</label>
                                <select class="form-select" v-model="advanceForm.payment_mode" required>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="upi">UPI</option>
                                    <option value="neft">NEFT</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" v-model="advanceForm.reference_number">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" v-model="advanceForm.remarks" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showAdvancePayment = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Collect Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Discharge Modal -->
        <div class="modal fade" :class="{ show: showDischargeModal }" :style="{ display: showDischargeModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Discharge Patient</h5>
                        <button type="button" class="btn-close" @click="showDischargeModal = false"></button>
                    </div>
                    <form @submit.prevent="completeDischarge">
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Discharge Type *</label>
                                    <select class="form-select" v-model="dischargeForm.discharge_type" required>
                                        <option value="normal">Normal Discharge</option>
                                        <option value="lama">LAMA (Left Against Medical Advice)</option>
                                        <option value="dor">DOR (Discharge on Request)</option>
                                        <option value="referred">Referred to Another Hospital</option>
                                        <option value="absconded">Absconded</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Condition at Discharge</label>
                                    <select class="form-select" v-model="dischargeForm.condition_at_discharge">
                                        <option value="improved">Improved</option>
                                        <option value="cured">Cured</option>
                                        <option value="same">Same</option>
                                        <option value="deteriorated">Deteriorated</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Discharge Summary *</label>
                                <textarea class="form-control" v-model="dischargeForm.discharge_summary" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Follow-up Advice</label>
                                <textarea class="form-control" v-model="dischargeForm.followup_advice" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Follow-up Date</label>
                                <input type="date" class="form-control" v-model="dischargeForm.followup_date">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showDischargeModal = false">Cancel</button>
                            <button type="submit" class="btn btn-success">Complete Discharge</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Nursing Chart Modal -->
        <div class="modal fade" :class="{ show: showAddNursingChart }" :style="{ display: showAddNursingChart ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Nursing Chart Entry</h5>
                        <button type="button" class="btn-close" @click="showAddNursingChart = false"></button>
                    </div>
                    <form @submit.prevent="saveNursingChart">
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Date *</label>
                                    <input type="date" class="form-control" v-model="nursingChartForm.chart_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Shift *</label>
                                    <select class="form-select" v-model="nursingChartForm.shift" required>
                                        <option value="morning">Morning</option>
                                        <option value="evening">Evening</option>
                                        <option value="night">Night</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">BP Systolic</label>
                                    <input type="number" class="form-control" v-model="nursingChartForm.bp_systolic" placeholder="120">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">BP Diastolic</label>
                                    <input type="number" class="form-control" v-model="nursingChartForm.bp_diastolic" placeholder="80">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Pulse (bpm)</label>
                                    <input type="number" class="form-control" v-model="nursingChartForm.pulse" placeholder="72">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Temp (°F)</label>
                                    <input type="number" step="0.1" class="form-control" v-model="nursingChartForm.temperature" placeholder="98.6">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">SpO2 (%)</label>
                                    <input type="number" class="form-control" v-model="nursingChartForm.spo2" placeholder="98">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Respiratory Rate</label>
                                    <input type="number" class="form-control" v-model="nursingChartForm.respiratory_rate" placeholder="16">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Blood Sugar</label>
                                    <input type="number" class="form-control" v-model="nursingChartForm.blood_sugar" placeholder="100">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Oral Intake (ml)</label>
                                    <input type="number" class="form-control" v-model="nursingChartForm.oral_intake_ml" placeholder="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">IV Intake (ml)</label>
                                    <input type="number" class="form-control" v-model="nursingChartForm.iv_intake_ml" placeholder="0">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Urine Output (ml)</label>
                                    <input type="number" class="form-control" v-model="nursingChartForm.urine_output_ml" placeholder="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Other Output (ml)</label>
                                    <input type="number" class="form-control" v-model="nursingChartForm.other_output_ml" placeholder="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nursing Notes</label>
                                <textarea class="form-control" v-model="nursingChartForm.nursing_notes" rows="3" placeholder="Enter observations and notes..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showAddNursingChart = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Entry</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Medication Modal -->
        <div class="modal fade" :class="{ show: showAddMedication }" :style="{ display: showAddMedication ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Medication Order</h5>
                        <button type="button" class="btn-close" @click="showAddMedication = false"></button>
                    </div>
                    <form @submit.prevent="saveMedication">
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Drug Name *</label>
                                    <select class="form-select" v-model="medicationForm.drug_name" required>
                                        <option value="">Select Drug</option>
                                        <option v-for="drug in drugs" :key="drug.drug_id" :value="drug.drug_name">
                                            {{ drug.drug_name }} {{ drug.generic_name ? '(' + drug.generic_name + ')' : '' }}
                                        </option>
                                    </select>
                                    <small class="text-muted">Can't find the drug? Add it from Masters > Pharmacy > Drugs</small>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Dosage *</label>
                                    <input type="text" class="form-control" v-model="medicationForm.dosage" required placeholder="e.g., 500mg">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Route *</label>
                                    <select class="form-select" v-model="medicationForm.route" required>
                                        <option value="">Select Route</option>
                                        <option value="oral">Oral</option>
                                        <option value="iv">IV (Intravenous)</option>
                                        <option value="im">IM (Intramuscular)</option>
                                        <option value="sc">SC (Subcutaneous)</option>
                                        <option value="topical">Topical</option>
                                        <option value="inhalation">Inhalation</option>
                                        <option value="pr">PR (Per Rectum)</option>
                                        <option value="sl">SL (Sublingual)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Frequency *</label>
                                    <input type="text" class="form-control" v-model="medicationForm.frequency" required placeholder="e.g., TDS, BD, OD">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Duration (days)</label>
                                    <input type="number" class="form-control" v-model="medicationForm.duration_days" placeholder="7">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Instructions</label>
                                    <input type="text" class="form-control" v-model="medicationForm.instructions" placeholder="e.g., Take after food">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" v-model="medicationForm.remarks" rows="2" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showAddMedication = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Medication</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Service Modal -->
        <div class="modal fade" :class="{ show: showAddService }" :style="{ display: showAddService ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingServiceId ? 'Edit Service / Charge' : 'Add Services / Charges' }}</h5>
                        <button type="button" class="btn-close" @click="closeServiceModal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Service Form -->
                        <div>
                            <div class="mb-3">
                                <label class="form-label">Service Date *</label>
                                <input type="date" class="form-control" v-model="serviceForm.service_date">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Doctor</label>
                                <select class="form-select" v-model="serviceForm.doctor_id">
                                    <option value="">-- Select Doctor (Optional) --</option>
                                    <option v-for="doctor in doctors" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                        {{ doctor.full_name }} - {{ doctor.specialization }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select from Hospital Services</label>
                                <select class="form-select" v-model="serviceForm.hospital_service_id" @change="onHospitalServiceChange">
                                    <option value="">-- Select Service (or enter manually below) --</option>
                                    <option v-for="hs in hospitalServices" :key="hs.hospital_service_id" :value="hs.hospital_service_id">
                                        {{ hs.service_name }} - Rs {{ hs.applicable_price || hs.base_price }}
                                        <template v-if="hs.price_source === 'bed'">(Bed Rate)</template>
                                        <template v-else-if="hs.price_source === 'room'">(Room Rate)</template>
                                        <template v-else>(Base Rate)</template>
                                        - {{ hs.cost_head_name }}
                                    </option>
                                </select>
                                <small class="text-muted">Showing bed-specific or room-specific rates when available</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Service Name *</label>
                                <input type="text" class="form-control" v-model="serviceForm.service_name" placeholder="Enter service name">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Quantity *</label>
                                    <input type="number" class="form-control" v-model="serviceForm.quantity" min="1" @input="calculateServiceAmount">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Rate (Rs) *</label>
                                    <input type="number" class="form-control" v-model="serviceForm.rate" step="0.01" min="0" @input="calculateServiceAmount">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Discount (Rs)</label>
                                    <input type="number" class="form-control" v-model="serviceForm.discount" step="0.01" min="0" @input="calculateServiceAmount">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Total Amount</label>
                                    <input type="text" class="form-control" :value="'Rs ' + serviceForm.total_amount" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="isPackage" v-model="serviceForm.is_package">
                                        <label class="form-check-label" for="isPackage">
                                            This is a package service
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Remarks</label>
                                    <input type="text" class="form-control" v-model="serviceForm.remarks" placeholder="Any additional notes...">
                                </div>
                            </div>
                            <div class="text-end">
                                <button v-if="editingServiceId" type="button" class="btn btn-primary" @click="saveService">
                                    <i class="bi bi-check-lg"></i> Update Service
                                </button>
                                <button v-else type="button" class="btn btn-success" @click="addServiceToList">
                                    <i class="bi bi-plus-lg"></i> Add to List
                                </button>
                            </div>
                        </div>

                        <!-- Services List (Only show in bulk add mode) -->
                        <div v-if="!editingServiceId && bulkServicesList.length > 0" class="mt-4">
                            <hr>
                            <h6>Services to be Added ({{ bulkServicesList.length }})</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Doctor</th>
                                            <th>Service Name</th>
                                            <th>Qty</th>
                                            <th>Rate</th>
                                            <th>Discount</th>
                                            <th>Total</th>
                                            <th width="80">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(svc, index) in bulkServicesList" :key="index">
                                            <td>{{ formatDate(svc.service_date) }}</td>
                                            <td>{{ svc.doctor_name || '-' }}</td>
                                            <td>{{ svc.service_name }}</td>
                                            <td>{{ svc.quantity }}</td>
                                            <td>Rs {{ svc.rate }}</td>
                                            <td>Rs {{ svc.discount }}</td>
                                            <td><strong>Rs {{ svc.total_amount }}</strong></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger" @click="removeFromBulkList(index)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="6" class="text-end">Grand Total:</th>
                                            <th colspan="2">Rs {{ bulkServicesTotal }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeServiceModal">Cancel</button>
                        <button v-if="!editingServiceId && bulkServicesList.length > 0" type="button" class="btn btn-primary" @click="saveBulkServices">
                            <i class="bi bi-save"></i> Save All Services ({{ bulkServicesList.length }})
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Investigation Modal -->
        <div class="modal fade" :class="{ show: showAddInvestigation }" :style="{ display: showAddInvestigation ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Order Investigation</h5>
                        <button type="button" class="btn-close" @click="showAddInvestigation = false"></button>
                    </div>
                    <form @submit.prevent="saveInvestigation">
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Investigation Type *</label>
                                    <select class="form-select" v-model="investigationForm.investigation_type" required>
                                        <option value="">Select Type</option>
                                        <option value="pathology">Pathology (Lab)</option>
                                        <option value="radiology">Radiology (Imaging)</option>
                                        <option value="procedure">Procedure</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Priority *</label>
                                    <select class="form-select" v-model="investigationForm.priority" required>
                                        <option value="routine">Routine</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="stat">STAT (Immediate)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Investigation Name *</label>
                                <input type="text" class="form-control" v-model="investigationForm.investigation_name" required placeholder="e.g., Complete Blood Count">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Clinical Notes / Indication</label>
                                <textarea class="form-control" v-model="investigationForm.clinical_notes" rows="2" placeholder="Enter clinical indication..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Special Instructions</label>
                                <textarea class="form-control" v-model="investigationForm.instructions" rows="2" placeholder="Special instructions for lab/radiology..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showAddInvestigation = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Order Investigation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Backdrop -->
        <div v-if="showAddNote || showAdvancePayment || showDischargeModal || showAddNursingChart || showAddMedication || showAddInvestigation || showAddService" class="modal-backdrop fade show"></div>
    </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

export default {
    name: 'IpdForm',
    setup() {
        const route = useRoute();
        const router = useRouter();

        const isViewMode = computed(() => !!route.params.id);
        const loading = ref(false);
        const submitting = ref(false);

        // Master data
        const departments = ref([]);
        const doctors = ref([]);
        const wards = ref([]);
        const availableBeds = ref([]);
        const insuranceCompanies = ref([]);
        const drugs = ref([]);

        // View mode data
        const admission = ref({});
        const runningBill = ref({});
        const progressNotes = ref([]);
        const nursingCharts = ref([]);
        const medications = ref([]);
        const investigations = ref([]);
        const services = ref([]);
        const advancePayments = ref([]);

        const activeTab = ref('overview');

        // Create mode data
        const patientSearch = ref('');
        const todaysOpdPatients = ref([]);
        const selectedPatient = ref(null);
        const showPatientDropdown = ref(false);

        // Computed property to filter today's patients based on search
        const filteredTodaysPatients = computed(() => {
            const search = patientSearch.value.toLowerCase().trim();
            if (!search) {
                return todaysOpdPatients.value;
            }
            return todaysOpdPatients.value.filter(p =>
                (p.first_name?.toLowerCase().includes(search)) ||
                (p.last_name?.toLowerCase().includes(search)) ||
                (p.patient_id?.toString().includes(search)) ||
                (p.mobile?.includes(search))
            );
        });

        const form = ref({
            patient_id: null,
            admission_date: new Date().toISOString().split('T')[0],
            admission_time: new Date().toTimeString().slice(0, 5),
            admission_type: 'elective',
            admission_source: '',
            department_id: '',
            treating_doctor_id: '',
            ward_id: '',
            bed_id: '',
            provisional_diagnosis: '',
            treatment_plan: '',
            is_mlc: false,
            mlc_number: '',
            police_station: '',
            brought_by: '',
            insurance_applicable: false,
            scheme_type: 'none',
            insurance_company: '',
            tpa_name: '',
            policy_number: '',
            pre_auth_amount: 0,
            credit_limit: 0,
            attendant_name: '',
            attendant_relation: '',
            attendant_mobile: '',
            attendant_address: '',
            attendant_email: '',
        });

        // Modals
        const showAddNote = ref(false);
        const showAdvancePayment = ref(false);
        const showDischargeModal = ref(false);
        const showTransferBed = ref(false);
        const showAddService = ref(false);
        const showAddMedication = ref(false);
        const showAddInvestigation = ref(false);
        const showAddNursingChart = ref(false);
        const showEditAdmission = ref(false);

        // Form data for modals
        const noteForm = ref({
            note_type: 'round',
            subjective: '',
            objective: '',
            assessment: '',
            plan: '',
            instructions: '',
        });

        const advanceForm = ref({
            amount: '',
            payment_mode: 'cash',
            reference_number: '',
            remarks: '',
        });

        const dischargeForm = ref({
            discharge_type: 'normal',
            condition_at_discharge: 'improved',
            discharge_summary: '',
            followup_advice: '',
            followup_date: '',
        });

        const nursingChartForm = ref({
            chart_date: new Date().toISOString().split('T')[0],
            shift: 'morning',
            bp_systolic: '',
            bp_diastolic: '',
            pulse: '',
            temperature: '',
            spo2: '',
            respiratory_rate: '',
            blood_sugar: '',
            oral_intake_ml: '',
            iv_intake_ml: '',
            urine_output_ml: '',
            other_output_ml: '',
            nursing_notes: '',
        });

        const medicationForm = ref({
            drug_name: '',
            dosage: '',
            route: '',
            frequency: '',
            duration_days: '',
            instructions: '',
            remarks: '',
        });

        const investigationForm = ref({
            investigation_type: '',
            investigation_name: '',
            priority: 'routine',
            clinical_notes: '',
            instructions: '',
        });

        const serviceForm = ref({
            service_date: new Date().toISOString().split('T')[0],
            doctor_id: '',
            service_type: '',
            hospital_service_id: '',
            service_name: '',
            quantity: 1,
            rate: 0,
            discount: 0,
            total_amount: 0,
            is_package: false,
            remarks: '',
        });

        const hospitalServices = ref([]);
        const editingServiceId = ref(null);
        const editingAdvanceId = ref(null);
        const bulkServicesList = ref([]);

        const bulkServicesTotal = computed(() => {
            return bulkServicesList.value.reduce((sum, svc) => sum + parseFloat(svc.total_amount || 0), 0).toFixed(2);
        });

        const filteredDoctors = computed(() => {
            if (!form.value.department_id) return doctors.value;
            return doctors.value.filter(d => d.department_id == form.value.department_id);
        });

        const canSubmit = computed(() => {
            return selectedPatient.value &&
                   form.value.admission_date &&
                   form.value.department_id &&
                   form.value.treating_doctor_id &&
                   form.value.ward_id &&
                   form.value.bed_id;
        });

        const bedsByRoom = computed(() => {
            if (!availableBeds.value || availableBeds.value.length === 0) return [];

            // Group beds by room
            const roomsMap = new Map();

            availableBeds.value.forEach(bed => {
                if (bed.room) {
                    if (!roomsMap.has(bed.room.room_id)) {
                        roomsMap.set(bed.room.room_id, {
                            room_id: bed.room.room_id,
                            room_name: bed.room.room_name,
                            room_type: bed.room.room_type || 'General',
                            beds: []
                        });
                    }
                    roomsMap.get(bed.room.room_id).beds.push(bed);
                }
            });

            return Array.from(roomsMap.values());
        });

        const loadMasterData = async () => {
            try {
                const [deptsRes, docsRes, wardsRes, insuranceRes, drugsRes, servicesRes] = await Promise.all([
                    axios.get('/api/departments'),
                    axios.get('/api/doctors'),
                    axios.get('/api/wards'),
                    axios.get('/api/insurance-companies-active'),
                    axios.get('/api/drugs'),
                    axios.get('/api/hospital-services', { params: { active_only: 1 } }),
                ]);
                departments.value = deptsRes.data.data || deptsRes.data;
                doctors.value = docsRes.data.data || docsRes.data;
                wards.value = wardsRes.data.data || wardsRes.data;
                insuranceCompanies.value = insuranceRes.data.data || insuranceRes.data;
                drugs.value = drugsRes.data || [];
                hospitalServices.value = servicesRes.data || [];

                // Load today's OPD patients if in create mode
                if (!isViewMode.value) {
                    await loadTodaysOpdPatients();

                    // Check if returning from patient creation with a new patient
                    if (route.query.patient_id) {
                        const patientId = parseInt(route.query.patient_id);
                        // Try to find in today's OPD patients first
                        let patient = todaysOpdPatients.value.find(p => p.patient_id === patientId);

                        // If not found, load the specific patient
                        if (!patient) {
                            try {
                                const response = await axios.get(`/api/patients/${patientId}`);
                                patient = response.data.data || response.data;
                                // Add to today's patients list
                                todaysOpdPatients.value.unshift(patient);
                            } catch (error) {
                                console.error('Failed to load newly created patient:', error);
                            }
                        }

                        if (patient) {
                            selectPatient(patient);
                        }
                    }
                }
            } catch (error) {
                console.error('Failed to load master data:', error);
            }
        };

        const loadAdmission = async () => {
            if (!route.params.id) return;
            loading.value = true;
            try {
                const response = await axios.get(`/api/ipd-admissions/${route.params.id}`);
                admission.value = response.data.admission;
                runningBill.value = response.data.running_bill || {};

                // Load hospital services with bed/room prices
                await loadHospitalServicesForAdmission();

                // Load related data
                await Promise.all([
                    loadProgressNotes(),
                    loadNursingCharts(),
                    loadMedications(),
                    loadInvestigations(),
                    loadServices(),
                    loadAdvancePayments(),
                ]);
            } catch (error) {
                console.error('Failed to load admission:', error);
                alert('Failed to load admission details');
            } finally {
                loading.value = false;
            }
        };

        const loadProgressNotes = async () => {
            try {
                const response = await axios.get(`/api/ipd-admissions/${route.params.id}/progress-notes`);
                progressNotes.value = response.data.data || response.data;
            } catch (error) {
                console.error('Failed to load progress notes:', error);
            }
        };

        const loadNursingCharts = async () => {
            try {
                const response = await axios.get(`/api/ipd-admissions/${route.params.id}/nursing-charts`);
                nursingCharts.value = response.data.data || response.data;
            } catch (error) {
                console.error('Failed to load nursing charts:', error);
            }
        };

        const loadMedications = async () => {
            try {
                const response = await axios.get(`/api/ipd-admissions/${route.params.id}/medications`);
                medications.value = response.data;
            } catch (error) {
                console.error('Failed to load medications:', error);
            }
        };

        const loadInvestigations = async () => {
            try {
                const response = await axios.get(`/api/ipd-admissions/${route.params.id}/investigations`);
                investigations.value = response.data;
            } catch (error) {
                console.error('Failed to load investigations:', error);
            }
        };

        const loadServices = async () => {
            try {
                const response = await axios.get(`/api/ipd-admissions/${route.params.id}/services`);
                services.value = response.data.data || response.data;
            } catch (error) {
                console.error('Failed to load services:', error);
            }
        };

        const loadAdvancePayments = async () => {
            try {
                const response = await axios.get(`/api/ipd-admissions/${route.params.id}/advance-payments`);
                advancePayments.value = response.data.payments || [];
            } catch (error) {
                console.error('Failed to load advance payments:', error);
            }
        };

        const loadRunningBill = async () => {
            try {
                const response = await axios.get(`/api/ipd-admissions/${route.params.id}/running-bill`);
                runningBill.value = response.data;
            } catch (error) {
                console.error('Failed to load running bill:', error);
            }
        };

        const loadTodaysOpdPatients = async () => {
            try {
                const response = await axios.get('/api/opd-visits', {
                    params: {
                        date: new Date().toISOString().split('T')[0]
                    }
                });

                // The API returns {visits: [...], summary: {...}}
                const visits = response.data.visits || [];

                // Extract unique patients from today's OPD visits
                const patientIds = new Set();
                const patients = [];

                visits.forEach(visit => {
                    if (visit.patient && !patientIds.has(visit.patient.patient_id)) {
                        patientIds.add(visit.patient.patient_id);
                        patients.push(visit.patient);
                    }
                });

                todaysOpdPatients.value = patients;
                console.log('Today\'s OPD patients loaded:', patients.length);
            } catch (error) {
                console.error('Failed to load today\'s OPD patients:', error);
                todaysOpdPatients.value = [];
            }
        };

        const getPatientColor = (patient) => {
            // Color coding based on age groups
            const age = parseInt(patient.age_years || patient.age || 0);

            if (age < 12) {
                return '#FF6B9D'; // Pink for pediatric (children)
            } else if (age >= 12 && age < 18) {
                return '#4ECDC4'; // Teal for adolescent
            } else if (age >= 18 && age < 60) {
                return '#45B7D1'; // Blue for adults
            } else if (age >= 60) {
                return '#FFA07A'; // Orange for seniors
            }
            return '#95E1D3'; // Default mint green
        };

        const getGenderBadgeClass = (gender) => {
            if (!gender) return 'bg-secondary';
            const genderLower = gender.toLowerCase();
            if (genderLower === 'male' || genderLower === 'm') {
                return 'bg-primary';
            } else if (genderLower === 'female' || genderLower === 'f') {
                return 'bg-danger';
            }
            return 'bg-info';
        };

        const selectPatient = (patient) => {
            selectedPatient.value = patient;
            form.value.patient_id = patient.patient_id;
            patientSearch.value = `${patient.first_name} ${patient.last_name}`;
            showPatientDropdown.value = false;
        };

        const clearPatient = () => {
            selectedPatient.value = null;
            form.value.patient_id = null;
            patientSearch.value = '';
            showPatientDropdown.value = false;
        };

        const hidePatientDropdown = () => {
            setTimeout(() => {
                showPatientDropdown.value = false;
            }, 200);
        };

        const loadAvailableBeds = async () => {
            if (!form.value.ward_id) {
                availableBeds.value = [];
                return;
            }
            try {
                const response = await axios.get(`/api/ipd-admissions-available-beds/${form.value.ward_id}`);
                availableBeds.value = response.data;
            } catch (error) {
                console.error('Failed to load available beds:', error);
            }
        };

        const loadDepartmentDoctors = () => {
            form.value.treating_doctor_id = '';
        };

        const submitAdmission = async () => {
            if (!canSubmit.value) return;
            submitting.value = true;
            try {
                const response = await axios.post('/api/ipd-admissions', form.value);
                alert('Patient admitted successfully!');
                router.push(`/ipd/${response.data.admission.ipd_id}`);
            } catch (error) {
                alert('Failed to create admission: ' + (error.response?.data?.message || error.message));
            } finally {
                submitting.value = false;
            }
        };

        const saveProgressNote = async () => {
            try {
                await axios.post(`/api/ipd-admissions/${route.params.id}/progress-notes`, noteForm.value);
                showAddNote.value = false;
                noteForm.value = { note_type: 'round', subjective: '', objective: '', assessment: '', plan: '', instructions: '' };
                loadProgressNotes();
            } catch (error) {
                alert('Failed to save note: ' + (error.response?.data?.message || error.message));
            }
        };

        const editAdvancePayment = (payment) => {
            editingAdvanceId.value = payment.advance_id;
            advanceForm.value = {
                amount: payment.amount,
                payment_mode: payment.payment_mode,
                reference_number: payment.reference_number || '',
                remarks: payment.remarks || '',
            };
            showAdvancePayment.value = true;
        };

        const deleteAdvancePayment = async (advanceId) => {
            if (!confirm('Are you sure you want to delete this advance payment?')) {
                return;
            }

            try {
                await axios.delete(`/api/ipd-admissions/${route.params.id}/advance-payments/${advanceId}`);
                loadAdvancePayments();
                loadRunningBill();
            } catch (error) {
                alert('Failed to delete advance payment: ' + (error.response?.data?.message || error.message));
            }
        };

        const closeAdvanceModal = () => {
            showAdvancePayment.value = false;
            editingAdvanceId.value = null;
            advanceForm.value = { amount: '', payment_mode: 'cash', reference_number: '', remarks: '' };
        };

        const saveAdvancePayment = async () => {
            try {
                if (editingAdvanceId.value) {
                    // Update existing advance payment
                    await axios.put(`/api/ipd-admissions/${route.params.id}/advance-payments/${editingAdvanceId.value}`, advanceForm.value);
                } else {
                    // Create new advance payment
                    await axios.post(`/api/ipd-admissions/${route.params.id}/advance-payments`, advanceForm.value);
                }

                closeAdvanceModal();
                loadAdvancePayments();
                loadRunningBill();
            } catch (error) {
                alert('Failed to save payment: ' + (error.response?.data?.message || error.message));
            }
        };

        const completeDischarge = async () => {
            if (!confirm('Are you sure you want to discharge this patient?')) return;
            try {
                await axios.post(`/api/ipd-admissions/${route.params.id}/complete-discharge`, dischargeForm.value);
                showDischargeModal.value = false;
                alert('Patient discharged successfully!');
                router.push('/ipd');
            } catch (error) {
                alert('Failed to discharge: ' + (error.response?.data?.message || error.message));
            }
        };

        const stopMedication = async (medication) => {
            if (!confirm('Stop this medication?')) return;
            try {
                await axios.put(`/api/ipd-admissions/${route.params.id}/medications/${medication.medication_id}`, { status: 'stopped' });
                loadMedications();
            } catch (error) {
                alert('Failed to stop medication: ' + (error.response?.data?.message || error.message));
            }
        };

        const saveNursingChart = async () => {
            try {
                await axios.post(`/api/ipd-admissions/${route.params.id}/nursing-charts`, nursingChartForm.value);
                showAddNursingChart.value = false;
                nursingChartForm.value = {
                    chart_date: new Date().toISOString().split('T')[0],
                    shift: 'morning',
                    bp_systolic: '',
                    bp_diastolic: '',
                    pulse: '',
                    temperature: '',
                    spo2: '',
                    respiratory_rate: '',
                    blood_sugar: '',
                    oral_intake_ml: '',
                    iv_intake_ml: '',
                    urine_output_ml: '',
                    other_output_ml: '',
                    nursing_notes: '',
                };
                loadNursingCharts();
            } catch (error) {
                alert('Failed to save nursing chart: ' + (error.response?.data?.message || error.message));
            }
        };

        const saveMedication = async () => {
            try {
                await axios.post(`/api/ipd-admissions/${route.params.id}/medications`, medicationForm.value);
                showAddMedication.value = false;
                medicationForm.value = {
                    drug_name: '',
                    dosage: '',
                    route: '',
                    frequency: '',
                    duration_days: '',
                    instructions: '',
                    remarks: '',
                };
                loadMedications();
            } catch (error) {
                alert('Failed to add medication: ' + (error.response?.data?.message || error.message));
            }
        };

        const saveInvestigation = async () => {
            try {
                await axios.post(`/api/ipd-admissions/${route.params.id}/investigations`, investigationForm.value);
                showAddInvestigation.value = false;
                investigationForm.value = {
                    investigation_type: '',
                    investigation_name: '',
                    priority: 'routine',
                    clinical_notes: '',
                    instructions: '',
                };
                loadInvestigations();
            } catch (error) {
                alert('Failed to order investigation: ' + (error.response?.data?.message || error.message));
            }
        };

        const onHospitalServiceChange = () => {
            const selectedService = hospitalServices.value.find(s => s.hospital_service_id == serviceForm.value.hospital_service_id);
            if (selectedService) {
                serviceForm.value.service_name = selectedService.service_name;
                // Use applicable_price if available (bed > room > base), otherwise fallback to base_price
                serviceForm.value.rate = parseFloat(selectedService.applicable_price || selectedService.base_price || 0);

                // Auto-set service type based on cost head type
                const costHeadType = selectedService.cost_head_type;
                if (costHeadType === 'ipd_services') {
                    serviceForm.value.service_type = 'bed';
                } else if (costHeadType === 'opd_services') {
                    serviceForm.value.service_type = 'doctor_visit';
                } else if (costHeadType === 'lab_services') {
                    serviceForm.value.service_type = 'lab';
                } else if (costHeadType === 'radiology') {
                    serviceForm.value.service_type = 'radiology';
                } else if (costHeadType === 'pharmacy') {
                    serviceForm.value.service_type = 'pharmacy';
                } else if (costHeadType === 'procedure') {
                    serviceForm.value.service_type = 'procedure';
                } else {
                    serviceForm.value.service_type = 'other';
                }

                calculateServiceAmount();
            }
        };

        const calculateServiceAmount = () => {
            const quantity = parseFloat(serviceForm.value.quantity) || 0;
            const rate = parseFloat(serviceForm.value.rate) || 0;
            const discount = parseFloat(serviceForm.value.discount) || 0;

            const subtotal = quantity * rate;
            serviceForm.value.total_amount = Math.max(0, subtotal - discount).toFixed(2);
        };

        const editServiceItem = (service) => {
            editingServiceId.value = service.ipd_service_id;
            serviceForm.value = {
                service_date: service.service_date,
                doctor_id: service.doctor_id || '',
                service_type: service.service_type,
                hospital_service_id: service.service_id || '',
                service_name: service.service_name,
                quantity: service.quantity,
                rate: service.rate,
                discount: service.discount || 0,
                total_amount: service.net_amount,
                is_package: service.is_package || false,
                remarks: service.remarks || '',
            };
            calculateServiceAmount();
            showAddService.value = true;
        };

        const deleteServiceItem = async (serviceId) => {
            if (!confirm('Are you sure you want to delete this service?')) {
                return;
            }

            try {
                await axios.delete(`/api/ipd-admissions/${route.params.id}/services/${serviceId}`);
                await loadServices();
                await loadAdmission();
            } catch (error) {
                alert('Failed to delete service: ' + (error.response?.data?.message || error.message));
            }
        };

        const closeServiceModal = () => {
            showAddService.value = false;
            editingServiceId.value = null;
            bulkServicesList.value = [];
            serviceForm.value = {
                service_date: new Date().toISOString().split('T')[0],
                doctor_id: '',
                service_type: '',
                hospital_service_id: '',
                service_name: '',
                quantity: 1,
                rate: 0,
                discount: 0,
                total_amount: 0,
                is_package: false,
                remarks: '',
            };
        };

        const addServiceToList = () => {
            console.log('addServiceToList called', serviceForm.value);

            // Validate form
            if (!serviceForm.value.service_date || !serviceForm.value.service_name) {
                alert('Please fill all required fields (Service Date and Service Name)');
                return;
            }

            // If service_type is not set (manual entry), default to 'other'
            const serviceType = serviceForm.value.service_type || 'other';

            // Get doctor name for display
            let doctorName = '';
            if (serviceForm.value.doctor_id) {
                const selectedDoctor = doctors.value.find(d => d.doctor_id == serviceForm.value.doctor_id);
                doctorName = selectedDoctor ? selectedDoctor.full_name : '';
            }

            // Add to bulk list
            bulkServicesList.value.push({
                service_date: serviceForm.value.service_date,
                doctor_id: serviceForm.value.doctor_id || null,
                doctor_name: doctorName,
                service_type: serviceType,
                service_id: serviceForm.value.hospital_service_id || null,
                service_name: serviceForm.value.service_name,
                quantity: parseFloat(serviceForm.value.quantity) || 1,
                rate: parseFloat(serviceForm.value.rate) || 0,
                discount: parseFloat(serviceForm.value.discount) || 0,
                total_amount: parseFloat(serviceForm.value.total_amount) || 0,
                is_package: serviceForm.value.is_package || false,
                remarks: serviceForm.value.remarks || '',
            });

            console.log('Service added to bulk list', bulkServicesList.value);

            // Reset form for next entry (keep date and doctor_id)
            serviceForm.value.hospital_service_id = '';
            serviceForm.value.service_name = '';
            serviceForm.value.quantity = 1;
            serviceForm.value.rate = 0;
            serviceForm.value.discount = 0;
            serviceForm.value.total_amount = 0;
            serviceForm.value.is_package = false;
            serviceForm.value.remarks = '';
            serviceForm.value.service_type = '';
            // Keep service_date and doctor_id as is for faster bulk entry
        };

        const removeFromBulkList = (index) => {
            bulkServicesList.value.splice(index, 1);
        };

        const saveBulkServices = async () => {
            if (bulkServicesList.value.length === 0) {
                alert('No services to save');
                return;
            }

            try {
                // Save all services
                for (const service of bulkServicesList.value) {
                    await axios.post(`/api/ipd-admissions/${route.params.id}/services`, service);
                }

                closeServiceModal();
                await loadServices();
                await loadAdmission();
                alert(`Successfully added ${bulkServicesList.value.length} service(s)`);
            } catch (error) {
                alert('Failed to save services: ' + (error.response?.data?.message || error.message));
            }
        };

        const saveService = async () => {
            try {
                const payload = {
                    service_date: serviceForm.value.service_date,
                    doctor_id: serviceForm.value.doctor_id || null,
                    service_type: serviceForm.value.service_type,
                    service_id: serviceForm.value.hospital_service_id || null,
                    service_name: serviceForm.value.service_name,
                    quantity: serviceForm.value.quantity,
                    rate: serviceForm.value.rate,
                    discount: serviceForm.value.discount,
                    is_package: serviceForm.value.is_package,
                    remarks: serviceForm.value.remarks,
                };

                if (editingServiceId.value) {
                    // Update existing service
                    await axios.put(`/api/ipd-admissions/${route.params.id}/services/${editingServiceId.value}`, payload);
                } else {
                    // Create new service
                    await axios.post(`/api/ipd-admissions/${route.params.id}/services`, payload);
                }

                closeServiceModal();
                await loadServices();
                await loadAdmission();
            } catch (error) {
                alert('Failed to save service: ' + (error.response?.data?.message || error.message));
            }
        };

        const loadHospitalServices = async () => {
            try {
                const response = await axios.get('/api/hospital-services', {
                    params: { active_only: 1 }
                });
                hospitalServices.value = response.data || [];
            } catch (error) {
                console.error('Failed to load hospital services:', error);
            }
        };

        const loadHospitalServicesForAdmission = async () => {
            if (!admission.value || !admission.value.bed_id) {
                await loadHospitalServices();
                return;
            }

            try {
                // Get bed details to find room_id
                const bedResponse = await axios.get(`/api/beds/${admission.value.bed_id}`);
                const bed = bedResponse.data;

                // Load services with bed and room pricing
                const response = await axios.get('/api/hospital-services', {
                    params: {
                        active_only: 1,
                        bed_id: admission.value.bed_id,
                        room_id: bed.room_id
                    }
                });

                hospitalServices.value = response.data || [];

                console.log('Loaded hospital services with bed/room prices:', {
                    bed_id: admission.value.bed_id,
                    room_id: bed.room_id,
                    services_count: hospitalServices.value.length
                });
            } catch (error) {
                console.error('Failed to load hospital services with bed/room prices:', error);
                // Fallback to loading without bed/room pricing
                await loadHospitalServices();
            }
        };

        const formatDate = (date) => {
            if (!date) return '';
            return new Date(date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        };

        const formatStatus = (status) => {
            const statusMap = {
                admitted: 'Admitted',
                discharge_initiated: 'Discharge Initiated',
                discharged: 'Discharged',
                cancelled: 'Cancelled',
            };
            return statusMap[status] || status;
        };

        const getStatusBadge = (status) => {
            const badges = {
                admitted: 'badge bg-success',
                discharge_initiated: 'badge bg-warning',
                discharged: 'badge bg-secondary',
                cancelled: 'badge bg-danger',
            };
            return badges[status] || 'badge bg-secondary';
        };

        const getMedicationStatusBadge = (status) => {
            const badges = {
                ordered: 'badge bg-info',
                issued: 'badge bg-primary',
                administered: 'badge bg-success',
                stopped: 'badge bg-danger',
                completed: 'badge bg-secondary',
            };
            return badges[status] || 'badge bg-secondary';
        };

        const printDischargeSummary = () => {
            const hospitalId = localStorage.getItem('hospital_id') || 1;
            const url = `/print/discharge-summary/${route.params.id}?hospital_id=${hospitalId}`;
            window.open(url, '_blank');
        };

        const printCaseSheet = () => {
            const hospitalId = localStorage.getItem('hospital_id') || 1;
            const url = `/print/ipd-case-sheet/${route.params.id}?hospital_id=${hospitalId}`;
            window.open(url, '_blank');
        };

        onMounted(() => {
            loadMasterData();
            if (isViewMode.value) {
                loadAdmission();
            }
        });

        return {
            isViewMode,
            loading,
            submitting,
            departments,
            doctors,
            wards,
            availableBeds,
            insuranceCompanies,
            drugs,
            admission,
            runningBill,
            progressNotes,
            nursingCharts,
            medications,
            investigations,
            services,
            advancePayments,
            activeTab,
            patientSearch,
            todaysOpdPatients,
            filteredTodaysPatients,
            selectedPatient,
            showPatientDropdown,
            form,
            filteredDoctors,
            canSubmit,
            bedsByRoom,
            showAddNote,
            showAdvancePayment,
            showDischargeModal,
            showTransferBed,
            showAddService,
            showAddMedication,
            showAddInvestigation,
            showAddNursingChart,
            showEditAdmission,
            noteForm,
            advanceForm,
            dischargeForm,
            nursingChartForm,
            medicationForm,
            investigationForm,
            selectPatient,
            clearPatient,
            hidePatientDropdown,
            getPatientColor,
            getGenderBadgeClass,
            loadAvailableBeds,
            loadDepartmentDoctors,
            submitAdmission,
            saveProgressNote,
            saveAdvancePayment,
            completeDischarge,
            stopMedication,
            saveNursingChart,
            saveMedication,
            saveInvestigation,
            serviceForm,
            hospitalServices,
            editingServiceId,
            editingAdvanceId,
            bulkServicesList,
            bulkServicesTotal,
            onHospitalServiceChange,
            calculateServiceAmount,
            saveService,
            addServiceToList,
            removeFromBulkList,
            saveBulkServices,
            editServiceItem,
            deleteServiceItem,
            closeServiceModal,
            editAdvancePayment,
            deleteAdvancePayment,
            closeAdvanceModal,
            loadHospitalServices,
            loadHospitalServicesForAdmission,
            formatDate,
            formatStatus,
            getStatusBadge,
            getMedicationStatusBadge,
            printDischargeSummary,
            printCaseSheet,
        };
    },
};
</script>

<style scoped>
/* Beds by Room */
.beds-by-room {
    max-height: 500px;
    overflow-y: auto;
}

.room-group {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 12px;
    background-color: #f8f9fa;
}

.room-header {
    font-size: 0.95rem;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #dee2e6;
}

.bed-selection {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.bed-option {
    width: 70px;
    padding: 8px;
    border: 2px solid #28a745;
    border-radius: 6px;
    background-color: #d4edda;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.bed-option:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.bed-option.selected {
    border-color: #007bff;
    background-color: #cce5ff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
}

.bed-number {
    font-size: 1.1rem;
    font-weight: bold;
    color: #155724;
}

.bed-option.selected .bed-number {
    color: #004085;
}

.btn-xs {
    padding: 0.15rem 0.4rem;
    font-size: 0.75rem;
}

/* Clear Patient Button */
.btn-clear-patient {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 0;
    font-size: 1.2rem;
    line-height: 1;
    z-index: 10;
    transition: color 0.2s ease;
}

.btn-clear-patient:hover {
    color: #dc3545;
}

.btn-clear-patient i {
    display: block;
}

/* Patient Autocomplete Dropdown */
.patient-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: 0.25rem;
}

.dropdown-section-header {
    padding: 0.5rem 1rem;
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    border-bottom: 1px solid #dee2e6;
    position: sticky;
    top: 0;
    z-index: 1;
}

.opd-patients-section {
    border-bottom: 2px solid #dee2e6;
}

.patient-dropdown-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f3f5;
    cursor: pointer;
    transition: all 0.15s ease;
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
}

.patient-dropdown-item:last-child {
    border-bottom: none;
}

.patient-dropdown-item:hover {
    background-color: #f8f9fa;
    transform: translateX(3px);
}

.patient-dropdown-item.active {
    background-color: rgba(54, 153, 255, 0.1);
    border-left: 4px solid #3699ff;
}

.patient-color-indicator {
    width: 6px;
    height: 45px;
    border-radius: 3px;
    flex-shrink: 0;
}

.patient-info .patient-name {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.patient-info .patient-details {
    font-size: 0.875rem;
    color: #6c757d;
}

.patient-dropdown-footer {
    padding: 0.5rem 1rem;
    background-color: #f8f9fa;
    text-align: center;
    font-size: 0.75rem;
    color: #6c757d;
    border-top: 1px solid #dee2e6;
}

.patient-dropdown-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: #6c757d;
    font-size: 0.875rem;
}

.patient-info {
    flex: 1;
}
</style>
