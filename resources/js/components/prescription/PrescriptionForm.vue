<template>
    <div class="prescription-form">
        <!-- Info Alert - Only show if prescription was auto-loaded from today -->
        <div v-if="showPrescriptionLoadedAlert && prescriptionDrugs.length > 0" class="alert alert-info alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Today's prescription loaded:</strong> {{ prescriptionDrugs.length }} drug(s) from earlier today. You can edit or add more.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- Action Buttons Row -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="btn-toolbar gap-2" role="toolbar">
                    <!-- Standard Rx -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-file-medical"></i>
                            Standard Rx
                        </button>
                        <ul class="dropdown-menu">
                            <li v-if="standardRxList.length === 0" class="dropdown-item text-muted">No Standard Rx found</li>
                            <li v-for="rx in standardRxList" :key="rx.id">
                                <a class="dropdown-item" href="#" @click.prevent="loadStandardRx(rx)">
                                    {{ rx.disease_name }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Copy Last Rx -->
                    <button class="btn btn-sm btn-outline-secondary" @click="copyLastRx">
                        <i class="bi bi-clipboard"></i>
                        Copy Last Rx
                    </button>

                    <!-- Remove Standard Rx -->
                    <button class="btn btn-sm btn-outline-warning" @click="removeStandardRx">
                        <i class="bi bi-x-circle"></i>
                        Remove Standard Rx
                    </button>

                    <!-- Remove Last Rx -->
                    <button class="btn btn-sm btn-outline-warning" @click="removeLastRx">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Remove Last Rx
                    </button>

                    <!-- Advice -->
                    <button class="btn btn-sm btn-outline-info" @click="openAdviceModal">
                        <i class="bi bi-chat-dots"></i>
                        Advice
                    </button>

                    <!-- Save & Print -->
                    <button class="btn btn-sm btn-success" @click="saveAndPrint">
                        <i class="bi bi-save me-1"></i>
                        Save & Print
                    </button>

                    <!-- Import Drugs Data -->
                    <button class="btn btn-sm btn-outline-primary" @click="openImportModal">
                        <i class="bi bi-upload"></i>
                        Import Drugs Data
                    </button>
                </div>
            </div>
        </div>

        <!-- Drug Entry Form -->
        <div class="row mb-2">
            <div class="col-md-2">
                <label class="form-label small">Name</label>
                <input
                    type="text"
                    class="form-control form-control-sm"
                    v-model="drugForm.searchTerm"
                    @input="searchDrugs"
                    @keydown.down.prevent="navigateDropdown(1)"
                    @keydown.up.prevent="navigateDropdown(-1)"
                    @keydown.enter.prevent="selectDrug(filteredDrugs[selectedIndex])"
                    placeholder="Search drug..."
                    autocomplete="off"
                />
                <!-- Autocomplete Dropdown -->
                <div v-if="showDrugDropdown && filteredDrugs.length > 0" class="autocomplete-dropdown">
                    <div
                        v-for="(drug, index) in filteredDrugs"
                        :key="drug.drug_master_id"
                        class="autocomplete-item"
                        :class="{ active: index === selectedIndex }"
                        @click="selectDrug(drug)"
                        @mouseenter="selectedIndex = index"
                    >
                        {{ drug.drug_name }}
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Drug Type</label>
                <input type="text" class="form-control form-control-sm" v-model="drugForm.drug_type" readonly />
            </div>
            <div class="col-md-1">
                <label class="form-label small">Language</label>
                <select class="form-select form-select-sm" v-model="drugForm.language" @change="fetchDoseTimes">
                    <option value="english">English</option>
                    <option value="marathi">Marathi</option>
                    <option value="hindi">Hindi</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Dose Advice</label>
                <select class="form-select form-select-sm" v-model="drugForm.dose_advice">
                    <option value="">Select</option>
                    <option v-for="dt in doseTimes" :key="dt.dose_time_id" :value="dt.dose_time_text">
                        {{ dt.dose_time_text }}
                    </option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small">Days</label>
                <input type="number" class="form-control form-control-sm" v-model="drugForm.days" min="0" />
            </div>
            <div class="col-md-1">
                <label class="form-label small">Qty</label>
                <input type="number" class="form-control form-control-sm" v-model="drugForm.qty" min="0" />
            </div>
            <div class="col-md-1">
                <label class="form-label small">&nbsp;</label>
                <button class="btn btn-primary btn-sm w-100" @click="addDrug">Add</button>
            </div>
        </div>

        <!-- Drugs Table -->
        <div class="table-responsive mb-3" style="max-height: 300px;">
            <table class="table table-sm table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%">Sr.No.</th>
                        <th style="width: 25%">Drug Names</th>
                        <th style="width: 15%">Drug Type</th>
                        <th style="width: 10%">Language</th>
                        <th style="width: 20%">Dose Time</th>
                        <th style="width: 10%">Days</th>
                        <th style="width: 10%">Qty</th>
                        <th style="width: 5%">*</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="prescriptionDrugs.length === 0">
                        <td colspan="8" class="text-center text-muted">No drugs added</td>
                    </tr>
                    <tr v-for="(drug, index) in prescriptionDrugs" :key="index">
                        <td>{{ index + 1 }}</td>
                        <td>{{ drug.drug_name }}</td>
                        <td>{{ drug.drug_type }}</td>
                        <td><span class="badge bg-info text-capitalize">{{ drug.language }}</span></td>
                        <td>{{ drug.dose_advice }}</td>
                        <td>{{ drug.days }}</td>
                        <td>{{ drug.qty }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" @click="removeDrug(index)" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Bottom Section -->
        <div class="row">
            <div class="col-md-4">
                <label class="form-label small">Advice</label>
                <div class="input-group input-group-sm mb-2">
                    <textarea class="form-control" v-model="adviceText" rows="3" readonly></textarea>
                    <button class="btn btn-outline-secondary" type="button" @click="attachDocument('advice')">
                        Attach Document
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Investigations</label>
                <div class="input-group input-group-sm mb-2">
                    <textarea class="form-control" v-model="investigations" rows="3"></textarea>
                    <button class="btn btn-outline-secondary" type="button" @click="attachDocument('investigation')">
                        Attach Document
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" v-model="qtyDisplayOnPrint" id="qtyDisplay">
                    <label class="form-check-label small" for="qtyDisplay">
                        Qty Displayed On Print
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" v-model="printConsultation" id="printConsultation">
                    <label class="form-check-label small" for="printConsultation">
                        Print Consultation
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" v-model="saveAsStandardRx" id="standardRx">
                    <label class="form-check-label small" for="standardRx">
                        Use as Standard Rx
                    </label>
                </div>
                <input
                    v-if="saveAsStandardRx"
                    type="text"
                    class="form-control form-control-sm mt-2"
                    v-model="diseaseName"
                    placeholder="Enter Disease Name"
                />
            </div>
        </div>

        <!-- Advice Modal -->
        <div class="modal fade" id="adviceModal" tabindex="-1" ref="adviceModalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Advice</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Language</label>
                            <select v-model="adviceForm.language" @change="fetchAdviceList" class="form-select">
                                <option value="english">English</option>
                                <option value="marathi">Marathi</option>
                                <option value="hindi">Hindi</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Advice</label>
                            <div class="input-group">
                                <textarea class="form-control" v-model="adviceForm.advice_text" rows="3" placeholder="Type new advice..."></textarea>
                                <button class="btn btn-outline-success" type="button" @click="saveAdviceToMaster" :disabled="!adviceForm.advice_text.trim()">
                                    <i class="bi bi-save"></i> Save
                                </button>
                            </div>
                            <small class="text-muted">Type new advice and click Save to add to master list</small>
                        </div>
                        <div v-if="adviceList.length > 0" class="mb-3">
                            <label class="form-label">Saved Advice (Click to use)</label>
                            <div class="list-group" style="max-height: 250px; overflow-y: auto;">
                                <button
                                    v-for="advice in adviceList"
                                    :key="advice.advice_id"
                                    type="button"
                                    class="list-group-item list-group-item-action text-start"
                                    @click="selectAdvice(advice)"
                                >
                                    {{ advice.advice_text }}
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-center text-muted py-3">
                            <i class="bi bi-info-circle"></i> No saved advice found. Type above and click Save to create one.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" @click="addAdviceToPrescription" :disabled="!adviceForm.advice_text.trim()">
                            <i class="bi bi-plus-lg me-1"></i> Add to Prescription
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <div class="modal fade" id="importModal" tabindex="-1" ref="importModalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Import Drugs Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Download Template</label>
                            <button class="btn btn-outline-primary btn-sm w-100" @click="downloadTemplate">
                                <i class="bi bi-download"></i> Download Excel Template
                            </button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload File</label>
                            <input type="file" class="form-control" @change="handleFileUpload" accept=".xlsx,.xls" ref="fileInput" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" @click="importDrugs" :disabled="!importFile || importing">
                            <span v-if="importing" class="spinner-border spinner-border-sm me-1"></span>
                            Import
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

// Props
const props = defineProps({
    patientId: {
        type: Number,
        default: null
    }
});

// State
const prescriptionDrugs = ref([]);
const drugMasters = ref([]);
const doseTimes = ref([]);
const adviceList = ref([]);
const standardRxList = ref([]);
const adviceText = ref('');
const investigations = ref('');
const qtyDisplayOnPrint = ref(true);
const printConsultation = ref(false);
const saveAsStandardRx = ref(false);
const diseaseName = ref('');
const showDrugDropdown = ref(false);
const selectedIndex = ref(0);
const importing = ref(false);
const importFile = ref(null);
const fileInput = ref(null);
const showPrescriptionLoadedAlert = ref(false);

// Modals
const adviceModalRef = ref(null);
const importModalRef = ref(null);
let adviceModal = null;
let importModal = null;

// Forms
const drugForm = reactive({
    searchTerm: '',
    drug_master_id: null,
    drug_name: '',
    drug_type: '',
    language: 'english',
    dose_advice: '',
    days: '',
    qty: ''
});

const adviceForm = reactive({
    language: 'english',
    advice_text: ''
});

// Computed
const filteredDrugs = computed(() => {
    if (!drugForm.searchTerm || drugForm.searchTerm.length < 2) {
        return [];
    }
    return drugMasters.value.filter(drug =>
        drug.drug_name.toLowerCase().includes(drugForm.searchTerm.toLowerCase())
    ).slice(0, 10);
});

// Methods
const fetchDrugMasters = async () => {
    try {
        const response = await axios.get('/api/drug-masters');
        drugMasters.value = response.data;
    } catch (error) {
        console.error('Error fetching drug masters:', error);
    }
};

const fetchDoseTimes = async () => {
    try {
        const response = await axios.get('/api/dose-time-masters', {
            params: { language: drugForm.language }
        });
        doseTimes.value = response.data;
    } catch (error) {
        console.error('Error fetching dose times:', error);
    }
};

const fetchAdviceList = async () => {
    try {
        const response = await axios.get('/api/advice-masters', {
            params: { language: adviceForm.language }
        });
        adviceList.value = response.data;
    } catch (error) {
        console.error('Error fetching advice list:', error);
        adviceList.value = [];
    }
};

const fetchStandardRxList = async () => {
    try {
        const response = await axios.get('/api/standard-rx');
        standardRxList.value = response.data;
    } catch (error) {
        console.error('Error fetching standard rx list:', error);
        standardRxList.value = [];
    }
};

const loadLastPrescription = async () => {
    if (!props.patientId) return;

    try {
        const response = await axios.get(`/api/prescriptions/last/${props.patientId}`);
        console.log('Prescription response:', response.data);
        if (response.data && response.data.drugs) {
            // Check if prescription is from today
            const prescriptionDate = new Date(response.data.created_at || response.data.prescription_date);
            const today = new Date();

            // Compare only the date part (ignore time)
            const isSameDay = prescriptionDate.getFullYear() === today.getFullYear() &&
                            prescriptionDate.getMonth() === today.getMonth() &&
                            prescriptionDate.getDate() === today.getDate();

            if (isSameDay) {
                // Only load if prescription is from today
                prescriptionDrugs.value = response.data.drugs;
                adviceText.value = response.data.advice || '';
                investigations.value = response.data.investigations || '';
                showPrescriptionLoadedAlert.value = true; // Show alert for today's prescription

                console.log('Loaded today\'s prescription with', prescriptionDrugs.value.length, 'drugs');
            } else {
                console.log('Previous prescription is from a different day - starting fresh');
                showPrescriptionLoadedAlert.value = false; // Don't show alert
            }
        }
    } catch (error) {
        // No previous prescription found - that's okay
        console.log('No previous prescription found for patient');
        showPrescriptionLoadedAlert.value = false;
    }
};

const searchDrugs = () => {
    showDrugDropdown.value = drugForm.searchTerm.length >= 2;
    selectedIndex.value = 0;
};

const navigateDropdown = (direction) => {
    if (filteredDrugs.value.length === 0) return;
    selectedIndex.value = Math.max(0, Math.min(filteredDrugs.value.length - 1, selectedIndex.value + direction));
};

const selectDrug = async (drug) => {
    if (!drug) return;

    drugForm.drug_master_id = drug.drug_master_id;
    drugForm.drug_name = drug.drug_name;
    drugForm.searchTerm = drug.drug_name;
    drugForm.drug_type = drug.drug_type?.type_name || '';
    drugForm.language = drug.language || 'english';
    drugForm.dose_advice = drug.dose_time || '';
    drugForm.days = drug.days || '';
    drugForm.qty = drug.quantity || '';

    showDrugDropdown.value = false;

    // Fetch dose times for this language
    await fetchDoseTimes();
};

const addDrug = () => {
    if (!drugForm.drug_name) {
        alert('Please select a drug');
        return;
    }

    prescriptionDrugs.value.push({
        drug_master_id: drugForm.drug_master_id,
        drug_name: drugForm.drug_name,
        drug_type: drugForm.drug_type,
        language: drugForm.language,
        dose_advice: drugForm.dose_advice,
        days: drugForm.days,
        qty: drugForm.qty
    });

    // Reset form
    Object.assign(drugForm, {
        searchTerm: '',
        drug_master_id: null,
        drug_name: '',
        drug_type: '',
        language: 'english',
        dose_advice: '',
        days: '',
        qty: ''
    });
};

const removeDrug = (index) => {
    if (confirm('Are you sure you want to delete this drug?')) {
        prescriptionDrugs.value.splice(index, 1);
    }
};

const openAdviceModal = () => {
    fetchAdviceList();
    adviceModal.show();
};

const selectAdvice = (advice) => {
    adviceForm.advice_text = advice.advice_text;
};

const saveAdviceToMaster = async () => {
    if (!adviceForm.advice_text.trim()) {
        alert('Please enter advice text');
        return;
    }

    try {
        const response = await axios.post('/api/advice-masters', {
            advice_text: adviceForm.advice_text.trim(),
            language: adviceForm.language
        });

        if (response.data.success) {
            alert('Advice saved to master list successfully');
            // Refresh advice list
            await fetchAdviceList();
            // Don't clear the text, user might want to add it to prescription
        }
    } catch (error) {
        console.error('Error saving advice:', error);
        if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert('Error saving advice to master');
        }
    }
};

const addAdviceToPrescription = () => {
    if (adviceForm.advice_text.trim()) {
        // Add to prescription advice (append if there's existing advice)
        if (adviceText.value && adviceText.value.trim()) {
            adviceText.value += '\n' + adviceForm.advice_text.trim();
        } else {
            adviceText.value = adviceForm.advice_text.trim();
        }

        // Clear form and close modal
        adviceForm.advice_text = '';
        adviceModal.hide();
        alert('Advice added to prescription');
    }
};

const loadStandardRx = async (rx) => {
    try {
        const response = await axios.get(`/api/standard-rx/${rx.id}`);
        prescriptionDrugs.value = response.data.drugs;
        adviceText.value = response.data.advice || '';
        alert('Standard Rx loaded successfully');
    } catch (error) {
        console.error('Error loading standard rx:', error);
        alert('Error loading standard rx');
    }
};

const copyLastRx = async () => {
    if (!props.patientId) {
        alert('Patient ID is required');
        return;
    }

    try {
        const response = await axios.get(`/api/prescriptions/last/${props.patientId}`);
        if (response.data) {
            prescriptionDrugs.value = response.data.drugs;
            adviceText.value = response.data.advice || '';
            investigations.value = response.data.investigations || '';
            alert('Last prescription copied successfully');
        }
    } catch (error) {
        console.error('Error copying last rx:', error);
        alert('No previous prescription found');
    }
};

const removeStandardRx = () => {
    if (confirm('Are you sure you want to remove all drugs?')) {
        prescriptionDrugs.value = [];
        adviceText.value = '';
        showPrescriptionLoadedAlert.value = false;
    }
};

const removeLastRx = () => {
    if (confirm('Are you sure you want to clear the prescription?')) {
        prescriptionDrugs.value = [];
        adviceText.value = '';
        showPrescriptionLoadedAlert.value = false;
        investigations.value = '';
    }
};

const saveAndPrint = async () => {
    if (prescriptionDrugs.value.length === 0) {
        alert('Please add at least one drug');
        return;
    }

    try {
        const payload = {
            patient_id: props.patientId,
            drugs: prescriptionDrugs.value,
            advice: adviceText.value,
            investigations: investigations.value,
            qty_display_on_print: qtyDisplayOnPrint.value,
            print_consultation: printConsultation.value
        };

        // If save as standard rx
        if (saveAsStandardRx.value && diseaseName.value.trim()) {
            payload.save_as_standard_rx = true;
            payload.disease_name = diseaseName.value;
        }

        const response = await axios.post('/api/prescriptions', payload);

        if (response.data.success) {
            const prescriptionId = response.data.data.prescription_id;

            // Ask user if they want to print
            if (confirm('Prescription saved successfully! Do you want to print now?')) {
                // Open print page in new window
                const printUrl = `/prescription/${prescriptionId}/print?consultation=${printConsultation.value ? 1 : 0}`;
                window.open(printUrl, '_blank');
            }

            // Reload the saved prescription
            await loadLastPrescription();
        }
    } catch (error) {
        console.error('Error saving prescription:', error);
        console.error('Error response:', error.response);

        let errorMessage = 'Error saving prescription';

        if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.response?.data?.error) {
            errorMessage = error.response.data.error;
        } else if (error.message) {
            errorMessage = error.message;
        }

        // Show validation errors if any
        if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat();
            errorMessage += '\n\nValidation Errors:\n' + errors.join('\n');
        }

        alert(errorMessage);
    }
};

const openImportModal = () => {
    importModal.show();
};

const handleFileUpload = (event) => {
    importFile.value = event.target.files[0];
};

const downloadTemplate = async () => {
    try {
        const response = await axios.get('/api/drug-masters-template', {
            responseType: 'blob'
        });

        // Create a blob URL and trigger download
        const blob = new Blob([response.data], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = 'drug_import_template.csv';
        link.click();
        window.URL.revokeObjectURL(link.href);
    } catch (error) {
        console.error('Error downloading template:', error);
        alert('Error downloading template');
    }
};

const importDrugs = async () => {
    if (!importFile.value) {
        alert('Please select a file');
        return;
    }

    importing.value = true;
    const formData = new FormData();
    formData.append('file', importFile.value);

    try {
        const response = await axios.post('/api/drug-masters-import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (response.data.success) {
            alert(`Successfully imported ${response.data.count} drugs`);
            importModal.hide();
            await fetchDrugMasters();
            importFile.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        }
    } catch (error) {
        console.error('Error importing drugs:', error);
        alert(error.response?.data?.message || 'Error importing drugs');
    }
    importing.value = false;
};

const attachDocument = (type) => {
    // TODO: Implement document attachment
    alert(`Attach document for ${type} - Feature coming soon`);
};

// Click outside to close dropdown
const handleClickOutside = (event) => {
    const autocompleteEl = document.querySelector('.autocomplete-dropdown');
    const inputEl = event.target;

    if (autocompleteEl && !autocompleteEl.contains(inputEl) && inputEl.tagName !== 'INPUT') {
        showDrugDropdown.value = false;
    }
};

// Keyboard shortcut handler (removed F11)

onMounted(() => {
    adviceModal = new Modal(adviceModalRef.value);
    importModal = new Modal(importModalRef.value);

    fetchDrugMasters();
    fetchDoseTimes();
    fetchStandardRxList();

    // Load last prescription for this patient
    loadLastPrescription();

    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.autocomplete-dropdown {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 1px solid #ddd;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-top: 2px;
}

.autocomplete-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.autocomplete-item:hover,
.autocomplete-item.active {
    background-color: #e9ecef;
}

.autocomplete-item:last-child {
    border-bottom: none;
}

.table-responsive {
    overflow-y: auto;
}

.table thead {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
}

.btn-toolbar {
    display: flex;
    flex-wrap: wrap;
}
</style>
