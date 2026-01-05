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
                        <h5 class="mb-1">{{ admission.patient?.full_name }}</h5>
                        <small class="text-muted">
                            {{ admission.patient?.gender }} / {{ admission.patient?.age_display }} |
                            {{ admission.patient?.mobile }}
                        </small>
                    </div>
                    <div class="col-md-2 text-center border-start border-end">
                        <small class="text-muted d-block">Ward / Bed</small>
                        <strong>{{ admission.ward?.ward_name }} - {{ admission.bed?.bed_number }}</strong>
                    </div>
                    <div class="col-md-2 text-center border-end">
                        <small class="text-muted d-block">Doctor</small>
                        <strong>{{ admission.treating_doctor?.doctor_name }}</strong>
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
                            <div v-if="admission.insurance_applicable" class="alert alert-info mb-0">
                                <strong>Insurance:</strong> {{ admission.insurance_company }} ({{ admission.scheme_type }})<br>
                                <small>TPA: {{ admission.tpa_name }} | Pre-Auth: Rs {{ admission.pre_auth_amount }}</small>
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
                                        <strong>{{ note.doctor?.doctor_name }}</strong>
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
                                        <strong>{{ note.doctor?.doctor_name }}</strong>
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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="services.length === 0">
                                                <td colspan="6" class="text-center text-muted py-3">No services added</td>
                                            </tr>
                                            <tr v-for="svc in services" :key="svc.ipd_service_id">
                                                <td>{{ formatDate(svc.service_date) }}</td>
                                                <td>{{ svc.service_type }}</td>
                                                <td>{{ svc.service_name }}</td>
                                                <td>{{ svc.quantity }}</td>
                                                <td>Rs {{ svc.rate }}</td>
                                                <td>Rs {{ svc.net_amount }}</td>
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
                                        <div class="d-flex justify-content-between">
                                            <small>{{ pay.receipt_number }}</small>
                                            <small class="text-muted">{{ formatDate(pay.payment_date) }}</small>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>{{ pay.payment_mode }}</span>
                                            <strong>Rs {{ pay.amount }}</strong>
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
                                    <label class="form-label">Select Patient *</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" v-model="patientSearch"
                                               placeholder="Search by name, mobile, ID..." @keyup.enter="searchPatients">
                                        <button type="button" class="btn btn-primary" @click="searchPatients">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                                <div v-if="patientResults.length > 0" class="list-group mb-3">
                                    <a v-for="p in patientResults" :key="p.patient_id" href="#"
                                       class="list-group-item list-group-item-action" @click.prevent="selectPatient(p)">
                                        <div class="d-flex justify-content-between">
                                            <strong>{{ p.full_name }}</strong>
                                            <small class="text-muted">ID: {{ p.patient_id }}</small>
                                        </div>
                                        <small class="text-muted">{{ p.gender }} / {{ p.age_display }} | {{ p.mobile }}</small>
                                    </a>
                                </div>
                                <div v-if="selectedPatient" class="alert alert-success">
                                    <strong>Selected:</strong> {{ selectedPatient.full_name }}
                                    ({{ selectedPatient.gender }} / {{ selectedPatient.age_display }})
                                    <button type="button" class="btn-close float-end" @click="selectedPatient = null"></button>
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
                                                {{ doc.doctor_name }}
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
                                        <input type="text" class="form-control" v-model="form.insurance_company">
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
                                <div v-if="availableBeds.length > 0">
                                    <label class="form-label">Available Beds</label>
                                    <div class="bed-selection">
                                        <div v-for="bed in availableBeds" :key="bed.bed_id"
                                             :class="['bed-option', { selected: form.bed_id === bed.bed_id }]"
                                             @click="form.bed_id = bed.bed_id">
                                            <div class="bed-number">{{ bed.bed_number }}</div>
                                            <small>{{ bed.bed_type }}</small>
                                            <small class="text-muted">Rs {{ bed.charges_per_day }}/day</small>
                                        </div>
                                    </div>
                                </div>
                                <div v-else-if="form.ward_id" class="alert alert-warning mb-0">
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
                        <h5 class="modal-title">Collect Advance Payment</h5>
                        <button type="button" class="btn-close" @click="showAdvancePayment = false"></button>
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

        <!-- Modal Backdrop -->
        <div v-if="showAddNote || showAdvancePayment || showDischargeModal" class="modal-backdrop fade show"></div>
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
        const patientResults = ref([]);
        const selectedPatient = ref(null);

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

        const loadMasterData = async () => {
            try {
                const [deptsRes, docsRes, wardsRes] = await Promise.all([
                    axios.get('/api/departments'),
                    axios.get('/api/doctors'),
                    axios.get('/api/wards'),
                ]);
                departments.value = deptsRes.data.data || deptsRes.data;
                doctors.value = docsRes.data.data || docsRes.data;
                wards.value = wardsRes.data.data || wardsRes.data;
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

        const searchPatients = async () => {
            if (!patientSearch.value) return;
            try {
                const response = await axios.get('/api/patients-search', { params: { search: patientSearch.value } });
                patientResults.value = response.data.data || response.data;
            } catch (error) {
                console.error('Failed to search patients:', error);
            }
        };

        const selectPatient = (patient) => {
            selectedPatient.value = patient;
            form.value.patient_id = patient.patient_id;
            patientResults.value = [];
            patientSearch.value = '';
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

        const saveAdvancePayment = async () => {
            try {
                await axios.post(`/api/ipd-admissions/${route.params.id}/advance-payments`, advanceForm.value);
                showAdvancePayment.value = false;
                advanceForm.value = { amount: '', payment_mode: 'cash', reference_number: '', remarks: '' };
                loadAdvancePayments();
                loadRunningBill();
            } catch (error) {
                alert('Failed to collect payment: ' + (error.response?.data?.message || error.message));
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
            patientResults,
            selectedPatient,
            form,
            filteredDoctors,
            canSubmit,
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
            searchPatients,
            selectPatient,
            loadAvailableBeds,
            loadDepartmentDoctors,
            submitAdmission,
            saveProgressNote,
            saveAdvancePayment,
            completeDischarge,
            stopMedication,
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
.bed-selection {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.bed-option {
    width: 80px;
    padding: 10px;
    border: 2px solid #28a745;
    border-radius: 8px;
    background-color: #d4edda;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.bed-option:hover {
    transform: scale(1.05);
}

.bed-option.selected {
    border-color: #007bff;
    background-color: #cce5ff;
}

.bed-number {
    font-size: 1.2rem;
    font-weight: bold;
}

.btn-xs {
    padding: 0.15rem 0.4rem;
    font-size: 0.75rem;
}
</style>
