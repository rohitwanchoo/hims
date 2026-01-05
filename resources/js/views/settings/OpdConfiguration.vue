<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">OPD Configuration</h4>
            <button class="btn btn-primary" @click="saveConfig" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-save me-1"></i> Save Configuration
            </button>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'entry_card' }"
                           @click.prevent="activeTab = 'entry_card'">
                            <i class="bi bi-card-checklist me-2"></i> Entry Card
                        </a>
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'consultation' }"
                           @click.prevent="activeTab = 'consultation'">
                            <i class="bi bi-cash-coin me-2"></i> Consultation Charges
                        </a>
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'rates' }"
                           @click.prevent="activeTab = 'rates'">
                            <i class="bi bi-currency-dollar me-2"></i> Rate Settings
                        </a>
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'registration' }"
                           @click.prevent="activeTab = 'registration'">
                            <i class="bi bi-person-plus me-2"></i> Registration Mode
                        </a>
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'appointment' }"
                           @click.prevent="activeTab = 'appointment'">
                            <i class="bi bi-calendar-check me-2"></i> Appointment
                        </a>
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'followup' }"
                           @click.prevent="activeTab = 'followup'">
                            <i class="bi bi-arrow-repeat me-2"></i> Follow-up
                        </a>
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'token' }"
                           @click.prevent="activeTab = 'token'">
                            <i class="bi bi-ticket-perforated me-2"></i> Token Settings
                        </a>
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'other' }"
                           @click.prevent="activeTab = 'other'">
                            <i class="bi bi-gear me-2"></i> Other Settings
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <!-- Entry Card Settings -->
                <div class="card" v-if="activeTab === 'entry_card'">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-card-checklist me-2"></i>Entry Card / Registration Charges</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-4">
                            Configure registration charges for first-time patients visiting the hospital.
                        </p>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" v-model="config.charge_entry_card" id="chargeEntryCard">
                                <label class="form-check-label" for="chargeEntryCard">
                                    <strong>Charge Entry Card Fee</strong>
                                </label>
                            </div>
                            <small class="text-muted">Enable to collect registration charges from patients</small>
                        </div>

                        <div v-if="config.charge_entry_card">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Entry Card Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" v-model="config.entry_card_amount" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Validity Type</label>
                                    <select class="form-select" v-model="config.entry_card_validity_type">
                                        <option value="one_time">One Time (Per Visit)</option>
                                        <option value="daily">Daily</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="half_yearly">Half Yearly (6 Months)</option>
                                        <option value="yearly">Yearly</option>
                                        <option value="lifetime">Lifetime</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3" v-if="config.entry_card_validity_type === 'custom'">
                                <label class="form-label">Custom Validity (Days)</label>
                                <input type="number" class="form-control" v-model="config.entry_card_validity_days" min="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consultation Charge Settings -->
                <div class="card" v-if="activeTab === 'consultation'">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Consultation Charge Collection</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-4">
                            Configure when to collect OPD consultation charges from patients.
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Collection Mode</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" v-model="config.consultation_charge_mode" value="at_registration" id="modeReg">
                                <label class="form-check-label" for="modeReg">
                                    <strong>At Registration</strong>
                                    <br><small class="text-muted">Collect consultation charges when patient is registered</small>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" v-model="config.consultation_charge_mode" value="after_consultation" id="modeAfter">
                                <label class="form-check-label" for="modeAfter">
                                    <strong>After Consultation</strong>
                                    <br><small class="text-muted">Collect charges after patient has consulted with doctor</small>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" v-model="config.consultation_charge_mode" value="both" id="modeBoth">
                                <label class="form-check-label" for="modeBoth">
                                    <strong>Both</strong>
                                    <br><small class="text-muted">Allow collection at either time</small>
                                </label>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> Use "At Registration" for pre-payment, "After Consultation" for post-payment based on services rendered.
                        </div>
                    </div>
                </div>

                <!-- Rate Settings -->
                <div class="card" v-if="activeTab === 'rates'">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Rate Configuration</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-4">
                            Configure how service rates are managed and whether they can be modified.
                        </p>

                        <div class="mb-4">
                            <label class="form-label">Rate Type</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" v-model="config.rate_type" value="fixed" id="rateFixed">
                                <label class="form-check-label" for="rateFixed">
                                    <strong>Fixed Rates</strong>
                                    <br><small class="text-muted">Rates are fixed and cannot be changed by any user</small>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" v-model="config.rate_type" value="editable" id="rateEdit">
                                <label class="form-check-label" for="rateEdit">
                                    <strong>Editable Rates</strong>
                                    <br><small class="text-muted">Users can change service charges as prescribed by doctor</small>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" v-model="config.rate_type" value="doctor_approval" id="rateApproval">
                                <label class="form-check-label" for="rateApproval">
                                    <strong>Doctor Approval Required</strong>
                                    <br><small class="text-muted">Rate changes require doctor/admin approval</small>
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" v-model="config.use_doctor_wise_rates" id="doctorWiseRates">
                                    <label class="form-check-label" for="doctorWiseRates">
                                        <strong>Doctor Wise OPD Rates</strong>
                                    </label>
                                </div>
                                <small class="text-muted d-block mb-3">Different consultation rates for each doctor/specialty</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" v-model="config.use_class_wise_rates" id="classWiseRates">
                                    <label class="form-check-label" for="classWiseRates">
                                        <strong>Class Wise Rates</strong>
                                    </label>
                                </div>
                                <small class="text-muted d-block mb-3">Different rates based on patient class (General, VIP, etc.)</small>
                            </div>
                        </div>

                        <div v-if="config.rate_type !== 'fixed'">
                            <hr>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" v-model="config.allow_rate_override" id="allowOverride">
                                <label class="form-check-label" for="allowOverride">
                                    <strong>Allow Rate Override</strong>
                                </label>
                            </div>
                        </div>

                        <div v-if="config.rate_type === 'doctor_approval'" class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Rate change requests will be sent for approval. Manage pending requests in
                            <router-link to="/settings/rate-requests">Rate Change Requests</router-link>.
                        </div>
                    </div>
                </div>

                <!-- Registration Mode -->
                <div class="card" v-if="activeTab === 'registration'">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>Registration Mode</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-4">
                            Configure how patients are registered for OPD visits.
                        </p>

                        <div class="mb-3">
                            <label class="form-label">Registration Mode</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" v-model="config.registration_mode" value="doctor" id="regDoctor">
                                <label class="form-check-label" for="regDoctor">
                                    <strong>Doctor Wise</strong>
                                    <br><small class="text-muted">Patient is registered directly to a specific doctor</small>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" v-model="config.registration_mode" value="department" id="regDept">
                                <label class="form-check-label" for="regDept">
                                    <strong>Department Wise</strong>
                                    <br><small class="text-muted">Patient is registered to a department, any doctor can consult</small>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" v-model="config.registration_mode" value="unit" id="regUnit">
                                <label class="form-check-label" for="regUnit">
                                    <strong>Unit Wise</strong>
                                    <br><small class="text-muted">Patient is registered to a unit, unit head or members can consult</small>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" v-model="config.registration_mode" value="group" id="regGroup">
                                <label class="form-check-label" for="regGroup">
                                    <strong>Group/Team Wise</strong>
                                    <br><small class="text-muted">Patient is registered to a doctor group/team</small>
                                </label>
                            </div>
                        </div>

                        <div v-if="config.registration_mode === 'unit' || config.registration_mode === 'group'" class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Configure doctor groups in <router-link to="/settings/doctor-groups">Doctor Groups</router-link>.
                        </div>
                    </div>
                </div>

                <!-- Appointment Settings -->
                <div class="card" v-if="activeTab === 'appointment'">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Appointment Settings</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-4">
                            Configure appointment booking and expiry settings.
                        </p>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Appointment Expiry (Days)</label>
                                <input type="number" class="form-control" v-model="config.appointment_expiry_days" min="1">
                                <small class="text-muted">Days after which unattended appointments expire</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Auto-Cancel Time</label>
                                <input type="time" class="form-control" v-model="config.appointment_cancel_time">
                                <small class="text-muted">Time when auto-cancellation runs daily</small>
                            </div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" v-model="config.auto_cancel_expired_appointments" id="autoCancel">
                            <label class="form-check-label" for="autoCancel">
                                <strong>Auto-Cancel Expired Appointments</strong>
                            </label>
                        </div>
                        <small class="text-muted">Automatically cancel appointments after expiry period</small>
                    </div>
                </div>

                <!-- Follow-up Settings -->
                <div class="card" v-if="activeTab === 'followup'">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Follow-up Settings</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-4">
                            Configure follow-up visit settings and free follow-up policies.
                        </p>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" v-model="config.enable_free_followup" id="freeFollowup">
                            <label class="form-check-label" for="freeFollowup">
                                <strong>Enable Free Follow-up</strong>
                            </label>
                        </div>

                        <div v-if="config.enable_free_followup" class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Default Follow-up Validity (Days)</label>
                                <input type="number" class="form-control" v-model="config.default_followup_validity_days" min="1">
                                <small class="text-muted">Days within which follow-up is valid</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Free Follow-up Period (Days)</label>
                                <input type="number" class="form-control" v-model="config.default_free_followup_days" min="0">
                                <small class="text-muted">Days within which follow-up is free</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Token Settings -->
                <div class="card" v-if="activeTab === 'token'">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-ticket-perforated me-2"></i>Token Generation</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-4">
                            Configure how OPD tokens are generated for patients.
                        </p>

                        <div class="mb-4">
                            <label class="form-label">Token Generation Mode</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" v-model="config.token_generation" value="auto" id="tokenAuto">
                                <label class="form-check-label" for="tokenAuto">
                                    <strong>Automatic</strong>
                                    <br><small class="text-muted">System generates tokens automatically</small>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" v-model="config.token_generation" value="manual" id="tokenManual">
                                <label class="form-check-label" for="tokenManual">
                                    <strong>Manual</strong>
                                    <br><small class="text-muted">Receptionist enters token number manually</small>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" v-model="config.token_generation" value="slot_based" id="tokenSlot">
                                <label class="form-check-label" for="tokenSlot">
                                    <strong>Slot Based</strong>
                                    <br><small class="text-muted">Token assigned based on time slot booking</small>
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" v-model="config.token_per_doctor" id="tokenPerDoc">
                                    <label class="form-check-label" for="tokenPerDoc">
                                        <strong>Token Per Doctor</strong>
                                    </label>
                                </div>
                                <small class="text-muted">Separate token sequence for each doctor</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" v-model="config.token_per_department" id="tokenPerDept">
                                    <label class="form-check-label" for="tokenPerDept">
                                        <strong>Token Per Department</strong>
                                    </label>
                                </div>
                                <small class="text-muted">Separate token sequence for each department</small>
                            </div>
                        </div>

                        <div v-if="config.token_generation === 'slot_based'" class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            Configure time slots in <router-link to="/settings/opd-time-slots">OPD Time Slots</router-link>.
                        </div>
                    </div>
                </div>

                <!-- Other Settings -->
                <div class="card" v-if="activeTab === 'other'">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Other Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" v-model="config.mandatory_vitals" id="mandVitals">
                            <label class="form-check-label" for="mandVitals">
                                <strong>Mandatory Vitals</strong>
                            </label>
                        </div>
                        <small class="text-muted d-block mb-4">Require vitals to be recorded before consultation</small>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" v-model="config.mandatory_chief_complaint" id="mandComplaint">
                            <label class="form-check-label" for="mandComplaint">
                                <strong>Mandatory Chief Complaint</strong>
                            </label>
                        </div>
                        <small class="text-muted d-block mb-4">Require chief complaint to be entered during registration</small>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" v-model="config.allow_multiple_visits_per_day" id="multiVisit">
                            <label class="form-check-label" for="multiVisit">
                                <strong>Allow Multiple Visits Per Day</strong>
                            </label>
                        </div>
                        <small class="text-muted d-block mb-4">Allow same patient to have multiple OPD visits on same day</small>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" v-model="config.require_payment_before_consultation" id="payBeforeConsult">
                            <label class="form-check-label" for="payBeforeConsult">
                                <strong>Require Payment Before Consultation</strong>
                            </label>
                        </div>
                        <small class="text-muted d-block">Patient must complete payment before seeing doctor</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const activeTab = ref('entry_card');
const saving = ref(false);
const loading = ref(true);

const config = ref({
    charge_entry_card: true,
    entry_card_amount: 0,
    entry_card_validity_type: 'yearly',
    entry_card_validity_days: null,
    consultation_charge_mode: 'at_registration',
    rate_type: 'fixed',
    allow_rate_override: false,
    require_doctor_approval_for_rate_change: false,
    use_doctor_wise_rates: true,
    use_class_wise_rates: true,
    registration_mode: 'doctor',
    appointment_expiry_days: 1,
    auto_cancel_expired_appointments: true,
    appointment_cancel_time: null,
    enable_free_followup: true,
    default_followup_validity_days: 7,
    default_free_followup_days: 3,
    token_generation: 'auto',
    token_per_doctor: true,
    token_per_department: false,
    mandatory_vitals: false,
    mandatory_chief_complaint: true,
    allow_multiple_visits_per_day: false,
    require_payment_before_consultation: false
});

onMounted(async () => {
    try {
        const response = await axios.get('/api/opd-configuration');
        if (response.data.config) {
            config.value = { ...config.value, ...response.data.config };
        } else if (response.data.defaults) {
            config.value = { ...config.value, ...response.data.defaults };
        }
    } catch (error) {
        console.error('Error loading OPD configuration:', error);
    } finally {
        loading.value = false;
    }
});

const saveConfig = async () => {
    saving.value = true;
    try {
        // Set require_doctor_approval based on rate_type
        config.value.require_doctor_approval_for_rate_change = config.value.rate_type === 'doctor_approval';

        if (config.value.config_id) {
            await axios.put(`/api/opd-configuration/${config.value.config_id}`, config.value);
        } else {
            const response = await axios.post('/api/opd-configuration', config.value);
            config.value = response.data.config;
        }
        alert('OPD Configuration saved successfully!');
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving configuration');
    } finally {
        saving.value = false;
    }
};
</script>
