<template>
    <div class="discharge-summary-form">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">
                    <i class="bi bi-file-earmark-medical me-2"></i>
                    {{ getPageTitle() }}
                </h5>
                <p class="text-muted mb-0 small" v-if="selectedPatient">
                    {{ selectedPatient.patient?.first_name }} {{ selectedPatient.patient?.last_name }} | 
                    IPD: {{ selectedPatient.ipd_number }} | 
                    Status: {{ selectedPatient.status }}
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light" @click="goBack">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </button>
                <button v-if="!isViewMode" class="btn btn-outline-secondary" @click="saveDraft" :disabled="loading">
                    <i class="bi bi-save me-1"></i> Save Draft
                </button>
                <button v-if="!isViewMode" class="btn btn-success" @click="saveSummary" :disabled="loading">
                    <i class="bi bi-check-circle me-1"></i> {{ $route.params.id ? 'Update' : 'Save' }} Summary
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading && !form.ipd_id" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="text-muted mt-2">Loading...</p>
        </div>

        <!-- Form Sections -->
        <div v-else>
            <!-- Patient Selection -->
            <div class="card mb-3" v-if="!$route.params.id">
                <div class="card-header d-flex justify-content-between align-items-center cursor-pointer" @click="toggleSection('patient')">
                    <h6 class="mb-0">
                        <i class="bi bi-person-circle me-2"></i>
                        Patient Selection
                    </h6>
                    <i :class="expandedSections.patient ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                </div>
                <div class="card-body" v-show="expandedSections.patient">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Select IPD Patient *</label>
                            <select class="form-select" v-model="form.ipd_id" @change="loadPatientData">
                                <option value="">Choose patient...</option>
                                <option v-for="admission in dischargedPatients" :key="admission.ipd_id" :value="admission.ipd_id">
                                    {{ admission.ipd_number }} - {{ admission.patient?.first_name }} {{ admission.patient?.last_name }} ({{ admission.status }})
                                </option>
                            </select>
                            <small class="text-muted">Select IPD patient to create discharge summary</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center cursor-pointer" @click="toggleSection('basic')">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Basic Information
                    </h6>
                    <i :class="expandedSections.basic ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                </div>
                <div class="card-body" v-show="expandedSections.basic">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Admission Date *</label>
                            <input type="datetime-local" class="form-control" v-model="form.admission_date" :disabled="isViewMode">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Discharge Date *</label>
                            <input type="datetime-local" class="form-control" v-model="form.discharge_date" :disabled="isViewMode">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Admission Type *</label>
                            <select class="form-select" v-model="form.admission_type" :disabled="isViewMode">
                                <option value="emergency">Emergency</option>
                                <option value="planned">Planned</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Condition at Discharge *</label>
                            <select class="form-select" v-model="form.condition_at_discharge" :disabled="isViewMode">
                                <option value="">Select...</option>
                                <option value="improved">Improved</option>
                                <option value="same">Same</option>
                                <option value="deteriorated">Deteriorated</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Treating Doctor</label>
                            <select class="form-select" v-model="form.treating_doctor_id" :disabled="isViewMode">
                                <option value="">Select doctor...</option>
                                <option v-for="doctor in doctors" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                    {{ doctor.full_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Consultant Doctor</label>
                            <select class="form-select" v-model="form.consultant_doctor_id" :disabled="isViewMode">
                                <option value="">Select doctor...</option>
                                <option v-for="doctor in doctors" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                    {{ doctor.full_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ABHA Address</label>
                            <input type="text" class="form-control" v-model="form.abha_address" placeholder="patient@abdm" :disabled="isViewMode">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical History -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center cursor-pointer" @click="toggleSection('history')">
                    <h6 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Medical History & Examination
                    </h6>
                    <i :class="expandedSections.history ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                </div>
                <div class="card-body" v-show="expandedSections.history">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Chief Complaints</label>
                            <textarea class="form-control" v-model="form.chief_complaints" rows="3" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">History of Present Illness</label>
                            <textarea class="form-control" v-model="form.history_of_present_illness" rows="3" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Past Medical History</label>
                            <textarea class="form-control" v-model="form.past_medical_history" rows="2" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Family History</label>
                            <textarea class="form-control" v-model="form.family_history" rows="2" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Physical Examination</label>
                            <textarea class="form-control" v-model="form.physical_examination" rows="3" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vital Signs</label>
                            <textarea class="form-control" v-model="form.vital_signs" rows="3" placeholder="BP, Pulse, Temp, SpO2" :disabled="isViewMode"></textarea>
                        </div>

                        <!-- Custom Fields for History Section -->
                        <template v-if="customFieldsBySection.history && customFieldsBySection.history.length > 0">
                            <div class="col-12"><hr class="my-2"></div>
                            <div v-for="field in customFieldsBySection.history" :key="field.field_id"
                                 :class="field.field_type === 'textarea' ? 'col-md-12' : 'col-md-6'">
                                <label class="form-label">
                                    {{ field.field_label }}
                                    <span v-if="field.is_required" class="text-danger">*</span>
                                </label>

                                <input v-if="field.field_type === 'text'" type="text" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                       :required="field.is_required" :disabled="isViewMode">

                                <textarea v-else-if="field.field_type === 'textarea'" class="form-control"
                                          v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                          :required="field.is_required" :disabled="isViewMode" rows="3"></textarea>

                                <input v-else-if="field.field_type === 'number'" type="number" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                       :required="field.is_required" :disabled="isViewMode">

                                <input v-else-if="field.field_type === 'date'" type="date" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :required="field.is_required"
                                       :disabled="isViewMode">

                                <select v-else-if="field.field_type === 'select'" class="form-select"
                                        v-model="customFieldValues[field.field_id]" :required="field.is_required"
                                        :disabled="isViewMode">
                                    <option value="">{{ field.placeholder || 'Select...' }}</option>
                                    <option v-for="option in field.field_options" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>

                                <small v-if="field.help_text" class="text-muted d-block mt-1">{{ field.help_text }}</small>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Diagnosis -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center cursor-pointer" @click="toggleSection('diagnosis')">
                    <h6 class="mb-0">
                        <i class="bi bi-clipboard2-pulse me-2"></i>
                        Diagnosis & ICD Codes
                    </h6>
                    <i :class="expandedSections.diagnosis ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                </div>
                <div class="card-body" v-show="expandedSections.diagnosis">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Provisional Diagnosis</label>
                            <textarea class="form-control" v-model="form.provisional_diagnosis" rows="2" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Final Diagnosis *</label>
                            <textarea class="form-control" v-model="form.final_diagnosis" rows="2" :disabled="isViewMode" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Secondary Diagnosis</label>
                            <textarea class="form-control" v-model="form.secondary_diagnosis" rows="2" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ICD-10 Codes</label>
                            <input type="text" class="form-control" v-model="form.icd_codes" placeholder="E.g., A01.0, B20" :disabled="isViewMode">
                            <small class="text-muted">Comma separated ICD-10 codes</small>
                        </div>

                        <!-- Custom Fields for Diagnosis Section -->
                        <template v-if="customFieldsBySection.diagnosis && customFieldsBySection.diagnosis.length > 0">
                            <div class="col-12"><hr class="my-2"></div>
                            <div v-for="field in customFieldsBySection.diagnosis" :key="field.field_id"
                                 :class="field.field_type === 'textarea' ? 'col-md-12' : 'col-md-6'">
                                <label class="form-label">
                                    {{ field.field_label }}
                                    <span v-if="field.is_required" class="text-danger">*</span>
                                </label>

                                <input v-if="field.field_type === 'text'" type="text" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                       :required="field.is_required" :disabled="isViewMode">

                                <textarea v-else-if="field.field_type === 'textarea'" class="form-control"
                                          v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                          :required="field.is_required" :disabled="isViewMode" rows="3"></textarea>

                                <input v-else-if="field.field_type === 'number'" type="number" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                       :required="field.is_required" :disabled="isViewMode">

                                <input v-else-if="field.field_type === 'date'" type="date" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :required="field.is_required"
                                       :disabled="isViewMode">

                                <select v-else-if="field.field_type === 'select'" class="form-select"
                                        v-model="customFieldValues[field.field_id]" :required="field.is_required"
                                        :disabled="isViewMode">
                                    <option value="">{{ field.placeholder || 'Select...' }}</option>
                                    <option v-for="option in field.field_options" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>

                                <small v-if="field.help_text" class="text-muted d-block mt-1">{{ field.help_text }}</small>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Treatment & Procedures -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center cursor-pointer" @click="toggleSection('treatment')">
                    <h6 class="mb-0">
                        <i class="bi bi-heart-pulse me-2"></i>
                        Treatment & Procedures
                    </h6>
                    <i :class="expandedSections.treatment ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                </div>
                <div class="card-body" v-show="expandedSections.treatment">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Course in Hospital</label>
                            <textarea class="form-control" v-model="form.course_in_hospital" rows="3" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Procedures Performed</label>
                            <textarea class="form-control" v-model="form.procedures_performed" rows="3" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Operation Notes</label>
                            <textarea class="form-control" v-model="form.operation_notes" rows="3" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Investigations</label>
                            <textarea class="form-control" v-model="form.investigations" rows="3" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Treatment Given</label>
                            <textarea class="form-control" v-model="form.treatment_given" rows="3" :disabled="isViewMode"></textarea>
                        </div>

                        <!-- Custom Fields for Treatment Section -->
                        <template v-if="customFieldsBySection.treatment && customFieldsBySection.treatment.length > 0">
                            <div class="col-12"><hr class="my-2"></div>
                            <div v-for="field in customFieldsBySection.treatment" :key="field.field_id"
                                 :class="field.field_type === 'textarea' ? 'col-md-12' : 'col-md-6'">
                                <label class="form-label">
                                    {{ field.field_label }}
                                    <span v-if="field.is_required" class="text-danger">*</span>
                                </label>

                                <input v-if="field.field_type === 'text'" type="text" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                       :required="field.is_required" :disabled="isViewMode">

                                <textarea v-else-if="field.field_type === 'textarea'" class="form-control"
                                          v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                          :required="field.is_required" :disabled="isViewMode" rows="3"></textarea>

                                <input v-else-if="field.field_type === 'number'" type="number" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                       :required="field.is_required" :disabled="isViewMode">

                                <input v-else-if="field.field_type === 'date'" type="date" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :required="field.is_required"
                                       :disabled="isViewMode">

                                <select v-else-if="field.field_type === 'select'" class="form-select"
                                        v-model="customFieldValues[field.field_id]" :required="field.is_required"
                                        :disabled="isViewMode">
                                    <option value="">{{ field.placeholder || 'Select...' }}</option>
                                    <option v-for="option in field.field_options" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>

                                <small v-if="field.help_text" class="text-muted d-block mt-1">{{ field.help_text }}</small>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Medications -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center cursor-pointer" @click="toggleSection('medications')">
                    <h6 class="mb-0">
                        <i class="bi bi-capsule me-2"></i>
                        Medications
                    </h6>
                    <i :class="expandedSections.medications ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                </div>
                <div class="card-body" v-show="expandedSections.medications">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Medications on Admission</label>
                            <textarea class="form-control" v-model="form.medications_on_admission" rows="5" placeholder="List medications with dosage" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Medications on Discharge</label>
                            <textarea class="form-control" v-model="form.medications_on_discharge" rows="5" placeholder="List medications with dosage and duration" :disabled="isViewMode"></textarea>
                        </div>

                        <!-- Custom Fields for Medications Section -->
                        <template v-if="customFieldsBySection.medications && customFieldsBySection.medications.length > 0">
                            <div class="col-12"><hr class="my-2"></div>
                            <div v-for="field in customFieldsBySection.medications" :key="field.field_id"
                                 :class="field.field_type === 'textarea' ? 'col-md-12' : 'col-md-6'">
                                <label class="form-label">
                                    {{ field.field_label }}
                                    <span v-if="field.is_required" class="text-danger">*</span>
                                </label>

                                <input v-if="field.field_type === 'text'" type="text" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                       :required="field.is_required" :disabled="isViewMode">

                                <textarea v-else-if="field.field_type === 'textarea'" class="form-control"
                                          v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                          :required="field.is_required" :disabled="isViewMode" rows="3"></textarea>

                                <input v-else-if="field.field_type === 'number'" type="number" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                       :required="field.is_required" :disabled="isViewMode">

                                <input v-else-if="field.field_type === 'date'" type="date" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :required="field.is_required"
                                       :disabled="isViewMode">

                                <select v-else-if="field.field_type === 'select'" class="form-select"
                                        v-model="customFieldValues[field.field_id]" :required="field.is_required"
                                        :disabled="isViewMode">
                                    <option value="">{{ field.placeholder || 'Select...' }}</option>
                                    <option v-for="option in field.field_options" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>

                                <small v-if="field.help_text" class="text-muted d-block mt-1">{{ field.help_text }}</small>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Discharge Instructions -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center cursor-pointer" @click="toggleSection('discharge')">
                    <h6 class="mb-0">
                        <i class="bi bi-house-check me-2"></i>
                        Discharge Instructions & Follow-up
                    </h6>
                    <i :class="expandedSections.discharge ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                </div>
                <div class="card-body" v-show="expandedSections.discharge">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Discharge Advice</label>
                            <textarea class="form-control" v-model="form.discharge_advice" rows="3" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Follow-up Instructions</label>
                            <textarea class="form-control" v-model="form.follow_up_instructions" rows="3" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Follow-up Date</label>
                            <input type="date" class="form-control mb-2" v-model="form.follow_up_date" :disabled="isViewMode">
                            <label class="form-label">Status</label>
                            <select class="form-select" v-model="form.status" :disabled="isViewMode">
                                <option value="draft">Draft</option>
                                <option value="completed">Completed</option>
                                <option value="signed">Signed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dietary Instructions</label>
                            <textarea class="form-control" v-model="form.dietary_instructions" rows="2" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Activity Restrictions</label>
                            <textarea class="form-control" v-model="form.activity_restrictions" rows="2" :disabled="isViewMode"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Additional Notes</label>
                            <textarea class="form-control" v-model="form.notes" rows="2" :disabled="isViewMode"></textarea>
                        </div>

                        <!-- Custom Fields for Discharge Section -->
                        <template v-if="customFieldsBySection.discharge && customFieldsBySection.discharge.length > 0">
                            <div class="col-12"><hr class="my-2"></div>
                            <div v-for="field in customFieldsBySection.discharge" :key="field.field_id"
                                 :class="field.field_type === 'textarea' ? 'col-md-12' : 'col-md-6'">
                                <label class="form-label">
                                    {{ field.field_label }}
                                    <span v-if="field.is_required" class="text-danger">*</span>
                                </label>

                                <input v-if="field.field_type === 'text'" type="text" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                       :required="field.is_required" :disabled="isViewMode">

                                <textarea v-else-if="field.field_type === 'textarea'" class="form-control"
                                          v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                          :required="field.is_required" :disabled="isViewMode" rows="3"></textarea>

                                <input v-else-if="field.field_type === 'number'" type="number" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :placeholder="field.placeholder"
                                       :required="field.is_required" :disabled="isViewMode">

                                <input v-else-if="field.field_type === 'date'" type="date" class="form-control"
                                       v-model="customFieldValues[field.field_id]" :required="field.is_required"
                                       :disabled="isViewMode">

                                <select v-else-if="field.field_type === 'select'" class="form-select"
                                        v-model="customFieldValues[field.field_id]" :required="field.is_required"
                                        :disabled="isViewMode">
                                    <option value="">{{ field.placeholder || 'Select...' }}</option>
                                    <option v-for="option in field.field_options" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>

                                <small v-if="field.help_text" class="text-muted d-block mt-1">{{ field.help_text }}</small>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Custom Fields Section -->
            <div class="card mb-3" v-if="customFieldsBySection.custom && customFieldsBySection.custom.length > 0">
                <div class="card-header d-flex justify-content-between align-items-center cursor-pointer" @click="toggleSection('custom')">
                    <h6 class="mb-0">
                        <i class="bi bi-sliders me-2"></i>
                        Additional Information
                    </h6>
                    <i :class="expandedSections.custom ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                </div>
                <div class="card-body" v-show="expandedSections.custom">
                    <div class="row g-3">
                        <div v-for="field in customFieldsBySection.custom" :key="field.field_id"
                             :class="field.field_type === 'textarea' ? 'col-md-12' : 'col-md-6'">
                            <label class="form-label">
                                {{ field.field_label }}
                                <span v-if="field.is_required" class="text-danger">*</span>
                            </label>

                            <!-- Text Input -->
                            <input v-if="field.field_type === 'text'"
                                   type="text"
                                   class="form-control"
                                   v-model="customFieldValues[field.field_id]"
                                   :placeholder="field.placeholder"
                                   :required="field.is_required"
                                   :disabled="isViewMode">

                            <!-- Textarea -->
                            <textarea v-else-if="field.field_type === 'textarea'"
                                      class="form-control"
                                      v-model="customFieldValues[field.field_id]"
                                      :placeholder="field.placeholder"
                                      :required="field.is_required"
                                      :disabled="isViewMode"
                                      rows="3"></textarea>

                            <!-- Number Input -->
                            <input v-else-if="field.field_type === 'number'"
                                   type="number"
                                   class="form-control"
                                   v-model="customFieldValues[field.field_id]"
                                   :placeholder="field.placeholder"
                                   :required="field.is_required"
                                   :disabled="isViewMode">

                            <!-- Date Input -->
                            <input v-else-if="field.field_type === 'date'"
                                   type="date"
                                   class="form-control"
                                   v-model="customFieldValues[field.field_id]"
                                   :required="field.is_required"
                                   :disabled="isViewMode">

                            <!-- Select Dropdown -->
                            <select v-else-if="field.field_type === 'select'"
                                    class="form-select"
                                    v-model="customFieldValues[field.field_id]"
                                    :required="field.is_required"
                                    :disabled="isViewMode">
                                <option value="">{{ field.placeholder || 'Select...' }}</option>
                                <option v-for="option in field.field_options" :key="option" :value="option">
                                    {{ option }}
                                </option>
                            </select>

                            <small v-if="field.help_text" class="text-muted d-block mt-1">{{ field.help_text }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.discharge-summary-form {
    max-width: 1400px;
    margin: 0 auto;
}

.cursor-pointer {
    cursor: pointer;
    user-select: none;
}

.card-header:hover {
    background-color: #f8f9fa;
}

.card {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #fff;
    border-bottom: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
}

.card-body {
    padding: 1.25rem;
}
</style>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const dischargedPatients = ref([]);
const doctors = ref([]);
const editMode = ref(false);
const selectedPatient = ref(null);
const customFields = ref([]);
const customFieldValues = ref({});

// Expanded sections state
const expandedSections = ref({
    patient: true,
    basic: true,
    history: false,
    diagnosis: true,
    treatment: false,
    medications: false,
    discharge: true,
    custom: true
});

const form = ref({
    ipd_id: '',
    admission_date: '',
    discharge_date: '',
    admission_type: 'emergency',
    chief_complaints: '',
    history_of_present_illness: '',
    past_medical_history: '',
    family_history: '',
    physical_examination: '',
    vital_signs: '',
    provisional_diagnosis: '',
    final_diagnosis: '',
    secondary_diagnosis: '',
    icd_codes: '',
    course_in_hospital: '',
    procedures_performed: '',
    operation_notes: '',
    investigations: '',
    treatment_given: '',
    medications_on_admission: '',
    medications_on_discharge: '',
    condition_at_discharge: '',
    discharge_advice: '',
    follow_up_instructions: '',
    follow_up_date: '',
    dietary_instructions: '',
    activity_restrictions: '',
    treating_doctor_id: '',
    consultant_doctor_id: '',
    abha_address: '',
    notes: '',
    status: 'draft'
});

const isViewMode = computed(() => !!route.params.id && !editMode.value);

const customFieldsBySection = computed(() => {
    const grouped = {
        custom: [],
        history: [],
        diagnosis: [],
        treatment: [],
        medications: [],
        discharge: []
    };

    customFields.value.forEach(field => {
        if (grouped[field.section]) {
            grouped[field.section].push(field);
        }
    });

    return grouped;
});

const getPageTitle = () => {
    if (!route.params.id) return 'New Discharge Summary';
    return editMode.value ? 'Edit Discharge Summary' : 'View Discharge Summary';
};

const toggleSection = (sectionName) => {
    expandedSections.value[sectionName] = !expandedSections.value[sectionName];
};

const goBack = () => {
    router.push('/discharge-summary');
};

onMounted(async () => {
    try {
        loading.value = true;

        // Fetch doctors from doctors master
        const doctorsRes = await axios.get('/api/doctors');
        doctors.value = doctorsRes.data.data || doctorsRes.data || [];

        // Fetch custom fields
        const customFieldsRes = await axios.get('/api/discharge-summary-custom-fields/active');
        customFields.value = customFieldsRes.data || [];

        if (route.params.id) {
            // Load existing summary
            const summaryRes = await axios.get(`/api/discharge-summaries/${route.params.id}`);
            const summary = summaryRes.data;

            // Auto-enable edit mode if mode=edit query parameter
            if (route.query.mode === 'edit' && summary.status !== 'signed') {
                editMode.value = true;
            }

            // Set selected patient
            selectedPatient.value = summary.ipd_admission;

            // Format dates for inputs
            form.value = {
                ...summary,
                admission_date: summary.admission_date ? summary.admission_date.substring(0, 16) : '',
                discharge_date: summary.discharge_date ? summary.discharge_date.substring(0, 16) : '',
                follow_up_date: summary.follow_up_date || ''
            };

            // Populate custom field values
            if (summary.custom_field_values && summary.custom_field_values.length > 0) {
                summary.custom_field_values.forEach(cfv => {
                    customFieldValues.value[cfv.field_id] = cfv.field_value;
                });
            }
        } else {
            // Fetch discharged patients
            const patientsRes = await axios.get('/api/discharge-summaries/discharged-patients');
            dischargedPatients.value = patientsRes.data || [];

            // Auto-select IPD patient if ipd_id query parameter is present
            if (route.query.ipd_id) {
                const ipdId = parseInt(route.query.ipd_id);
                form.value.ipd_id = ipdId;
                // Give a small delay to ensure reactivity updates
                await new Promise(resolve => setTimeout(resolve, 100));
                await loadPatientData();

                // Collapse patient selection since it's auto-filled
                expandedSections.value.patient = false;
                // Expand important sections
                expandedSections.value.basic = true;
                expandedSections.value.diagnosis = true;
            }
        }
    } catch (error) {
        console.error('Error loading form data:', error);
        alert('Error loading form data');
    } finally {
        loading.value = false;
    }
});

const loadPatientData = async () => {
    if (!form.value.ipd_id) return;

    try {
        const ipdId = parseInt(form.value.ipd_id);

        // First try to find in the cached list
        let admission = dischargedPatients.value.find(a => parseInt(a.ipd_id) === ipdId);

        // If not found or we need more details, fetch from API
        if (!admission || route.query.ipd_id) {
            try {
                const response = await axios.get(`/api/ipd-admissions/${ipdId}`);
                admission = response.data;
                selectedPatient.value = admission;
            } catch (error) {
                console.error('Error fetching IPD admission details:', error);
                // Fall back to cached data if API fails
                admission = dischargedPatients.value.find(a => parseInt(a.ipd_id) === ipdId);
            }
        }

        if (admission) {
            selectedPatient.value = admission;

            // Auto-fill basic information
            form.value.admission_date = admission.admission_date ? admission.admission_date.substring(0, 16) : '';
            form.value.discharge_date = admission.discharge_date ? admission.discharge_date.substring(0, 16) : new Date().toISOString().substring(0, 16);
            form.value.admission_type = admission.admission_type || 'emergency';

            // Auto-fill doctors if available
            if (admission.treating_doctor_id) {
                form.value.treating_doctor_id = admission.treating_doctor_id;
            }
            if (admission.consultant_doctor_id) {
                form.value.consultant_doctor_id = admission.consultant_doctor_id;
            }

            // Auto-fill from admission data if available
            if (admission.diagnosis) {
                form.value.provisional_diagnosis = admission.diagnosis;
                form.value.final_diagnosis = admission.diagnosis; // Also set as final diagnosis
            }
            if (admission.chief_complaints) {
                form.value.chief_complaints = admission.chief_complaints;
            }
            if (admission.medical_history) {
                form.value.past_medical_history = admission.medical_history;
            }
            if (admission.symptoms) {
                form.value.history_of_present_illness = admission.symptoms;
            }
            if (admission.vital_signs) {
                form.value.vital_signs = admission.vital_signs;
            }
            if (admission.examination_findings) {
                form.value.physical_examination = admission.examination_findings;
            }

            // Auto-fill ABHA if available
            if (admission.patient?.abha_address) {
                form.value.abha_address = admission.patient.abha_address;
            }

            // Try to get clinical notes and other data
            if (admission.progress_notes && admission.progress_notes.length > 0) {
                // Combine progress notes into course in hospital
                const notesText = admission.progress_notes.map(note =>
                    `${note.note_date}: ${note.progress_note}`
                ).join('\n\n');
                form.value.course_in_hospital = notesText;
            }

            // Get medications if available
            if (admission.medications && admission.medications.length > 0) {
                const medsOnAdmission = admission.medications
                    .filter(m => m.medication_type === 'on_admission')
                    .map(m => `${m.medication_name} - ${m.dosage} - ${m.frequency}`)
                    .join('\n');
                if (medsOnAdmission) {
                    form.value.medications_on_admission = medsOnAdmission;
                }
            }

            // Get investigations if available
            if (admission.investigations && admission.investigations.length > 0) {
                const investigationsText = admission.investigations.map(inv =>
                    `${inv.test_name}: ${inv.result || 'Pending'}`
                ).join('\n');
                form.value.investigations = investigationsText;
            }

            // Set condition based on status
            if (admission.status === 'discharged') {
                form.value.condition_at_discharge = 'improved';
            }
        }
    } catch (error) {
        console.error('Error loading patient data:', error);
    }
};

const saveSummary = async () => {
    if (!form.value.ipd_id && !route.params.id) {
        alert('Please select a patient');
        expandedSections.value.patient = true;
        return;
    }

    if (!form.value.final_diagnosis) {
        alert('Please enter final diagnosis');
        expandedSections.value.diagnosis = true;
        return;
    }

    if (!form.value.condition_at_discharge) {
        alert('Please select condition at discharge');
        expandedSections.value.basic = true;
        return;
    }

    loading.value = true;
    try {
        const payload = {
            ...form.value,
            custom_fields: customFieldValues.value
        };

        if (route.params.id) {
            await axios.put(`/api/discharge-summaries/${route.params.id}`, payload);
            alert('Discharge summary updated successfully!');
        } else {
            payload.status = 'completed';
            await axios.post('/api/discharge-summaries', payload);
            alert('Discharge summary created successfully!');
        }
        router.push('/discharge-summary');
    } catch (error) {
        console.error('Error saving summary:', error);
        alert(error.response?.data?.message || 'Error saving discharge summary');
    } finally {
        loading.value = false;
    }
};

const saveDraft = async () => {
    if (!form.value.ipd_id) {
        alert('Please select a patient');
        expandedSections.value.patient = true;
        return;
    }

    loading.value = true;
    try {
        const payload = {
            ...form.value,
            status: 'draft',
            custom_fields: customFieldValues.value
        };

        await axios.post('/api/discharge-summaries', payload);
        alert('Draft saved successfully!');
        router.push('/discharge-summary');
    } catch (error) {
        console.error('Error saving draft:', error);
        alert(error.response?.data?.message || 'Error saving draft');
    } finally {
        loading.value = false;
    }
};
</script>
