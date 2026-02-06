<template>
    <div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Drug Type</label>
                <select v-model="selectedDrugType" @change="fetchDrugs" class="form-select">
                    <option value="">All Types</option>
                    <option v-for="type in drugTypes" :key="type.drug_type_id" :value="type.drug_type_id">
                        {{ type.type_name }}
                    </option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-success w-100" @click="openAddDrugTypeModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Drug Type
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary w-100" @click="openImportModal">
                    <i class="bi bi-upload me-1"></i> Import Drugs
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-info w-100" @click="exportDrugs">
                    <i class="bi bi-download me-1"></i> Export Drugs
                </button>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Drug Name *</label>
                <input type="text" class="form-control" v-model="form.drug_name" />
            </div>
            <div class="col-md-2">
                <label class="form-label">Language</label>
                <select v-model="selectedLanguage" @change="fetchDoseTimes" class="form-select">
                    <option value="english">English</option>
                    <option value="marathi">Marathi</option>
                    <option value="hindi">Hindi</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Dose Time</label>
                <select class="form-select" v-model="form.dose_time">
                    <option value="">Select</option>
                    <option v-for="doseTime in doseTimes" :key="doseTime.dose_time_id" :value="doseTime.dose_time_text">
                        {{ doseTime.dose_time_text }}
                    </option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">Days</label>
                <input type="number" class="form-control" v-model="form.days" min="0" />
            </div>
            <div class="col-md-1">
                <label class="form-label">Qty</label>
                <input type="number" class="form-control" v-model="form.quantity" min="0" />
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-success w-100" @click="saveDrug" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                    Save
                </button>
            </div>
        </div>

        <!-- Drug List -->
        <div class="border rounded p-3" style="height: 400px; overflow-y: auto;">
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border spinner-border-sm me-2"></div>Loading...
            </div>
            <div v-else-if="drugs.length === 0" class="text-center text-muted py-4">
                No drugs found
            </div>
            <div v-else class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Drug Name</th>
                            <th>Language</th>
                            <th>Dose Time</th>
                            <th>Days</th>
                            <th>Qty</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="drug in drugs"
                            :key="drug.drug_master_id"
                            class="cursor-pointer"
                            :class="{ 'table-active': selectedDrug?.drug_master_id === drug.drug_master_id }"
                            @click="selectDrug(drug)"
                        >
                            <td>{{ drug.drug_name }}</td>
                            <td>
                                <span class="badge bg-info text-capitalize">
                                    {{ drug.language }}
                                </span>
                            </td>
                            <td>{{ drug.dose_time || '-' }}</td>
                            <td>{{ drug.days || '-' }}</td>
                            <td>{{ drug.quantity || '-' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        @click.stop="editDrug(drug)"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        @click.stop="confirmDelete(drug)"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Drug Type Modal -->
        <div class="modal fade" id="addDrugTypeModal" tabindex="-1" ref="addDrugTypeModalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Drug Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveDrugType">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Type Name *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="drugTypeForm.type_name"
                                    required
                                    maxlength="100"
                                />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Save
                            </button>
                        </div>
                    </form>
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
                        <div class="alert alert-info">
                            <strong>CSV Format:</strong> Drug Type, Dose Time, Drug Name, Language, Days, Qty
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Download Template</label>
                            <button class="btn btn-outline-primary btn-sm w-100" @click="downloadTemplate">
                                <i class="bi bi-download"></i> Download CSV Template
                            </button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload File</label>
                            <input type="file" class="form-control" @change="handleFileUpload" accept=".xlsx,.xls,.csv" ref="fileInput" />
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

        <!-- Edit Drug Modal -->
        <div class="modal fade" id="editDrugModal" tabindex="-1" ref="editDrugModalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Drug</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="updateDrug">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Drug Type</label>
                                <select v-model="editForm.drug_type_id" class="form-select">
                                    <option :value="null">Select Type</option>
                                    <option v-for="type in drugTypes" :key="type.drug_type_id" :value="type.drug_type_id">
                                        {{ type.type_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Drug Name *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="editForm.drug_name"
                                    required
                                    maxlength="255"
                                />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Language</label>
                                <select v-model="editLanguage" @change="fetchDoseTimesForEdit" class="form-select">
                                    <option value="english">English</option>
                                    <option value="marathi">Marathi</option>
                                    <option value="hindi">Hindi</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dose Time</label>
                                <select class="form-select" v-model="editForm.dose_time">
                                    <option value="">Select</option>
                                    <option v-for="doseTime in editDoseTimes" :key="doseTime.dose_time_id" :value="doseTime.dose_time_text">
                                        {{ doseTime.dose_time_text }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Days</label>
                                <input type="number" class="form-control" v-model="editForm.days" min="0" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" v-model="editForm.quantity" min="0" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const selectedDrugType = ref('');
const selectedLanguage = ref('english');
const editLanguage = ref('english');
const selectedDrug = ref(null);
const drugs = ref([]);
const drugTypes = ref([]);
const doseTimes = ref([]);
const editDoseTimes = ref([]);
const loading = ref(false);
const saving = ref(false);
const importing = ref(false);
const importFile = ref(null);
const fileInput = ref(null);
const addDrugTypeModalRef = ref(null);
const editDrugModalRef = ref(null);
const importModalRef = ref(null);
let addDrugTypeModal = null;
let editDrugModal = null;
let importModal = null;

const form = ref({
    drug_name: '',
    dose_time: '',
    days: '',
    quantity: ''
});

const drugTypeForm = ref({
    type_name: ''
});

const editForm = ref({
    drug_master_id: null,
    drug_type_id: null,
    drug_name: '',
    dose_time: '',
    days: '',
    quantity: ''
});

const fetchDrugTypes = async () => {
    try {
        const response = await axios.get('/api/drug-types');
        drugTypes.value = response.data;
    } catch (error) {
        console.error('Error fetching drug types:', error);
    }
};

const fetchDoseTimes = async () => {
    try {
        const response = await axios.get('/api/dose-time-masters', {
            params: { language: selectedLanguage.value }
        });
        doseTimes.value = response.data;
    } catch (error) {
        console.error('Error fetching dose times:', error);
    }
};

const fetchDoseTimesForEdit = async () => {
    try {
        const response = await axios.get('/api/dose-time-masters', {
            params: { language: editLanguage.value }
        });
        editDoseTimes.value = response.data;
    } catch (error) {
        console.error('Error fetching dose times:', error);
    }
};

const fetchDrugs = async () => {
    loading.value = true;
    try {
        const params = {};
        if (selectedDrugType.value) {
            params.drug_type_id = selectedDrugType.value;
        }
        const response = await axios.get('/api/drug-masters', { params });
        drugs.value = response.data;
    } catch (error) {
        console.error('Error fetching drugs:', error);
        alert('Error loading drugs');
    }
    loading.value = false;
};

const selectDrug = (drug) => {
    selectedDrug.value = drug;
};

const editDrug = async (drug) => {
    editForm.value = {
        drug_master_id: drug.drug_master_id,
        drug_type_id: drug.drug_type_id,
        drug_name: drug.drug_name,
        dose_time: drug.dose_time || '',
        days: drug.days || '',
        quantity: drug.quantity || ''
    };
    selectedDrug.value = drug;
    // Set language from drug data
    editLanguage.value = drug.language || 'english';
    // Fetch dose times for the drug's language
    await fetchDoseTimesForEdit();
    editDrugModal.show();
};

const saveDrug = async () => {
    if (!form.value.drug_name.trim()) {
        alert('Please enter drug name');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.post('/api/drug-masters', {
            drug_type_id: selectedDrugType.value || null,
            drug_name: form.value.drug_name.trim(),
            language: selectedLanguage.value,
            dose_time: form.value.dose_time || null,
            days: form.value.days || null,
            quantity: form.value.quantity || null
        });

        if (response.data.success) {
            form.value = {
                drug_name: '',
                dose_time: '',
                days: '',
                quantity: ''
            };
            await fetchDrugs();
            selectedDrug.value = response.data.data;
            alert('Drug added successfully');
        }
    } catch (error) {
        console.error('Error adding drug:', error);
        alert('Error adding drug');
    }
    saving.value = false;
};

const updateDrug = async () => {
    if (!editForm.value.drug_name.trim()) {
        alert('Please enter drug name');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.put(`/api/drug-masters/${editForm.value.drug_master_id}`, {
            drug_type_id: editForm.value.drug_type_id || null,
            drug_name: editForm.value.drug_name.trim(),
            language: editLanguage.value,
            dose_time: editForm.value.dose_time || null,
            days: editForm.value.days || null,
            quantity: editForm.value.quantity || null
        });

        if (response.data.success) {
            editDrugModal.hide();
            await fetchDrugs();
            alert('Drug updated successfully');
        }
    } catch (error) {
        console.error('Error updating drug:', error);
        alert('Error updating drug');
    }
    saving.value = false;
};

const confirmDelete = async (drug) => {
    if (!confirm(`Are you sure you want to delete this drug?\n\n"${drug.drug_name}"`)) {
        return;
    }

    try {
        const response = await axios.delete(`/api/drug-masters/${drug.drug_master_id}`);

        if (response.data.success) {
            if (selectedDrug.value?.drug_master_id === drug.drug_master_id) {
                selectedDrug.value = null;
            }
            await fetchDrugs();
            alert('Drug deleted successfully');
        }
    } catch (error) {
        console.error('Error deleting drug:', error);
        alert('Error deleting drug');
    }
};

const openAddDrugTypeModal = () => {
    drugTypeForm.value.type_name = '';
    addDrugTypeModal.show();
};

const saveDrugType = async () => {
    if (!drugTypeForm.value.type_name.trim()) {
        alert('Please enter drug type name');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.post('/api/drug-types', {
            type_name: drugTypeForm.value.type_name.trim()
        });

        if (response.data.success) {
            addDrugTypeModal.hide();
            await fetchDrugTypes();
            selectedDrugType.value = response.data.data.drug_type_id;
            await fetchDrugs();
            alert('Drug type added successfully');
        }
    } catch (error) {
        console.error('Error adding drug type:', error);
        if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert('Error adding drug type');
        }
    }
    saving.value = false;
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
            await fetchDrugs();
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

const exportDrugs = async () => {
    try {
        const response = await axios.get('/api/drug-masters-export', {
            responseType: 'blob'
        });

        // Create a blob URL and trigger download
        const blob = new Blob([response.data], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = 'drug_masters_export.csv';
        link.click();
        window.URL.revokeObjectURL(link.href);
    } catch (error) {
        console.error('Error exporting drugs:', error);
        alert('Error exporting drugs');
    }
};

onMounted(() => {
    addDrugTypeModal = new Modal(addDrugTypeModalRef.value);
    editDrugModal = new Modal(editDrugModalRef.value);
    importModal = new Modal(importModalRef.value);
    fetchDrugTypes();
    fetchDoseTimes();
    fetchDrugs();
});
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

.table-responsive {
    max-height: 380px;
    overflow-y: auto;
}

.table thead {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 1;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>
