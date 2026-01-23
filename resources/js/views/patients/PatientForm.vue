<template>
    <div>
        <h4 class="mb-4">{{ isEdit ? 'Edit Patient' : 'New Patient Registration' }}</h4>
        <form @submit.prevent="handleSubmit">
            <div class="row">
                <!-- Left Column - Form Fields -->
                <div class="col-lg-9">
                    <div class="card mb-4">
                        <div class="card-body">
                            <!-- Basic Information -->
                            <h6 class="text-primary mb-3">Basic Information</h6>
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Prefix *</label>
                                    <select class="form-select" v-model="form.prefix_id" required>
                                        <option value="">Select</option>
                                        <option v-for="p in prefixes" :key="p.prefix_id" :value="p.prefix_id">{{ p.prefix_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" class="form-control" v-model="form.first_name" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" v-model="form.middle_name">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" v-model="form.last_name" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" v-model="form.dob" @change="calculateAgeFromDob">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Years</label>
                                    <input type="number" class="form-control" v-model.number="form.age_years" min="0" max="150" @input="calculateDobFromAge">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Months</label>
                                    <input type="number" class="form-control" v-model.number="form.age_months" min="0" max="11" @input="calculateDobFromAge">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Days</label>
                                    <input type="number" class="form-control" v-model.number="form.age_days" min="0" max="30" @input="calculateDobFromAge">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gender *</label>
                                    <select class="form-select" v-model="form.gender_id" required>
                                        <option value="">Select</option>
                                        <option v-for="g in genders" :key="g.gender_id" :value="g.gender_id">{{ g.gender_name }}</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Blood Group</label>
                                    <select class="form-select" v-model="form.blood_group_id">
                                        <option value="">Select</option>
                                        <option v-for="b in bloodGroups" :key="b.blood_group_id" :value="b.blood_group_id">{{ b.blood_group_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Patient Type</label>
                                    <select class="form-select" v-model="form.patient_type_id">
                                        <option value="">Select</option>
                                        <option v-for="pt in patientTypes" :key="pt.patient_type_id" :value="pt.patient_type_id">{{ pt.patient_type_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Marital Status</label>
                                    <select class="form-select" v-model="form.marital_status_id">
                                        <option value="">Select</option>
                                        <option v-for="ms in maritalStatuses" :key="ms.marital_status_id" :value="ms.marital_status_id">{{ ms.marital_status_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Class</label>
                                    <select class="form-select" v-model="form.class_id">
                                        <option value="">Select</option>
                                        <option v-for="c in patientClasses" :key="c.class_id" :value="c.class_id">{{ c.class_name }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Reference & Insurance Information -->
                            <h6 class="text-primary mt-4 mb-3">Reference & Insurance Information</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Reference Doctor</label>
                                    <select class="form-select" v-model="form.reference_doctor_id">
                                        <option value="">Select Doctor</option>
                                        <option v-for="d in referenceDoctors" :key="d.reference_doctor_id" :value="d.reference_doctor_id">
                                            {{ d.doctor_name }} {{ d.clinic_name ? '- ' + d.clinic_name : '' }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Insurance Company</label>
                                    <select class="form-select" v-model="form.insurance_company_id">
                                        <option value="">Select</option>
                                        <option v-for="ic in insuranceCompanies" :key="ic.insurance_company_id" :value="ic.insurance_company_id">
                                            {{ ic.company_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Insurance Policy No.</label>
                                    <input type="text" class="form-control" v-model="form.insurance_policy_no">
                                </div>
                            </div>

                            <!-- Subscribe Options -->
                            <div class="row g-3 mt-3">
                                <div class="col-md-2 col-sm-12">
                                    <label class="form-label mb-0">Subscribe</label>
                                </div>
                                <div class="col-md-10 col-sm-12">
                                    <div class="d-flex flex-row flex-wrap gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="subscribe_sms" v-model="form.subscribe_sms">
                                            <label class="form-check-label" for="subscribe_sms">SMS</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="subscribe_whatsapp" v-model="form.subscribe_whatsapp">
                                            <label class="form-check-label" for="subscribe_whatsapp">WhatsApp</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="subscribe_email" v-model="form.subscribe_email">
                                            <label class="form-check-label" for="subscribe_email">Email</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Permanent Address -->
                            <h6 class="text-primary mt-4 mb-3">Permanent Address</h6>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" v-model="form.permanent_address" rows="2"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Area/Village</label>
                                    <select class="form-select" v-model="form.permanent_area_id" @change="onPermanentAreaChange">
                                        <option value="">Select Area</option>
                                        <option v-for="a in permanentAreas" :key="a.area_id" :value="a.area_id">
                                            {{ a.area_name }} {{ a.pincode ? '(' + a.pincode + ')' : '' }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">City/Taluka</label>
                                    <select class="form-select" v-model="form.permanent_city_id" @change="onPermanentCityChange">
                                        <option value="">Select City</option>
                                        <option v-for="c in permanentCities" :key="c.city_id" :value="c.city_id">{{ c.city_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">District</label>
                                    <select class="form-select" v-model="form.permanent_district_id" @change="onPermanentDistrictChange">
                                        <option value="">Select District</option>
                                        <option v-for="d in permanentDistricts" :key="d.district_id" :value="d.district_id">{{ d.district_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State</label>
                                    <select class="form-select" v-model="form.permanent_state_id" @change="onPermanentStateChange">
                                        <option value="">Select State</option>
                                        <option v-for="s in states" :key="s.state_id" :value="s.state_id">{{ s.state_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Country</label>
                                    <select class="form-select" v-model="form.permanent_country_id" @change="onPermanentCountryChange">
                                        <option value="">Select Country</option>
                                        <option v-for="c in countries" :key="c.country_id" :value="c.country_id">{{ c.country_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" class="form-control" v-model="form.permanent_pincode" maxlength="10">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Mobile *</label>
                                    <input type="tel" class="form-control" v-model="form.permanent_mobile" required
                                           pattern="[0-9]{10}" title="Enter 10 digit mobile number"
                                           :class="{'is-invalid': mobileError}">
                                    <div class="invalid-feedback">{{ mobileError }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" v-model="form.permanent_email">
                                </div>
                            </div>

                            <!-- Current Address -->
                            <h6 class="text-primary mt-4 mb-3">
                                Current Address
                                <div class="form-check form-check-inline ms-3">
                                    <input class="form-check-input" type="checkbox" id="same_as_permanent" v-model="form.same_as_permanent" @change="onSameAsPermanentChange">
                                    <label class="form-check-label" for="same_as_permanent">Same as Permanent Address</label>
                                </div>
                            </h6>
                            <div class="row g-3" v-if="!form.same_as_permanent">
                                <div class="col-md-12">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" v-model="form.current_address" rows="2"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Area/Village</label>
                                    <select class="form-select" v-model="form.current_area_id" @change="onCurrentAreaChange">
                                        <option value="">Select Area</option>
                                        <option v-for="a in currentAreas" :key="a.area_id" :value="a.area_id">
                                            {{ a.area_name }} {{ a.pincode ? '(' + a.pincode + ')' : '' }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">City/Taluka</label>
                                    <select class="form-select" v-model="form.current_city_id" @change="onCurrentCityChange">
                                        <option value="">Select City</option>
                                        <option v-for="c in currentCities" :key="c.city_id" :value="c.city_id">{{ c.city_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">District</label>
                                    <select class="form-select" v-model="form.current_district_id" @change="onCurrentDistrictChange">
                                        <option value="">Select District</option>
                                        <option v-for="d in currentDistricts" :key="d.district_id" :value="d.district_id">{{ d.district_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State</label>
                                    <select class="form-select" v-model="form.current_state_id" @change="onCurrentStateChange">
                                        <option value="">Select State</option>
                                        <option v-for="s in states" :key="s.state_id" :value="s.state_id">{{ s.state_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Country</label>
                                    <select class="form-select" v-model="form.current_country_id" @change="onCurrentCountryChange">
                                        <option value="">Select Country</option>
                                        <option v-for="c in countries" :key="c.country_id" :value="c.country_id">{{ c.country_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" class="form-control" v-model="form.current_pincode" maxlength="10">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Mobile</label>
                                    <input type="tel" class="form-control" v-model="form.current_mobile">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" v-model="form.current_email">
                                </div>
                            </div>

                            <!-- Emergency Contact -->
                            <h6 class="text-primary mt-4 mb-3">Emergency Contact</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Contact Name</label>
                                    <input type="text" class="form-control" v-model="form.emergency_contact_name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Contact Phone</label>
                                    <input type="tel" class="form-control" v-model="form.emergency_contact_phone">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Relation</label>
                                    <input type="text" class="form-control" v-model="form.emergency_contact_relation">
                                </div>
                            </div>

                            <!-- Medical Information -->
                            <h6 class="text-primary mt-4 mb-3">Medical Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Allergies</label>
                                    <textarea class="form-control" v-model="form.allergies" rows="2"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Medical History</label>
                                    <textarea class="form-control" v-model="form.medical_history" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Photo & Documents -->
                <div class="col-lg-3">
                    <!-- Photo Section -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Patient Photo</h6>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <img v-if="photoPreview" :src="photoPreview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                <div v-else class="border rounded p-4 text-muted">
                                    <i class="bi bi-person-bounding-box" style="font-size: 4rem;"></i>
                                    <p class="mb-0 mt-2">No photo</p>
                                </div>
                            </div>

                            <!-- Webcam Preview -->
                            <div v-if="showWebcam" class="mb-3">
                                <video ref="videoRef" autoplay playsinline class="img-thumbnail" style="max-width: 200px;"></video>
                                <canvas ref="canvasRef" style="display: none;"></canvas>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" @click="toggleWebcam">
                                    <i class="bi bi-camera me-1"></i>
                                    {{ showWebcam ? 'Close Camera' : 'Open Camera' }}
                                </button>
                                <button v-if="showWebcam" type="button" class="btn btn-success btn-sm" @click="capturePhoto">
                                    <i class="bi bi-camera-fill me-1"></i>Capture
                                </button>
                                <label class="btn btn-outline-secondary btn-sm mb-0">
                                    <i class="bi bi-upload me-1"></i>Upload Photo
                                    <input type="file" accept="image/*" @change="handlePhotoUpload" hidden>
                                </label>
                                <button v-if="photoPreview" type="button" class="btn btn-outline-danger btn-sm" @click="removePhoto">
                                    <i class="bi bi-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Section -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Documents</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-file-earmark-plus me-1"></i>Add Documents
                                    <input type="file" multiple accept="image/*,.pdf,.doc,.docx" @change="handleDocumentUpload" hidden>
                                </label>
                            </div>

                            <!-- Existing Documents (when editing) -->
                            <div v-if="existingDocuments.length > 0" class="mb-3">
                                <small class="text-muted d-block mb-2">Existing Documents:</small>
                                <div class="list-group list-group-flush">
                                    <div v-for="doc in existingDocuments" :key="doc.document_id" class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-truncate" style="max-width: 100px;" :title="doc.document_title || doc.file_name">
                                            <i :class="getDocumentIcon(doc.file_name)" class="me-1"></i>
                                            {{ doc.document_title || doc.file_name }}
                                        </span>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" @click="viewDocument(doc)" title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" @click="deleteExistingDocument(doc)" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- New Documents -->
                            <div v-if="documents.length > 0">
                                <small class="text-muted d-block mb-2">New Documents:</small>
                                <div class="list-group list-group-flush">
                                    <div v-for="(doc, index) in documents" :key="index" class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-truncate" style="max-width: 100px;" :title="doc.name">
                                            <i :class="getDocumentIcon(doc.name)" class="me-1"></i>{{ doc.name }}
                                        </span>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" @click="previewNewDocument(doc)" title="Preview">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" @click="removeDocument(index)" title="Remove">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-if="documents.length === 0 && existingDocuments.length === 0" class="text-muted small mb-0">No documents attached</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Preview Modal -->
            <div class="modal fade" id="documentPreviewModal" tabindex="-1" ref="documentModalRef">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ previewDocument.title }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center" style="max-height: 70vh; overflow: auto;">
                            <div v-if="previewDocument.loading" class="py-5">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2">Loading document...</p>
                            </div>
                            <template v-else>
                                <img v-if="previewDocument.type === 'image'" :src="previewDocument.url" class="img-fluid" alt="Document Preview">
                                <iframe v-else-if="previewDocument.type === 'pdf'" :src="previewDocument.url" class="w-100" style="height: 65vh; border: none;"></iframe>
                                <div v-else class="py-5 text-muted">
                                    <i class="bi bi-file-earmark-text" style="font-size: 4rem;"></i>
                                    <p class="mt-3">Preview not available for this file type.</p>
                                    <a :href="previewDocument.url" target="_blank" class="btn btn-primary">
                                        <i class="bi bi-download me-2"></i>Download File
                                    </a>
                                </div>
                            </template>
                        </div>
                        <div class="modal-footer">
                            <a v-if="previewDocument.url && !previewDocument.loading" :href="previewDocument.url" target="_blank" class="btn btn-outline-primary">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Open in New Tab
                            </a>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="mt-4">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    {{ isEdit ? 'Update' : 'Register' }} Patient
                </button>
                <router-link to="/patients" class="btn btn-secondary ms-2">Cancel</router-link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { Modal } from 'bootstrap';

const route = useRoute();
const router = useRouter();
const saving = ref(false);
const isEdit = computed(() => !!route.params.id);

// Master data
const prefixes = ref([]);
const genders = ref([]);
const bloodGroups = ref([]);
const patientTypes = ref([]);
const maritalStatuses = ref([]);
const patientClasses = ref([]);
const referenceDoctors = ref([]);
const insuranceCompanies = ref([]);

// Address master data
const countries = ref([]);
const states = ref([]);
const allDistricts = ref([]);
const allCities = ref([]);
const allAreas = ref([]);

// Default address values
const defaultCountryId = ref(null);
const defaultStateId = ref(null);

// Filtered address data for permanent address
const permanentDistricts = ref([]);
const permanentCities = ref([]);
const permanentAreas = ref([]);

// Filtered address data for current address
const currentDistricts = ref([]);
const currentCities = ref([]);
const currentAreas = ref([]);

// Photo handling
const photoPreview = ref(null);
const photoFile = ref(null);
const showWebcam = ref(false);
const videoRef = ref(null);
const canvasRef = ref(null);
let mediaStream = null;

// Documents handling
const documents = ref([]);
const existingDocuments = ref([]);
const documentModalRef = ref(null);
let documentModal = null;

// Document preview state
const previewDocument = reactive({
    title: '',
    url: '',
    type: '',
    loading: false
});

// Validation
const mobileError = ref('');

const form = reactive({
    prefix_id: '',
    first_name: '',
    middle_name: '',
    last_name: '',
    dob: '',
    age_years: null,
    age_months: null,
    age_days: null,
    gender_id: '',
    blood_group_id: '',
    patient_type_id: '',
    marital_status_id: '',
    class_id: '',
    reference_doctor_id: '',
    insurance_company_id: '',
    insurance_policy_no: '',
    subscribe_sms: false,
    subscribe_whatsapp: false,
    subscribe_email: false,
    // Permanent Address
    permanent_address: '',
    permanent_country_id: '',
    permanent_state_id: '',
    permanent_district_id: '',
    permanent_city_id: '',
    permanent_area_id: '',
    permanent_pincode: '',
    permanent_mobile: '',
    permanent_email: '',
    // Current Address
    same_as_permanent: true,
    current_address: '',
    current_country_id: '',
    current_state_id: '',
    current_district_id: '',
    current_city_id: '',
    current_area_id: '',
    current_pincode: '',
    current_mobile: '',
    current_email: '',
    // Emergency & Medical
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relation: '',
    allergies: '',
    medical_history: ''
});

// Bi-directional DOB/Age calculation
const calculateAgeFromDob = () => {
    if (!form.dob) {
        form.age_years = null;
        form.age_months = null;
        form.age_days = null;
        return;
    }

    const dob = new Date(form.dob);
    const today = new Date();

    let years = today.getFullYear() - dob.getFullYear();
    let months = today.getMonth() - dob.getMonth();
    let days = today.getDate() - dob.getDate();

    if (days < 0) {
        months--;
        const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
        days += lastMonth.getDate();
    }

    if (months < 0) {
        years--;
        months += 12;
    }

    form.age_years = years;
    form.age_months = months;
    form.age_days = days;
};

const calculateDobFromAge = () => {
    const years = form.age_years || 0;
    const months = form.age_months || 0;
    const days = form.age_days || 0;

    if (years === 0 && months === 0 && days === 0) {
        return;
    }

    const today = new Date();
    const dob = new Date(
        today.getFullYear() - years,
        today.getMonth() - months,
        today.getDate() - days
    );

    form.dob = dob.toISOString().split('T')[0];
};

// Mobile validation
watch(() => form.permanent_mobile, (val) => {
    if (val && !/^[0-9]{10}$/.test(val)) {
        mobileError.value = 'Enter valid 10 digit mobile number';
    } else {
        mobileError.value = '';
    }
});

// Address hierarchy handlers - Permanent Address
const onPermanentCountryChange = () => {
    form.permanent_state_id = '';
    form.permanent_district_id = '';
    form.permanent_city_id = '';
    form.permanent_area_id = '';
    permanentDistricts.value = [];
    permanentCities.value = [];
    permanentAreas.value = [];
};

const onPermanentStateChange = () => {
    form.permanent_district_id = '';
    form.permanent_city_id = '';
    form.permanent_area_id = '';
    permanentCities.value = [];
    permanentAreas.value = [];

    if (form.permanent_state_id) {
        permanentDistricts.value = allDistricts.value.filter(d => d.state_id == form.permanent_state_id);
        // Auto-populate country
        const state = states.value.find(s => s.state_id == form.permanent_state_id);
        if (state && state.country_id) {
            form.permanent_country_id = state.country_id;
        }
    } else {
        permanentDistricts.value = [];
    }
};

const onPermanentDistrictChange = () => {
    form.permanent_city_id = '';
    form.permanent_area_id = '';
    permanentAreas.value = [];

    if (form.permanent_district_id) {
        permanentCities.value = allCities.value.filter(c => c.district_id == form.permanent_district_id);
    } else {
        permanentCities.value = [];
    }
};

const onPermanentCityChange = () => {
    form.permanent_area_id = '';

    if (form.permanent_city_id) {
        permanentAreas.value = allAreas.value.filter(a => a.city_id == form.permanent_city_id);
    } else {
        permanentAreas.value = [];
    }
};

const onPermanentAreaChange = async () => {
    if (form.permanent_area_id) {
        try {
            const response = await axios.get(`/api/areas/${form.permanent_area_id}/hierarchy`);
            const data = response.data;

            // Auto-populate all fields from area hierarchy
            form.permanent_city_id = data.city_id;
            form.permanent_district_id = data.district_id;
            form.permanent_state_id = data.state_id;
            form.permanent_country_id = data.country_id;
            if (data.pincode) {
                form.permanent_pincode = data.pincode;
            }

            // Refresh cascading dropdowns
            permanentDistricts.value = allDistricts.value.filter(d => d.state_id == data.state_id);
            permanentCities.value = allCities.value.filter(c => c.district_id == data.district_id);
            permanentAreas.value = allAreas.value.filter(a => a.city_id == data.city_id);
        } catch (error) {
            console.error('Error fetching area hierarchy:', error);
        }
    }
};

// Address hierarchy handlers - Current Address
const onCurrentCountryChange = () => {
    form.current_state_id = '';
    form.current_district_id = '';
    form.current_city_id = '';
    form.current_area_id = '';
    currentDistricts.value = [];
    currentCities.value = [];
    currentAreas.value = [];
};

const onCurrentStateChange = () => {
    form.current_district_id = '';
    form.current_city_id = '';
    form.current_area_id = '';
    currentCities.value = [];
    currentAreas.value = [];

    if (form.current_state_id) {
        currentDistricts.value = allDistricts.value.filter(d => d.state_id == form.current_state_id);
        // Auto-populate country
        const state = states.value.find(s => s.state_id == form.current_state_id);
        if (state && state.country_id) {
            form.current_country_id = state.country_id;
        }
    } else {
        currentDistricts.value = [];
    }
};

const onCurrentDistrictChange = () => {
    form.current_city_id = '';
    form.current_area_id = '';
    currentAreas.value = [];

    if (form.current_district_id) {
        currentCities.value = allCities.value.filter(c => c.district_id == form.current_district_id);
    } else {
        currentCities.value = [];
    }
};

const onCurrentCityChange = () => {
    form.current_area_id = '';

    if (form.current_city_id) {
        currentAreas.value = allAreas.value.filter(a => a.city_id == form.current_city_id);
    } else {
        currentAreas.value = [];
    }
};

const onCurrentAreaChange = async () => {
    if (form.current_area_id) {
        try {
            const response = await axios.get(`/api/areas/${form.current_area_id}/hierarchy`);
            const data = response.data;

            form.current_city_id = data.city_id;
            form.current_district_id = data.district_id;
            form.current_state_id = data.state_id;
            form.current_country_id = data.country_id;
            if (data.pincode) {
                form.current_pincode = data.pincode;
            }

            currentDistricts.value = allDistricts.value.filter(d => d.state_id == data.state_id);
            currentCities.value = allCities.value.filter(c => c.district_id == data.district_id);
            currentAreas.value = allAreas.value.filter(a => a.city_id == data.city_id);
        } catch (error) {
            console.error('Error fetching area hierarchy:', error);
        }
    }
};

// Same as permanent address handler
const onSameAsPermanentChange = () => {
    if (form.same_as_permanent) {
        // Copy permanent to current
        form.current_address = form.permanent_address;
        form.current_country_id = form.permanent_country_id;
        form.current_state_id = form.permanent_state_id;
        form.current_district_id = form.permanent_district_id;
        form.current_city_id = form.permanent_city_id;
        form.current_area_id = form.permanent_area_id;
        form.current_pincode = form.permanent_pincode;
        form.current_mobile = form.permanent_mobile;
        form.current_email = form.permanent_email;
    }
};

// Webcam functions
const toggleWebcam = async () => {
    if (showWebcam.value) {
        stopWebcam();
    } else {
        showWebcam.value = true;
        await startWebcam();
    }
};

const startWebcam = async () => {
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
        }
    } catch (error) {
        console.error('Error accessing webcam:', error);
        alert('Unable to access webcam. Please check permissions.');
        showWebcam.value = false;
    }
};

const stopWebcam = () => {
    if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
        mediaStream = null;
    }
    showWebcam.value = false;
};

const capturePhoto = () => {
    if (!videoRef.value || !canvasRef.value) return;

    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);

    canvas.toBlob((blob) => {
        photoFile.value = new File([blob], 'webcam-photo.jpg', { type: 'image/jpeg' });
        photoPreview.value = URL.createObjectURL(blob);
        stopWebcam();
    }, 'image/jpeg', 0.8);
};

const handlePhotoUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        photoFile.value = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const removePhoto = () => {
    photoFile.value = null;
    photoPreview.value = null;
};

// Document handling
const handleDocumentUpload = (event) => {
    const files = Array.from(event.target.files);
    files.forEach(file => {
        documents.value.push(file);
    });
};

const removeDocument = (index) => {
    documents.value.splice(index, 1);
};

// Get document icon based on file extension
const getDocumentIcon = (fileName) => {
    if (!fileName) return 'bi bi-file-earmark';
    const ext = fileName.split('.').pop().toLowerCase();
    const iconMap = {
        'pdf': 'bi bi-file-earmark-pdf text-danger',
        'doc': 'bi bi-file-earmark-word text-primary',
        'docx': 'bi bi-file-earmark-word text-primary',
        'xls': 'bi bi-file-earmark-excel text-success',
        'xlsx': 'bi bi-file-earmark-excel text-success',
        'jpg': 'bi bi-file-earmark-image text-info',
        'jpeg': 'bi bi-file-earmark-image text-info',
        'png': 'bi bi-file-earmark-image text-info',
        'gif': 'bi bi-file-earmark-image text-info',
        'webp': 'bi bi-file-earmark-image text-info'
    };
    return iconMap[ext] || 'bi bi-file-earmark';
};

// Get file type for preview
const getFileType = (fileName) => {
    if (!fileName) return 'other';
    const ext = fileName.split('.').pop().toLowerCase();
    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(ext)) return 'image';
    if (ext === 'pdf') return 'pdf';
    return 'other';
};

// View existing document
const viewDocument = (doc) => {
    previewDocument.title = doc.document_title || doc.file_name;
    previewDocument.loading = true;
    previewDocument.type = getFileType(doc.file_name);
    previewDocument.url = `/storage/${doc.file_path}`;

    if (!documentModal) {
        documentModal = new Modal(documentModalRef.value);
    }
    documentModal.show();

    // Simulate loading for smooth UX
    setTimeout(() => {
        previewDocument.loading = false;
    }, 300);
};

// Preview new document (before upload)
const previewNewDocument = (file) => {
    previewDocument.title = file.name;
    previewDocument.loading = true;
    previewDocument.type = getFileType(file.name);

    if (!documentModal) {
        documentModal = new Modal(documentModalRef.value);
    }
    documentModal.show();

    // Create object URL for preview
    const objectUrl = URL.createObjectURL(file);
    previewDocument.url = objectUrl;

    setTimeout(() => {
        previewDocument.loading = false;
    }, 300);
};

// Delete existing document
const deleteExistingDocument = async (doc) => {
    if (!confirm(`Are you sure you want to delete "${doc.document_title || doc.file_name}"?`)) {
        return;
    }

    try {
        await axios.delete(`/api/patient-documents/${doc.document_id}`);
        existingDocuments.value = existingDocuments.value.filter(
            d => d.document_id !== doc.document_id
        );
    } catch (error) {
        alert(error.response?.data?.message || 'Error deleting document');
    }
};

// Fetch existing documents for patient
const fetchExistingDocuments = async () => {
    if (isEdit.value) {
        try {
            const response = await axios.get(`/api/patients/${route.params.id}/documents`);
            existingDocuments.value = response.data.data || response.data || [];
        } catch (error) {
            console.error('Error fetching documents:', error);
        }
    }
};

// Fetch master data
const fetchMasterData = async () => {
    try {
        const [prefixRes, genderRes, bloodRes, ptRes, msRes, classRes, refDocRes, insRes, countryRes, stateRes, districtRes, cityRes, areaRes, defaultCountryRes, defaultStateRes] = await Promise.all([
            axios.get('/api/prefixes-active'),
            axios.get('/api/genders-active'),
            axios.get('/api/blood-groups-active'),
            axios.get('/api/patient-types-active'),
            axios.get('/api/marital-statuses-active'),
            axios.get('/api/patient-classes').catch(() => ({ data: [] })),
            axios.get('/api/reference-doctors-active').catch(() => ({ data: [] })),
            axios.get('/api/insurance-companies-active').catch(() => ({ data: [] })),
            axios.get('/api/countries-active').catch(() => ({ data: [] })),
            axios.get('/api/states-active').catch(() => ({ data: [] })),
            axios.get('/api/districts-active').catch(() => ({ data: [] })),
            axios.get('/api/cities-active').catch(() => ({ data: [] })),
            axios.get('/api/areas-active').catch(() => ({ data: [] })),
            axios.get('/api/countries-default').catch(() => ({ data: null })),
            axios.get('/api/states-default').catch(() => ({ data: null }))
        ]);

        prefixes.value = prefixRes.data;
        genders.value = genderRes.data;
        bloodGroups.value = bloodRes.data;
        patientTypes.value = ptRes.data;
        maritalStatuses.value = msRes.data;
        patientClasses.value = classRes.data;
        referenceDoctors.value = refDocRes.data;
        insuranceCompanies.value = insRes.data;
        countries.value = countryRes.data;
        states.value = stateRes.data;
        allDistricts.value = districtRes.data;
        allCities.value = cityRes.data;
        allAreas.value = areaRes.data;

        // Store default values
        if (defaultCountryRes.data?.country_id) {
            defaultCountryId.value = defaultCountryRes.data.country_id;
        }
        if (defaultStateRes.data?.state_id) {
            defaultStateId.value = defaultStateRes.data.state_id;
        }

        // Apply default country and state for new patients
        if (!isEdit.value) {
            if (defaultCountryId.value) {
                form.permanent_country_id = defaultCountryId.value;
            }
            if (defaultStateId.value) {
                form.permanent_state_id = defaultStateId.value;
                // Load districts for the default state
                permanentDistricts.value = allDistricts.value.filter(d => d.state_id == defaultStateId.value);

                // Load areas for the default state (for area dropdown to show values immediately)
                try {
                    const areasRes = await axios.get('/api/areas-by-state', { params: { state_id: defaultStateId.value } });
                    permanentAreas.value = areasRes.data || [];
                } catch (e) {
                    // Fallback: filter from all areas if API fails
                    permanentAreas.value = allAreas.value;
                }
            }
        }
    } catch (error) {
        console.error('Error fetching master data:', error);
    }
};

const fetchPatient = async () => {
    if (isEdit.value) {
        try {
            const response = await axios.get(`/api/patients/${route.params.id}`);
            const patientData = response.data.data || response.data;

            // Convert date fields to YYYY-MM-DD format for input type="date"
            if (patientData.dob) {
                patientData.dob = patientData.dob.split('T')[0];
            }

            // Assign patient data to form
            Object.assign(form, patientData);

            // Handle photo preview
            if (patientData.photo) {
                photoPreview.value = `/storage/${patientData.photo}`;
            }

            // Setup cascading dropdowns based on loaded data
            if (form.permanent_state_id) {
                permanentDistricts.value = allDistricts.value.filter(d => d.state_id == form.permanent_state_id);
            }
            if (form.permanent_district_id) {
                permanentCities.value = allCities.value.filter(c => c.district_id == form.permanent_district_id);
            }
            if (form.permanent_city_id) {
                permanentAreas.value = allAreas.value.filter(a => a.city_id == form.permanent_city_id);
            }

            if (form.current_state_id) {
                currentDistricts.value = allDistricts.value.filter(d => d.state_id == form.current_state_id);
            }
            if (form.current_district_id) {
                currentCities.value = allCities.value.filter(c => c.district_id == form.current_district_id);
            }
            if (form.current_city_id) {
                currentAreas.value = allAreas.value.filter(a => a.city_id == form.current_city_id);
            }
        } catch (error) {
            console.error('Error fetching patient:', error);
        }
    }
};

const handleSubmit = async () => {
    if (mobileError.value) {
        alert('Please fix validation errors');
        return;
    }

    saving.value = true;
    try {
        // Build patient_name from name parts
        const patientName = [form.first_name, form.middle_name, form.last_name].filter(Boolean).join(' ');

        const formData = new FormData();

        // Fields to exclude from general loop (handled separately)
        const excludeFields = ['photo', 'documents'];

        // Add all form fields except excluded ones
        Object.keys(form).forEach(key => {
            if (!excludeFields.includes(key) && form[key] !== null && form[key] !== '' && form[key] !== undefined) {
                formData.append(key, form[key]);
            }
        });

        // Add computed patient_name and mobile
        formData.append('patient_name', patientName);
        formData.append('mobile', form.permanent_mobile);

        // Add photo if exists
        if (photoFile.value) {
            formData.append('photo', photoFile.value);
        }

        // Add documents
        documents.value.forEach((doc, index) => {
            formData.append(`documents[${index}]`, doc);
        });

        let response;
        if (isEdit.value) {
            formData.append('_method', 'PUT');
            response = await axios.post(`/api/patients/${route.params.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        } else {
            response = await axios.post('/api/patients', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        }

        // Check if we should return to OPD form
        if (route.query.returnToOPD === 'true' && response.data) {
            const patientId = response.data.data?.patient_id || response.data.patient_id;
            if (patientId) {
                router.push(`/opd/create?patient=${patientId}`);
                return;
            }
        }

        // Check if we should return to another form (like appointments)
        if (route.query.returnTo && response.data) {
            const patientId = response.data.data?.patient_id || response.data.patient_id;
            if (patientId) {
                router.push(`${route.query.returnTo}?patient_id=${patientId}`);
                return;
            }
        }

        router.push('/patients');
    } catch (error) {
        if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat();
            alert('Validation Error:\n' + errors.join('\n'));
        } else {
            alert(error.response?.data?.message || 'Error saving patient');
        }
    }
    saving.value = false;
};

onMounted(async () => {
    await fetchMasterData();
    await fetchPatient();
    await fetchExistingDocuments();
});

onUnmounted(() => {
    stopWebcam();
    if (photoPreview.value && photoPreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(photoPreview.value);
    }
    // Clean up preview URL if it's a blob
    if (previewDocument.url && previewDocument.url.startsWith('blob:')) {
        URL.revokeObjectURL(previewDocument.url);
    }
});
</script>

<style scoped>
.form-label {
    font-weight: 500;
    font-size: 0.875rem;
}
</style>
